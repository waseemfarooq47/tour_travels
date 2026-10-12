<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";

/* CUSTOMER ID */

$user_id = $_SESSION['user_id'];


/* TOTAL BOOKINGS */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$total_bookings = $result->fetch_assoc()['total'];


/* CONFIRMED BOOKINGS */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE user_id = ?
    AND status = 'confirmed'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$confirmed_bookings = $result->fetch_assoc()['total'];


/* PENDING BOOKINGS */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM bookings
    WHERE user_id = ?
    AND status = 'pending'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$pending_bookings = $result->fetch_assoc()['total'];


/* TOTAL SPENT */

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(total_amount), 0) AS total
    FROM bookings
    WHERE user_id = ?
    AND status = 'confirmed'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$total_spent = $result->fetch_assoc()['total'];


/* RECENT BOOKINGS */

$stmt = $conn->prepare("
    SELECT
        bookings.id,
        tour_packages.package_name,
        bookings.travel_date,
        bookings.persons,
        bookings.total_amount,
        bookings.status
    FROM bookings

    INNER JOIN tour_packages
        ON bookings.package_id = tour_packages.id

    WHERE bookings.user_id = ?

    ORDER BY bookings.id DESC

    LIMIT 5
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$recent_bookings = $stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Customer Dashboard | Tour & Travel</title>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: #f5f7fb;
    color: #1f2937;
}


/* MAIN */

.container {
    max-width: 1200px;
    margin: auto;
    padding: 35px 20px;
}


/* WELCOME */

.welcome {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 25px;
}

.welcome h1 {
    font-size: 28px;
    margin-bottom: 8px;
}

.welcome p {
    color: #6b7280;
    font-size: 14px;
}


/* STATS */

.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 22px;
}

.card-title {
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 10px;
}

.card-number {
    font-size: 27px;
    font-weight: bold;
}

.card-description {
    color: #9ca3af;
    font-size: 12px;
    margin-top: 5px;
}


/* QUICK ACTIONS */

.section-title {
    font-size: 19px;
    margin-bottom: 15px;
}

.actions {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 30px;
}

.action {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 22px;
    text-decoration: none;
    color: #111827;
}

.action:hover {
    border-color: #2563eb;
}

.action h3 {
    margin-bottom: 7px;
    font-size: 16px;
}

.action p {
    color: #6b7280;
    font-size: 13px;
}


/* TABLE */

.table-box {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f9fafb;
    padding: 14px;
    text-align: left;
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
}

td {
    padding: 14px;
    border-top: 1px solid #f0f0f0;
    font-size: 13px;
}


/* STATUS */

.status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 11px;
    text-transform: capitalize;
}

.confirmed {
    background: #dcfce7;
    color: #166534;
}

.pending {
    background: #fef3c7;
    color: #92400e;
}

.cancelled {
    background: #fee2e2;
    color: #991b1b;
}


/* MOBILE */

@media (max-width: 900px) {

    .stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .actions {
        grid-template-columns: 1fr;
    }

}


@media (max-width: 550px) {

    .stats {
        grid-template-columns: 1fr;
    }

    .welcome h1 {
        font-size: 23px;
    }

}

</style>

</head>

<body>


<!-- NAVIGATION -->

<?php require_once "../includes/customer_header.php"; ?>


<!-- DASHBOARD -->

<div class="container">


    <!-- WELCOME -->

    <div class="welcome">

        <h1>
            Welcome,
            <?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋
        </h1>

        <p>
            Plan your next journey and manage your bookings from your dashboard.
        </p>

    </div>


    <!-- STATISTICS -->

    <div class="stats">


        <div class="card">

            <div class="card-title">
                Total Bookings
            </div>

            <div class="card-number">
                <?php echo $total_bookings; ?>
            </div>

            <div class="card-description">
                All your bookings
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Confirmed Trips
            </div>

            <div class="card-number">
                <?php echo $confirmed_bookings; ?>
            </div>

            <div class="card-description">
                Confirmed bookings
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Pending Bookings
            </div>

            <div class="card-number">
                <?php echo $pending_bookings; ?>
            </div>

            <div class="card-description">
                Awaiting confirmation
            </div>

        </div>


        <div class="card">

            <div class="card-title">
                Total Spent
            </div>

            <div class="card-number">
                ₹<?php echo number_format($total_spent, 2); ?>
            </div>

            <div class="card-description">
                Confirmed bookings
            </div>

        </div>


    </div>


    <!-- QUICK ACTIONS -->

    <h2 class="section-title">
        Quick Actions
    </h2>


    <div class="actions">


        <a href="packages.php" class="action">

            <h3>
                Explore Tour Packages
            </h3>

            <p>
                Browse available destinations and tour packages.
            </p>

        </a>


        <a href="bookings.php" class="action">

            <h3>
                My Bookings
            </h3>

            <p>
                View your booking history and travel details.
            </p>

        </a>


        <a href="packages.php" class="action">

            <h3>
                Plan Your Next Trip
            </h3>

            <p>
                Choose a package and book your next journey.
            </p>

        </a>


    </div>


    <!-- RECENT BOOKINGS -->

    <h2 class="section-title">
        Recent Bookings
    </h2>


    <div class="table-box">

        <table>

            <thead>

                <tr>

                    <th>Booking ID</th>

                    <th>Package</th>

                    <th>Travel Date</th>

                    <th>Persons</th>

                    <th>Amount</th>

                    <th>Status</th>

                </tr>

            </thead>


            <tbody>


            <?php if ($recent_bookings->num_rows > 0): ?>


                <?php while ($booking = $recent_bookings->fetch_assoc()): ?>


                    <tr>

                        <td>
                            #<?php echo $booking['id']; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking['package_name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($booking['travel_date']); ?>
                        </td>

                        <td>
                            <?php echo $booking['persons']; ?>
                        </td>

                        <td>
                            ₹<?php echo number_format($booking['total_amount'], 2); ?>
                        </td>

                        <td>

                            <span class="status <?php echo $booking['status']; ?>">

                                <?php echo htmlspecialchars($booking['status']); ?>

                            </span>

                        </td>

                    </tr>


                <?php endwhile; ?>


            <?php else: ?>


                <tr>

                    <td colspan="6" style="text-align:center; padding:30px;">

                        You don't have any bookings yet.

                    </td>

                </tr>


            <?php endif; ?>


            </tbody>

        </table>

    </div>


</div>


<!-- FOOTER -->

<?php require_once "../includes/customer_footer.php"; ?>


</body>

</html>