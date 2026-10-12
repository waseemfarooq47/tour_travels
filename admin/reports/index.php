<?php
session_start();

require_once "../../config/database.php";

require_once "../../includes/header.php";


// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}


// Get counts
$customers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");
$total_customers = $customers->fetch_assoc()['total'];

$destinations = $conn->query("SELECT COUNT(*) AS total FROM destinations");
$total_destinations = $destinations->fetch_assoc()['total'];

$packages = $conn->query("SELECT COUNT(*) AS total FROM tour_packages");
$total_packages = $packages->fetch_assoc()['total'];

$bookings = $conn->query("SELECT COUNT(*) AS total FROM bookings");
$total_bookings = $bookings->fetch_assoc()['total'];

$confirmed = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'confirmed'");
$confirmed_bookings = $confirmed->fetch_assoc()['total'];

$pending = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'pending'");
$pending_bookings = $pending->fetch_assoc()['total'];

$cancelled = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'cancelled'");
$cancelled_bookings = $cancelled->fetch_assoc()['total'];

$paid = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE payment_status = 'paid'
");

$total_paid = $paid->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Tour & Travel</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h1 {
            margin-bottom: 25px;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .card h3 {
            margin-top: 0;
            color: #555;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
        }

        .back {
            display: inline-block;
            margin-top: 30px;
            padding: 10px 18px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        @media (max-width: 800px) {
            .cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Tour & Travel Reports</h1>

    <div class="cards">

        <div class="card">
            <h3>Total Customers</h3>
            <div class="number">
                <?php echo $total_customers; ?>
            </div>
        </div>

        <div class="card">
            <h3>Total Destinations</h3>
            <div class="number">
                <?php echo $total_destinations; ?>
            </div>
        </div>

        <div class="card">
            <h3>Total Packages</h3>
            <div class="number">
                <?php echo $total_packages; ?>
            </div>
        </div>

        <div class="card">
            <h3>Total Bookings</h3>
            <div class="number">
                <?php echo $total_bookings; ?>
            </div>
        </div>

        <div class="card">
            <h3>Confirmed Bookings</h3>
            <div class="number">
                <?php echo $confirmed_bookings; ?>
            </div>
        </div>

        <div class="card">
            <h3>Pending Bookings</h3>
            <div class="number">
                <?php echo $pending_bookings; ?>
            </div>
        </div>

        <div class="card">
            <h3>Cancelled Bookings</h3>
            <div class="number">
                <?php echo $cancelled_bookings; ?>
            </div>
        </div>

        <div class="card">
            <h3>Total Paid Amount</h3>
            <div class="number">
                ₹<?php echo number_format($total_paid, 2); ?>
            </div>
        </div>

    </div>

    <a class="back" href="../dashboard.php">
        ← Back to Dashboard
    </a>

</div>

<?php
    require_once "../../includes/footer.php"
?>

</body>
</html>