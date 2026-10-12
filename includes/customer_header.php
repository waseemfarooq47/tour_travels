<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tour & Travel</title>

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

        /* NAVBAR */

        .customer-navbar {
            background: #111827;
            color: white;
            padding: 0 35px;
            height: 68px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .customer-logo {
            font-size: 21px;
            font-weight: bold;
        }

        .customer-logo span {
            display: block;
            font-size: 11px;
            font-weight: normal;
            color: #9ca3af;
            margin-top: 3px;
        }

        .customer-menu {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .customer-menu a {
            color: #d1d5db;
            text-decoration: none;
            padding: 10px 13px;
            border-radius: 6px;
            font-size: 14px;
        }

        .customer-menu a:hover {
            background: #374151;
            color: white;
        }

        .customer-menu .logout {
            background: #dc2626;
            color: white;
            margin-left: 8px;
        }

        .customer-menu .logout:hover {
            background: #b91c1c;
        }

        /* PAGE */

        .customer-container {
            max-width: 1200px;
            margin: auto;
            padding: 35px 20px;
        }

        /* MOBILE */

        @media (max-width: 750px) {

            .customer-navbar {
                height: auto;
                padding: 18px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .customer-menu {
                flex-wrap: wrap;
                justify-content: center;
            }

            .customer-menu a {
                font-size: 13px;
            }

        }

    </style>

</head>

<body>

<nav class="customer-navbar">

    <div class="customer-logo">

        Tour & Travel

        <span>Explore • Travel • Enjoy</span>

    </div>


    <div class="customer-menu">

        <a href="/tour-travel/customer/dashboard.php">
            Dashboard
        </a>

        <a href="/tour-travel/customer/packages.php">
            Tour Packages
        </a>

        <a href="/tour-travel/customer/bookings.php">
            My Bookings
        </a>

        <a class="logout" href="/tour-travel/auth/logout.php">
            Logout
        </a>

    </div>

</nav>

<div class="customer-container">