<?php
session_start();

require_once "../config/database.php";

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

/* =========================
   DASHBOARD COUNTS
========================= */

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");
$total_customers = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) AS total FROM destinations WHERE status = 1");
$total_destinations = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) AS total FROM tour_packages WHERE status = 1");
$total_packages = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) AS total FROM bookings");
$total_bookings = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'confirmed'");
$confirmed_bookings = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) AS total FROM bookings WHERE status = 'pending'");
$pending_bookings = $result->fetch_assoc()['total'];

/* =========================
   TOTAL REVENUE
========================= */

$result = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE payment_status = 'paid'
");

$total_revenue = $result->fetch_assoc()['total'];

/* =========================
   RECENT BOOKINGS
========================= */

$recent_bookings = $conn->query("
    SELECT 
        bookings.id,
        users.name AS customer_name,
        tour_packages.package_name,
        bookings.travel_date,
        bookings.persons,
        bookings.total_amount,
        bookings.status
    FROM bookings
    INNER JOIN users 
        ON bookings.user_id = users.id
    INNER JOIN tour_packages 
        ON bookings.package_id = tour_packages.id
    ORDER BY bookings.id DESC
    LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Tour & Travel</title>

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

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 240px;
            height: 100vh;
            background: #111827;
            color: white;
            padding: 25px 15px;
        }

        .logo {
            font-size: 21px;
            font-weight: bold;
            padding: 0 15px 30px;
            border-bottom: 1px solid #374151;
            margin-bottom: 20px;
        }

        .logo span {
            display: block;
            font-size: 12px;
            font-weight: normal;
            color: #9ca3af;
            margin-top: 5px;
        }

        .menu-title {
            font-size: 11px;
            color: #9ca3af;
            text-transform: uppercase;
            padding: 0 15px;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .sidebar a {
            display: block;
            color: #d1d5db;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .sidebar a:hover {
            background: #374151;
            color: white;
        }

        .sidebar .active {
            background: #2563eb;
            color: white;
        }

        .logout {
            position: absolute;
            bottom: 25px;
            left: 15px;
            right: 15px;
        }

        .logout a {
            background: #dc2626;
            color: white;
            text-align: center;
        }

        .logout a:hover {
            background: #b91c1c;
        }

        /* =========================
           MAIN CONTENT
        ========================= */

        .main {
            margin-left: 240px;
            min-height: 100vh;
        }

        /* =========================
           TOP BAR
        ========================= */

        .topbar {
            background: white;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 35px;
            border-bottom: 1px solid #e5e7eb;
        }

        .topbar h2 {
            font-size: 20px;
        }

        .admin-info {
            font-size: 14px;
            color: #6b7280;
        }

        .admin-name {
            font-weight: bold;
            color: #111827;
        }

        /* =========================
           CONTENT
        ========================= */

        .content {
            padding: 30px 35px;
        }

        .welcome {
            margin-bottom: 30px;
        }

        .welcome h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .welcome p {
            color: #6b7280;
        }

        /* =========================
           STAT CARDS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 22px;
            border: 1px solid #e5e7eb;
        }

        .card-title {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .card-number {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .card-text {
            font-size: 12px;
            color: #9ca3af;
        }

        /* =========================
           QUICK ACTIONS
        ========================= */

        .section-title {
            font-size: 19px;
            margin-bottom: 15px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .action {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 18px;
            text-decoration: none;
            color: #111827;
            transition: 0.2s;
        }

        .action:hover {
            border-color: #2563eb;
            transform: translateY(-2px);
        }

        .action-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .action-description {
            font-size: 12px;
            color: #6b7280;
        }

        /* =========================
           RECENT BOOKINGS
        ========================= */

        .table-container {
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
            text-align: left;
            background: #f9fafb;
            padding: 14px;
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }

        td {
            padding: 14px;
            border-top: 1px solid #f0f0f0;
            font-size: 13px;
        }

        .status {
            padding: 5px 9px;
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

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 700px) {

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
            }

            .logout {
                position: relative;
                bottom: auto;
                left: auto;
                right: auto;
                margin-top: 20px;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .topbar {
                padding: 0 20px;
            }

            .content {
                padding: 20px;
            }

        }

    </style>

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<div class="sidebar">

    <div class="logo">
        Tour & Travel
        <span>Management System</span>
    </div>

    <div class="menu-title">
        Main Menu
    </div>

    <a href="dashboard.php" class="active">
        Dashboard
    </a>

    <a href="destinations/index.php">
        Destinations
    </a>

    <a href="packages/index.php">
        Tour Packages
    </a>

    <a href="bookings/index.php">
        Bookings
    </a>

    <a href="reports/index.php">
        Reports
    </a>

    <div class="menu-title" style="margin-top: 25px;">
        Account
    </div>

    <div class="logout">
        <a href="../auth/logout.php">
            Logout
        </a>
    </div>

</div>


<!-- =========================
     MAIN
========================= -->

<div class="main">

    <div class="topbar">

        <h2>Admin Dashboard</h2>

        <div class="admin-info">
            Welcome,
            <span class="admin-name">
                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </span>
        </div>

    </div>


    <div class="content">

        <!-- WELCOME -->

        <div class="welcome">

            <h1>Dashboard Overview</h1>

            <p>
                Manage your destinations, tour packages, bookings and reports from here.
            </p>

        </div>


        <!-- =========================
             STATISTICS
        ========================= -->

        <div class="stats">

            <div class="card">

                <div class="card-title">
                    Total Customers
                </div>

                <div class="card-number">
                    <?php echo $total_customers; ?>
                </div>

                <div class="card-text">
                    Registered customers
                </div>

            </div>


            <div class="card">

                <div class="card-title">
                    Destinations
                </div>

                <div class="card-number">
                    <?php echo $total_destinations; ?>
                </div>

                <div class="card-text">
                    Active destinations
                </div>

            </div>


            <div class="card">

                <div class="card-title">
                    Tour Packages
                </div>

                <div class="card-number">
                    <?php echo $total_packages; ?>
                </div>

                <div class="card-text">
                    Active packages
                </div>

            </div>


            <div class="card">

                <div class="card-title">
                    Total Bookings
                </div>

                <div class="card-number">
                    <?php echo $total_bookings; ?>
                </div>

                <div class="card-text">
                    All bookings
                </div>

            </div>


            <div class="card">

                <div class="card-title">
                    Confirmed Bookings
                </div>

                <div class="card-number">
                    <?php echo $confirmed_bookings; ?>
                </div>

                <div class="card-text">
                    Confirmed trips
                </div>

            </div>


            <div class="card">

                <div class="card-title">
                    Pending Bookings
                </div>

                <div class="card-number">
                    <?php echo $pending_bookings; ?>
                </div>

                <div class="card-text">
                    Awaiting confirmation
                </div>

            </div>


            <div class="card">

                <div class="card-title">
                    Revenue
                </div>

                <div class="card-number">
                    ₹<?php echo number_format($total_revenue, 2); ?>
                </div>

                <div class="card-text">
                    Total paid amount
                </div>

            </div>


            <div class="card">

                <div class="card-title">
                    System Status
                </div>

                <div class="card-number">
                    Active
                </div>

                <div class="card-text">
                    System is running
                </div>

            </div>

        </div>


        <!-- =========================
             QUICK ACTIONS
        ========================= -->

        <h2 class="section-title">
            Quick Actions
        </h2>

        <div class="quick-actions">

            <a href="destinations/add.php" class="action">

                <div class="action-title">
                    Add Destination
                </div>

                <div class="action-description">
                    Create a new travel destination
                </div>

            </a>


            <a href="packages/add.php" class="action">

                <div class="action-title">
                    Add Tour Package
                </div>

                <div class="action-description">
                    Create a new tour package
                </div>

            </a>


            <a href="bookings/index.php" class="action">

                <div class="action-title">
                    Manage Bookings
                </div>

                <div class="action-description">
                    View and update customer bookings
                </div>

            </a>


            <a href="reports/index.php" class="action">

                <div class="action-title">
                    View Reports
                </div>

                <div class="action-description">
                    Check system statistics
                </div>

            </a>

        </div>


        <!-- =========================
             RECENT BOOKINGS
        ========================= -->

        <h2 class="section-title">
            Recent Bookings
        </h2>

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Customer</th>
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
                                <?php echo htmlspecialchars($booking['customer_name']); ?>
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

                        <td colspan="7" style="text-align:center; padding:30px;">

                            No bookings found.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>

</html>