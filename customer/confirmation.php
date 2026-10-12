<?php

require_once "../includes/customer_header.php";
require_once "../config/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: bookings.php");
    exit();
}

$booking_id = (int) $_GET['id'];

$user_id = $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Get Booking + Payment Details
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        bookings.id AS booking_id,
        bookings.travel_date,
        bookings.persons,
        bookings.total_amount,
        bookings.status AS booking_status,

        tour_packages.package_name,
        tour_packages.duration,

        destinations.name AS destination_name,
        destinations.location AS destination_location,

        payments.payment_method,
        payments.transaction_id,
        payments.payment_status,
        payments.payment_date

    FROM bookings

    INNER JOIN tour_packages
        ON bookings.package_id = tour_packages.id

    INNER JOIN destinations
        ON tour_packages.destination_id = destinations.id

    LEFT JOIN payments
        ON bookings.id = payments.booking_id
        AND payments.payment_status = 'paid'

    WHERE bookings.id = ?
      AND bookings.user_id = ?

    LIMIT 1
");

$stmt->bind_param("ii", $booking_id, $user_id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    echo "<div class='error-box'>Booking details could not be found.</div>";

    require_once "../includes/customer_footer.php";

    exit();
}


$booking = $result->fetch_assoc();

?>

<style>

/* =========================================================
   Confirmation Page
   ========================================================= */

.confirmation-page {
    max-width: 950px;
    margin: 0 auto;
}


/* Header */

.confirmation-header {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    padding: 35px 25px;
    margin-bottom: 25px;
}

.success-icon {
    width: 65px;
    height: 65px;
    margin: 0 auto 15px;

    border-radius: 50%;

    background: #dcfce7;
    color: #15803d;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 32px;
    font-weight: bold;
}

.confirmation-header h1 {
    font-size: 28px;
    color: #111827;
    margin-bottom: 8px;
}

.confirmation-header p {
    color: #6b7280;
    font-size: 14px;
}

.booking-number {
    margin-top: 15px;
    font-size: 13px;
    color: #6b7280;
}

.booking-number strong {
    color: #111827;
}


/* Main Card */

.confirmation-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}


/* Package Header */

.package-header {
    padding: 25px;
    border-bottom: 1px solid #e5e7eb;
}

.package-header h2 {
    font-size: 23px;
    color: #111827;
    margin-bottom: 8px;
}

.destination {
    color: #2563eb;
    font-size: 14px;
    font-weight: bold;
}


/* Details */

.details-section {
    padding: 25px;
}

.section-title {
    font-size: 17px;
    font-weight: bold;
    color: #111827;
    margin-bottom: 18px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.detail-box {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
}

.detail-label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 6px;
}

.detail-value {
    font-size: 15px;
    font-weight: bold;
    color: #111827;
}


/* Payment Section */

.payment-section {
    border-top: 1px solid #e5e7eb;
    padding: 25px;
}

.payment-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.payment-box {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
}

.payment-label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 6px;
}

.payment-value {
    font-size: 14px;
    font-weight: bold;
    color: #111827;
}

.paid {
    color: #15803d;
}


/* Total */

.total-section {
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;

    padding: 25px;

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    font-size: 15px;
    font-weight: bold;
    color: #374151;
}

.total-amount {
    font-size: 27px;
    font-weight: bold;
    color: #111827;
}


/* Actions */

.actions {
    padding: 25px;

    display: flex;
    justify-content: center;
    gap: 12px;

    border-top: 1px solid #e5e7eb;
}

.action-button {
    display: inline-block;

    padding: 11px 20px;

    border-radius: 7px;

    text-decoration: none;

    font-size: 13px;
    font-weight: bold;

    cursor: pointer;
    border: none;
}

.primary-button {
    background: #2563eb;
    color: white;
}

.primary-button:hover {
    background: #1d4ed8;
}

.secondary-button {
    background: #f3f4f6;
    color: #374151;
}

.secondary-button:hover {
    background: #e5e7eb;
}


/* Note */

.confirmation-note {
    margin-top: 20px;

    text-align: center;

    color: #6b7280;

    font-size: 12px;
}


/* Error */

.error-box {
    max-width: 950px;
    margin: 30px auto;

    padding: 15px;

    background: #fee2e2;
    color: #991b1b;

    border: 1px solid #fecaca;

    border-radius: 8px;
}


/* Print */

@media print {

    body {
        background: white;
    }

    .customer-navbar,
    .actions,
    footer,
    .confirmation-note {
        display: none !important;
    }

    .confirmation-page {
        max-width: 100%;
        margin: 0;
    }

    .confirmation-header,
    .confirmation-card {
        border: 1px solid #ddd;
        box-shadow: none;
    }

}


/* Responsive */

@media (max-width: 650px) {

    .details-grid,
    .payment-grid {
        grid-template-columns: 1fr;
    }

    .total-section {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .actions {
        flex-direction: column;
    }

    .action-button {
        text-align: center;
    }

}

</style>


<div class="confirmation-page">


    <!-- Confirmation Header -->

    <div class="confirmation-header">

        <div class="success-icon">
            ✓
        </div>

        <h1>
            Booking Confirmed!
        </h1>

        <p>
            Your tour booking and payment have been successfully processed.
        </p>

        <div class="booking-number">

            Booking ID:
            <strong>
                #<?php echo $booking['booking_id']; ?>
            </strong>

        </div>

    </div>


    <!-- Booking Card -->

    <div class="confirmation-card">


        <!-- Package -->

        <div class="package-header">

            <h2>
                <?php echo htmlspecialchars($booking['package_name']); ?>
            </h2>

            <div class="destination">

                📍
                <?php echo htmlspecialchars($booking['destination_name']); ?>

                -
                <?php echo htmlspecialchars($booking['destination_location']); ?>

            </div>

        </div>


        <!-- Travel Details -->

        <div class="details-section">

            <div class="section-title">
                Travel Details
            </div>


            <div class="details-grid">


                <div class="detail-box">

                    <div class="detail-label">
                        Travel Date
                    </div>

                    <div class="detail-value">

                        <?php
                        echo date(
                            "d M Y",
                            strtotime($booking['travel_date'])
                        );
                        ?>

                    </div>

                </div>


                <div class="detail-box">

                    <div class="detail-label">
                        Number of Persons
                    </div>

                    <div class="detail-value">

                        <?php echo $booking['persons']; ?>

                    </div>

                </div>


                <div class="detail-box">

                    <div class="detail-label">
                        Package Duration
                    </div>

                    <div class="detail-value">

                        <?php echo $booking['duration']; ?> Days

                    </div>

                </div>


                <div class="detail-box">

                    <div class="detail-label">
                        Booking Status
                    </div>

                    <div class="detail-value paid">

                        <?php
                        echo ucfirst(
                            htmlspecialchars(
                                $booking['booking_status']
                            )
                        );
                        ?>

                    </div>

                </div>


            </div>

        </div>


        <!-- Payment Details -->

        <div class="payment-section">

            <div class="section-title">
                Payment Details
            </div>


            <div class="payment-grid">


                <div class="payment-box">

                    <div class="payment-label">
                        Payment Method
                    </div>

                    <div class="payment-value">

                        <?php

                        if (!empty($booking['payment_method'])) {

                            echo ucfirst(
                                htmlspecialchars(
                                    $booking['payment_method']
                                )
                            );

                        } else {

                            echo "Not Available";

                        }

                        ?>

                    </div>

                </div>


                <div class="payment-box">

                    <div class="payment-label">
                        Payment Status
                    </div>

                    <div class="payment-value paid">

                        <?php

                        if (!empty($booking['payment_status'])) {

                            echo ucfirst(
                                htmlspecialchars(
                                    $booking['payment_status']
                                )
                            );

                        } else {

                            echo "Pending";

                        }

                        ?>

                    </div>

                </div>


                <div class="payment-box">

                    <div class="payment-label">
                        Transaction ID
                    </div>

                    <div class="payment-value">

                        <?php

                        echo !empty($booking['transaction_id'])
                            ? htmlspecialchars($booking['transaction_id'])
                            : "Not Available";

                        ?>

                    </div>

                </div>


                <div class="payment-box">

                    <div class="payment-label">
                        Payment Date
                    </div>

                    <div class="payment-value">

                        <?php

                        if (!empty($booking['payment_date'])) {

                            echo date(
                                "d M Y, h:i A",
                                strtotime(
                                    $booking['payment_date']
                                )
                            );

                        } else {

                            echo "Not Available";

                        }

                        ?>

                    </div>

                </div>


            </div>

        </div>


        <!-- Total -->

        <div class="total-section">

            <div class="total-label">
                Total Amount Paid
            </div>

            <div class="total-amount">

                ₹<?php echo number_format(
                    $booking['total_amount'],
                    2
                ); ?>

            </div>

        </div>


        <!-- Actions -->

        <div class="actions">

            <button
                onclick="window.print()"
                class="action-button primary-button"
            >
                🖨 Print / Save PDF
            </button>


            <a
                href="bookings.php"
                class="action-button secondary-button"
            >
                My Bookings
            </a>


            <a
                href="packages.php"
                class="action-button secondary-button"
            >
                Explore More Packages
            </a>

        </div>


    </div>


    <div class="confirmation-note">

        Please keep your Booking ID and Transaction ID for future reference.

    </div>


</div>


<?php require_once "../includes/customer_footer.php"; ?>