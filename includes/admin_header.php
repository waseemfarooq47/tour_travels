<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Admin Panel | Tour & Travel
    </title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
            color: #111827;
        }


        /* ==============================
           Sidebar
           ============================== */

        .admin-sidebar {

            position: fixed;

            left: 0;
            top: 0;
            bottom: 0;

            width: 245px;

            background: #111827;

            color: white;

            padding: 22px 15px;

            overflow-y: auto;
        }


        .admin-logo {

            padding: 8px 15px 25px;

            border-bottom: 1px solid #374151;

            margin-bottom: 18px;
        }


        .admin-logo h2 {

            font-size: 20px;

            margin-bottom: 5px;
        }


        .admin-logo span {

            font-size: 11px;

            color: #9ca3af;
        }


        .menu-title {

            font-size: 10px;

            text-transform: uppercase;

            color: #6b7280;

            padding: 0 15px;

            margin: 20px 0 8px;

            letter-spacing: 0.7px;
        }


        .admin-menu {

            list-style: none;
        }


        .admin-menu li {

            margin-bottom: 4px;
        }


        .admin-menu a {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 11px 14px;

            color: #d1d5db;

            text-decoration: none;

            border-radius: 7px;

            font-size: 13px;

            transition: 0.2s;
        }


        .admin-menu a:hover {

            background: #1f2937;

            color: white;
        }


        .admin-menu a.active {

            background: #2563eb;

            color: white;
        }


        .menu-icon {

            width: 20px;

            text-align: center;

            font-size: 15px;
        }


        /* ==============================
           Main Area
           ============================== */

        .admin-main {

            margin-left: 245px;

            min-height: 100vh;
        }


        /* ==============================
           Topbar
           ============================== */

        .admin-topbar {

            height: 65px;

            background: white;

            border-bottom: 1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 30px;

            position: sticky;

            top: 0;

            z-index: 10;
        }


        .page-title {

            font-size: 17px;

            font-weight: bold;

            color: #111827;
        }


        .admin-user {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .admin-avatar {

            width: 35px;
            height: 35px;

            border-radius: 50%;

            background: #2563eb;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 13px;

            font-weight: bold;
        }


        .admin-user-info {

            line-height: 1.3;
        }


        .admin-user-name {

            font-size: 13px;

            font-weight: bold;
        }


        .admin-user-role {

            font-size: 10px;

            color: #6b7280;
        }


        /* ==============================
           Page Content
           ============================== */

        .admin-content {

            padding: 30px;
        }


        /* ==============================
           Common Cards
           ============================== */

        .admin-card {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 10px;

            padding: 22px;

            margin-bottom: 20px;
        }


        .admin-card-title {

            font-size: 17px;

            font-weight: bold;

            margin-bottom: 6px;
        }


        .admin-card-subtitle {

            color: #6b7280;

            font-size: 12px;

            margin-bottom: 20px;
        }


        /* ==============================
           Buttons
           ============================== */

        .btn {

            display: inline-block;

            border: none;

            padding: 9px 15px;

            border-radius: 6px;

            text-decoration: none;

            font-size: 12px;

            font-weight: bold;

            cursor: pointer;
        }


        .btn-primary {

            background: #2563eb;

            color: white;
        }


        .btn-primary:hover {

            background: #1d4ed8;
        }


        .btn-secondary {

            background: #f3f4f6;

            color: #374151;
        }


        .btn-danger {

            background: #dc2626;

            color: white;
        }


        /* ==============================
           Mobile
           ============================== */

        @media (max-width: 800px) {

            .admin-sidebar {

                width: 210px;
            }

            .admin-main {

                margin-left: 210px;
            }

            .admin-content {

                padding: 20px;
            }

        }


        @media (max-width: 600px) {

            .admin-sidebar {

                position: relative;

                width: 100%;

                height: auto;
            }

            .admin-main {

                margin-left: 0;
            }

            .admin-topbar {

                padding: 0 15px;
            }

            .admin-content {

                padding: 15px;
            }

        }

    </style>

</head>


<body>


<!-- ==============================
     Sidebar
     ============================== -->

<aside class="admin-sidebar">


    <div class="admin-logo">

        <h2>
            Tour & Travel
        </h2>

        <span>
            Administration Panel
        </span>

    </div>


    <div class="menu-title">
        Main
    </div>


<ul class="admin-menu">

    <!-- Dashboard -->
    <li>
        <a
            href="/tour-travel/admin/dashboard.php"
            class="<?php
                echo (
                    basename($current_path) === 'dashboard.php'
                    && strpos($current_path, '/admin/') !== false
                )
                ? 'active'
                : '';
            ?>"
        >
            <span class="menu-icon">▣</span>
            <span>Dashboard</span>
        </a>
    </li>


    <!-- Destinations -->
    <li>
        <a
            href="/tour-travel/admin/destinations/index.php"
            class="<?php
                echo strpos(
                    $current_path,
                    '/admin/destinations/'
                ) !== false
                ? 'active'
                : '';
            ?>"
        >
            <span class="menu-icon">📍</span>
            <span>Destinations</span>
        </a>
    </li>


    <!-- Tour Packages -->
    <li>
        <a
            href="/tour-travel/admin/packages/index.php"
            class="<?php
                echo strpos(
                    $current_path,
                    '/admin/packages/'
                ) !== false
                ? 'active'
                : '';
            ?>"
        >
            <span class="menu-icon">🧳</span>
            <span>Tour Packages</span>
        </a>
    </li>


    <!-- Bookings -->
    <li>
        <a
            href="/tour-travel/admin/bookings/index.php"
            class="<?php
                echo strpos(
                    $current_path,
                    '/admin/bookings/'
                ) !== false
                ? 'active'
                : '';
            ?>"
        >
            <span class="menu-icon">📋</span>
            <span>Bookings</span>
        </a>
    </li>


    <!-- Reports -->
    <li>
        <a
            href="/tour-travel/admin/reports/index.php"
            class="<?php
                echo strpos(
                    $current_path,
                    '/admin/reports/'
                ) !== false
                ? 'active'
                : '';
            ?>"
        >
            <span class="menu-icon">📊</span>
            <span>Reports</span>
        </a>
    </li>


    <!-- Customers -->
    <li>
        <a
            href="/tour-travel/customer/dashboard.php"
            class=""
        >
            <span class="menu-icon">👥</span>
            <span>Customers</span>
        </a>
    </li>

</ul>


    <div class="menu-title">
        Account
    </div>


    <ul class="admin-menu">


        <li>

            <a href="../auth/logout.php">

                <span class="menu-icon">↪</span>

                Logout

            </a>

        </li>


    </ul>


</aside>


<!-- ==============================
     Main
     ============================== -->

<main class="admin-main">


    <header class="admin-topbar">


        <div class="page-title">

            <?php

            $titles = [

                'dashboard.php' => 'Dashboard',

                'destinations.php' => 'Destinations',

                'packages.php' => 'Tour Packages',

                'customers.php' => 'Customers',

                'bookings.php' => 'Bookings',

                'payments.php' => 'Payments',

                'reports.php' => 'Reports'

            ];

            echo $titles[$current_page] ?? 'Admin Panel';

            ?>

        </div>


        <div class="admin-user">


            <div class="admin-avatar">

                <?php

                echo strtoupper(
                    substr(
                        $_SESSION['user_name'] ?? 'A',
                        0,
                        1
                    )
                );

                ?>

            </div>


            <div class="admin-user-info">

                <div class="admin-user-name">

                    <?php
                    echo htmlspecialchars(
                        $_SESSION['user_name'] ?? 'Administrator'
                    );
                    ?>

                </div>

                <div class="admin-user-role">
                    Administrator
                </div>

            </div>


        </div>


    </header>


    <div class="admin-content">