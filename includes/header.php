<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /tour-travel/auth/login.php");
    exit();
}

$current_path = $_SERVER['PHP_SELF'];

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tour & Travel Management System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
        }

        .navbar {
            background: #1f2937;
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 21px;
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 15px;
        }

        .nav-links a:hover {
            text-decoration: underline;
        }

        .logout {
            background: #dc3545;
            padding: 8px 13px;
            border-radius: 5px;
        }

        .logout:hover {
            text-decoration: none !important;
            background: #bb2d3b;
        }

        .page-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
    </style>
</head>

<body>

<nav class="navbar">

    <div class="logo">
        Tour & Travel
    </div>

    <div class="nav-links">

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>

            <a href="/tour-travel/admin/dashboard.php">Dashboard</a>
            <a href="/tour-travel/admin/destinations/index.php">Destinations</a>
            <a href="/tour-travel/admin/packages/index.php">Packages</a>
            <a href="/tour-travel/admin/bookings/index.php">Bookings</a>
            <a href="/tour-travel/admin/reports/index.php">Reports</a>

        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer'): ?>

            <a href="/tour-travel/customer/dashboard.php">Dashboard</a>
            <a href="/tour-travel/customer/packages.php">Packages</a>
            <a href="/tour-travel/customer/bookings.php">My Bookings</a>

        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>

            <a class="logout" href="/tour-travel/auth/logout.php">
                Logout
            </a>

        <?php endif; ?>

    </div>

</nav>

<div class="page-container">