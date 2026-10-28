
<?php

require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure customer is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: bookings.php");
    exit();
}

$booking_id = (int) $_GET['id'];
$user_id = (int) $_SESSION['user_id'];
$error = "";

// Fetch booking details
$stmt = $conn->prepare("
    SELECT
        b.id,
        b.travel_date,
        b.persons,
        b.total_amount,
        b.status,
        tp.package_name,
        tp.duration,
        d.name AS destination_name,
        d.location AS destination_location
    FROM bookings b
    INNER JOIN tour_packages tp ON b.package_id = tp.id
    INNER JOIN destinations d ON tp.destination_id = d.id
    WHERE b.id = ? AND b.user_id = ?
");

$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    require_once "../includes/customer_header.php";
    echo "<div class='error-message'>Booking not found.</div>";
    require_once "../includes/customer_footer.php";
    exit();
}

$booking = $result->fetch_assoc();

// Process payment before producing HTML
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $payment_method = $_POST['payment_method'] ?? '';
    $allowed_methods = ['cash', 'upi', 'card'];

    if (!in_array($payment_method, $allowed_methods, true)) {
        $error = "Please select a valid payment method.";
    } elseif ($booking['status'] === 'confirmed') {
        // Prevent a duplicate demo payment for confirmed bookings
        $check = $conn->prepare("
            SELECT id FROM payments
            WHERE booking_id = ? AND payment_status = 'paid'
            LIMIT 1
        ");
        $check->bind_param("i", $booking_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            header("Location: confirmation.php?id=" . $booking_id);
            exit();
        }

        $error = "This booking is already confirmed. Please contact support.";
    } else {

        $check = $conn->prepare("
            SELECT id FROM payments
            WHERE booking_id = ? AND payment_status = 'paid'
            LIMIT 1
        ");
        $check->bind_param("i", $booking_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            header("Location: confirmation.php?id=" . $booking_id);
            exit();
        }

        $transaction_id = "TXN" . time() . rand(100, 999);

        $payment = $conn->prepare("
            INSERT INTO payments
                (booking_id, payment_method, transaction_id,
                 amount, payment_status)
            VALUES (?, ?, ?, ?, 'paid')
        ");

        $payment->bind_param(
            "issd",
            $booking_id,
            $payment_method,
            $transaction_id,
            $booking['total_amount']
        );

        if ($payment->execute()) {
            $update = $conn->prepare("
                UPDATE bookings
                SET status = 'confirmed'
                WHERE id = ? AND user_id = ?
            ");
            $update->bind_param("ii", $booking_id, $user_id);

            if ($update->execute() && $update->affected_rows >= 0) {
                header("Location: confirmation.php?id=" . $booking_id);
                exit();
            }

            $error = "Payment was recorded, but booking confirmation failed. Contact support.";
        } else {
            $error = "Payment could not be processed. Please try again.";
        }
    }
}

// Load the customer header only after all redirects
require_once "../includes/customer_header.php";

?>

<style>
.payment-page{max-width:1050px;margin:30px auto;padding:0 18px}
.payment-heading{margin-bottom:25px}
.payment-heading h1{font-size:28px;color:#111827;margin-bottom:8px}
.payment-heading p{color:#6b7280;font-size:14px}
.payment-layout{display:grid;grid-template-columns:1fr 380px;gap:25px}
.booking-summary,.payment-card{background:white;border:1px solid #e5e7eb;border-radius:12px;padding:25px}
.booking-summary h2,.payment-card h2{font-size:20px;margin-bottom:20px}
.package-name{font-size:22px;font-weight:bold;color:#111827;margin-bottom:8px}
.destination{color:#2563eb;font-size:14px;font-weight:bold;margin-bottom:25px}
.summary-list{border-top:1px solid #e5e7eb}
.summary-row{display:flex;justify-content:space-between;gap:20px;padding:15px 0;border-bottom:1px solid #f0f0f0;font-size:14px}
.summary-label{color:#6b7280}
.summary-value{color:#111827;font-weight:bold;text-align:right}
.total-row{margin-top:5px;padding-top:20px}
.total-row .summary-label{font-size:15px;font-weight:bold;color:#374151}
.total-row .summary-value{font-size:24px;color:#111827}
.payment-method{margin-bottom:12px}
.payment-method input{position:absolute;opacity:0}
.payment-method label{display:block;border:1px solid #d1d5db;border-radius:8px;padding:15px;cursor:pointer;transition:.2s}
.payment-method label:hover{border-color:#2563eb;background:#f8faff}
.payment-method input:checked+label{border-color:#2563eb;background:#eff6ff}
.payment-method input:focus-visible+label{outline:2px solid #2563eb}
.method-title{font-size:14px;font-weight:bold;color:#111827}
.method-description{font-size:12px;color:#6b7280;margin-top:4px}
.demo-notice{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:12px;border-radius:7px;margin:20px 0;font-size:12px;line-height:1.5}
.pay-button{width:100%;border:none;background:#2563eb;color:white;padding:13px;border-radius:7px;font-size:14px;font-weight:bold;cursor:pointer}
.pay-button:hover{background:#1d4ed8}
.error-message{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;padding:12px;border-radius:7px;margin-bottom:20px;font-size:13px}
.back-link{display:block;text-align:center;margin-top:15px;color:#2563eb;text-decoration:none;font-size:13px}
@media(max-width:800px){.payment-layout{grid-template-columns:1fr}}
@media(max-width:500px){.summary-row{flex-direction:column;gap:5px}.summary-value{text-align:left}}
</style>

<div class="payment-page">

    <div class="payment-heading">
        <h1>Complete Your Payment</h1>
        <p>Review your booking details and select a payment method.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="payment-layout">

        <div class="booking-summary">
            <h2>Booking Summary</h2>

            <div class="package-name">
                <?php echo htmlspecialchars($booking['package_name']); ?>
            </div>

            <div class="destination">
                📍 <?php echo htmlspecialchars($booking['destination_name']); ?>
                - <?php echo htmlspecialchars($booking['destination_location']); ?>
            </div>

            <div class="summary-list">

                <div class="summary-row">
                    <div class="summary-label">Booking ID</div>
                    <div class="summary-value">#<?php echo (int)$booking['id']; ?></div>
                </div>

                <div class="summary-row">
                    <div class="summary-label">Travel Date</div>
                    <div class="summary-value">
                        <?php echo date("d M Y", strtotime($booking['travel_date'])); ?>
                    </div>
                </div>

                <div class="summary-row">
                    <div class="summary-label">Number of Persons</div>
                    <div class="summary-value"><?php echo (int)$booking['persons']; ?></div>
                </div>

                <div class="summary-row">
                    <div class="summary-label">Duration</div>
                    <div class="summary-value"><?php echo htmlspecialchars($booking['duration']); ?> Days</div>
                </div>

                <div class="summary-row">
                    <div class="summary-label">Price per Person</div>
                    <div class="summary-value">
                        ₹<?php
                        echo number_format(
                            (float)$booking['total_amount'] /
                            max(1, (int)$booking['persons']),
                            2
                        );
                        ?>
                    </div>
                </div>

                <div class="summary-row total-row">
                    <div class="summary-label">Total Amount</div>
                    <div class="summary-value">
                        ₹<?php echo number_format((float)$booking['total_amount'], 2); ?>
                    </div>
                </div>

            </div>
        </div>

        <div class="payment-card">
            <h2>Select Payment Method</h2>

            <form method="POST" action="">
                <div class="payment-method">
                    <input type="radio" id="cash" name="payment_method" value="cash" required>
                    <label for="cash">
                        <div class="method-title">💵 Cash</div>
                        <div class="method-description">Pay in cash for your booking.</div>
                    </label>
                </div>

                <div class="payment-method">
                    <input type="radio" id="upi" name="payment_method" value="upi">
                    <label for="upi">
                        <div class="method-title">📱 UPI</div>
                        <div class="method-description">Demo UPI payment selection.</div>
                    </label>
                </div>

                <div class="payment-method">
                    <input type="radio" id="card" name="payment_method" value="card">
                    <label for="card">
                        <div class="method-title">💳 Card</div>
                        <div class="method-description">Demo card payment selection.</div>
                    </label>
                </div>

                <div class="demo-notice">
                    <strong>Demo Payment:</strong>
                    This project simulates payment for learning purposes.
                    No real money or payment gateway is used.
                </div>

                <button type="submit" class="pay-button">Confirm Payment</button>

                <a href="bookings.php" class="back-link">← Back to My Bookings</a>
            </form>
        </div>

    </div>
</div>

<?php require_once "../includes/customer_footer.php"; ?>
