<?php

require_once "../includes/customer_header.php";
require_once "../config/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: packages.php");
    exit();
}

$package_id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| Get Package Details
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        tour_packages.id,
        tour_packages.package_name,
        tour_packages.description,
        tour_packages.duration,
        tour_packages.price,
        destinations.name AS destination_name,
        destinations.location AS destination_location,
        destinations.image AS destination_image
    FROM tour_packages
    INNER JOIN destinations
        ON tour_packages.destination_id = destinations.id
    WHERE tour_packages.id = ?
      AND tour_packages.status = 1
      AND destinations.status = 1
");

$stmt->bind_param("i", $package_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='error-box'>Tour package not found.</div>";
    require_once "../includes/customer_footer.php";
    exit();
}

$package = $result->fetch_assoc();

$error = "";

/*
|--------------------------------------------------------------------------
| Handle Booking
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $travel_date = $_POST['travel_date'] ?? "";
    $persons = (int) ($_POST['persons'] ?? 0);

    if (empty($travel_date)) {

        $error = "Please select a travel date.";

    } elseif ($travel_date < date("Y-m-d")) {

        $error = "Travel date cannot be in the past.";

    } elseif ($persons < 1) {

        $error = "Please enter at least 1 person.";

    } else {

        $total_amount = $package['price'] * $persons;

        $user_id = $_SESSION['user_id'];

        $insert = $conn->prepare("
            INSERT INTO bookings
            (user_id, package_id, travel_date, persons, total_amount, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");

        $insert->bind_param(
            "iisid",
            $user_id,
            $package_id,
            $travel_date,
            $persons,
            $total_amount
        );

        if ($insert->execute()) {

            $booking_id = $insert->insert_id;

            header("Location: payment.php?id=" . $booking_id);
            exit();

        } else {

            $error = "Unable to create booking. Please try again.";
        }
    }
}

?>

<style>

.booking-page {
    max-width: 1100px;
    margin: 0 auto;
}

.booking-heading {
    margin-bottom: 25px;
}

.booking-heading h1 {
    font-size: 28px;
    margin-bottom: 8px;
    color: #111827;
}

.booking-heading p {
    color: #6b7280;
    font-size: 14px;
}

.booking-layout {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 25px;
}

/* Package Card */

.package-summary {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}

.package-image {
    height: 260px;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
}

.package-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    color: #9ca3af;
    font-size: 14px;
}

.package-info {
    padding: 25px;
}

.package-info h2 {
    font-size: 23px;
    margin-bottom: 10px;
}

.destination {
    color: #2563eb;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 18px;
}

.description {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.7;
    margin-bottom: 20px;
}

.package-details {
    display: flex;
    gap: 30px;
    padding-top: 18px;
    border-top: 1px solid #e5e7eb;
}

.detail-item {
    font-size: 13px;
    color: #6b7280;
}

.detail-item strong {
    display: block;
    color: #111827;
    font-size: 18px;
    margin-top: 5px;
}

/* Booking Form */

.booking-form {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
}

.booking-form h2 {
    font-size: 21px;
    margin-bottom: 22px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: bold;
    color: #374151;
    margin-bottom: 8px;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    font-size: 14px;
    outline: none;
}

.form-group input:focus {
    border-color: #2563eb;
}

.total-box {
    background: #f3f6ff;
    border: 1px solid #dbe4ff;
    border-radius: 8px;
    padding: 18px;
    margin: 25px 0;
}

.total-label {
    font-size: 13px;
    color: #6b7280;
}

.total-amount {
    font-size: 26px;
    font-weight: bold;
    color: #111827;
    margin-top: 5px;
}

.payment-note {
    font-size: 12px;
    color: #6b7280;
    margin-top: 5px;
}

.book-button {
    width: 100%;
    border: none;
    background: #2563eb;
    color: white;
    padding: 13px;
    border-radius: 7px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
}

.book-button:hover {
    background: #1d4ed8;
}

.back-link {
    display: inline-block;
    margin-top: 15px;
    color: #2563eb;
    text-decoration: none;
    font-size: 13px;
}

.error-box {
    max-width: 1100px;
    margin: 30px auto;
    padding: 15px;
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
    border-radius: 8px;
}

.error-message {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 20px;
    font-size: 13px;
}

/* Responsive */

@media (max-width: 800px) {

    .booking-layout {
        grid-template-columns: 1fr;
    }

    .package-image {
        height: 220px;
    }

}

@media (max-width: 500px) {

    .package-details {
        flex-direction: column;
        gap: 15px;
    }

}

</style>


<div class="booking-page">

    <div class="booking-heading">
        <h1>Book Your Tour</h1>
        <p>Review your selected package and provide your travel details.</p>
    </div>


    <?php if (!empty($error)): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="booking-layout">

        <!-- Package Summary -->

        <div class="package-summary">

            <div class="package-image">

                <?php if (!empty($package['destination_image'])): ?>

                    <img
                        src="../<?php echo htmlspecialchars($package['destination_image']); ?>"
                        alt="<?php echo htmlspecialchars($package['package_name']); ?>"
                    >

                <?php else: ?>

                    <div class="no-image">
                        No image available
                    </div>

                <?php endif; ?>

            </div>


            <div class="package-info">

                <h2>
                    <?php echo htmlspecialchars($package['package_name']); ?>
                </h2>

                <div class="destination">

                    📍
                    <?php echo htmlspecialchars($package['destination_name']); ?>

                    -
                    <?php echo htmlspecialchars($package['destination_location']); ?>

                </div>


                <div class="description">

                    <?php

                    if (!empty($package['description'])) {

                        echo htmlspecialchars($package['description']);

                    } else {

                        echo "Explore this destination with our carefully planned tour package.";

                    }

                    ?>

                </div>


                <div class="package-details">

                    <div class="detail-item">

                        Duration

                        <strong>
                            <?php echo $package['duration']; ?> Days
                        </strong>

                    </div>


                    <div class="detail-item">

                        Price

                        <strong>
                            ₹<?php echo number_format($package['price'], 2); ?>
                        </strong>

                    </div>

                </div>

            </div>

        </div>


        <!-- Booking Form -->

        <div class="booking-form">

            <h2>Travel Details</h2>


            <form method="POST">


                <div class="form-group">

                    <label for="travel_date">
                        Travel Date
                    </label>

                    <input
                        type="date"
                        id="travel_date"
                        name="travel_date"
                        min="<?php echo date('Y-m-d'); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="persons">
                        Number of Persons
                    </label>

                    <input
                        type="number"
                        id="persons"
                        name="persons"
                        min="1"
                        value="1"
                        required
                    >

                </div>


                <div class="total-box">

                    <div class="total-label">
                        Estimated Total
                    </div>

                    <div class="total-amount">
                        ₹<span id="totalAmount">
                            <?php echo number_format($package['price'], 2); ?>
                        </span>
                    </div>

                    <div class="payment-note">
                        Total amount = price per person × number of persons
                    </div>

                </div>


                <button type="submit" class="book-button">
                    Proceed to Payment
                </button>


                <a href="packages.php" class="back-link">
                    ← Back to Tour Packages
                </a>

            </form>

        </div>

    </div>

</div>


<script>

const personsInput = document.getElementById("persons");

const totalAmount = document.getElementById("totalAmount");

const pricePerPerson = <?php echo (float) $package['price']; ?>;


personsInput.addEventListener("input", function () {

    let persons = parseInt(this.value) || 1;

    if (persons < 1) {
        persons = 1;
    }

    const total = pricePerPerson * persons;

    totalAmount.textContent = total.toLocaleString("en-IN", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

});

</script>


<?php require_once "../includes/customer_footer.php"; ?>