<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Customer authentication
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'customer'
) {
    header("Location: ../auth/login.php");
    exit();
}

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$customerName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Customer';
$customerInitial = strtoupper(substr(trim($customerName), 0, 1));

function customerNavActive($page, $currentPage) {
    return $page === $currentPage ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tour & Travel | Customer Portal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy: #101a2e;
            --navy-light: #1a2944;
            --blue: #2563eb;
            --blue-light: #eff6ff;
            --text: #172033;
            --muted: #64748b;
            --border: #e5eaf2;
            --white: #ffffff;
            --danger: #dc2626;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', Arial, Helvetica, sans-serif;
            background: #f4f7fc;
            color: var(--text);
            line-height: 1.6;
        }

        a {
            text-decoration: none;
        }

        /* CUSTOMER NAVBAR */

        .customer-navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            width: 100%;
            min-height: 78px;
            padding: 12px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
            background: var(--navy);
            color: var(--white);
            border-bottom: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 4px 18px rgba(15, 23, 42, .12);
        }

        /* BRAND */

        .customer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .customer-brand-icon {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            border-radius: 13px;
            color: white;
            font-size: 23px;
            box-shadow: 0 5px 15px rgba(37, 99, 235, .3);
        }

        .customer-logo {
            color: white;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -.6px;
            line-height: 1.3;
        }

        .customer-logo span {
            display: block;
            margin-top: 3px;
            color: #a9b8d0;
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        /* NAVIGATION */

        .customer-nav-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
        }

        .customer-menu {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .customer-menu a {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 14px;
            border-radius: 9px;
            color: #d6deeb;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            transition: background .2s ease, color .2s ease;
        }

        .customer-menu a:hover {
            background: var(--navy-light);
            color: white;
        }

        .customer-menu a.active {
            background: rgba(59, 130, 246, .16);
            color: #93c5fd;
        }

        .customer-menu a.active::after {
            content: "";
            position: absolute;
            bottom: 3px;
            left: 14px;
            right: 14px;
            height: 2px;
            background: #60a5fa;
            border-radius: 5px;
        }

        .nav-icon {
            font-size: 15px;
            line-height: 1;
        }

        /* CUSTOMER PROFILE */

        .customer-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 18px;
            border-left: 1px solid rgba(255,255,255,.15);
        }

        .customer-avatar {
            width: 37px;
            height: 37px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border-radius: 50%;
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 800;
            font-size: 15px;
        }

        .customer-profile-info {
            min-width: 0;
        }

        .customer-profile-name {
            max-width: 130px;
            overflow: hidden;
            color: white;
            font-size: 12px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .customer-profile-label {
            color: #9eacc2;
            font-size: 10px;
        }

        /* LOGOUT */

        .customer-menu a.logout {
            margin-left: 5px;
            padding: 10px 15px;
            border: 1px solid rgba(248, 113, 113, .25);
            background: rgba(220, 38, 38, .13);
            color: #fca5a5;
        }

        .customer-menu a.logout:hover {
            background: var(--danger);
            color: white;
        }

        .customer-menu a.logout::after {
            display: none;
        }

        /* MOBILE MENU BUTTON */

        .customer-mobile-toggle {
            display: none;
            width: 42px;
            height: 42px;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 10px;
            background: var(--navy-light);
            color: white;
            font-size: 23px;
            cursor: pointer;
        }

        /* PAGE CONTENT WRAPPER */

        .customer-container {
            width: min(1200px, 100%);
            margin: 0 auto;
            padding: 35px 22px;
            min-height: calc(100vh - 150px);
        }

        /* RESPONSIVE */

        @media (max-width: 1100px) {
            .customer-navbar {
                padding-left: 3%;
                padding-right: 3%;
                gap: 12px;
            }

            .customer-nav-right {
                gap: 10px;
            }

            .customer-menu a {
                padding: 10px 9px;
                font-size: 12px;
            }

            .customer-profile {
                padding-left: 10px;
            }
        }

        @media (max-width: 850px) {
            .customer-navbar {
                min-height: 72px;
                flex-wrap: wrap;
                padding: 12px 20px;
            }

            .customer-mobile-toggle {
                display: flex;
                margin-left: auto;
            }

            .customer-nav-right {
                display: none;
                flex-basis: 100%;
                width: 100%;
                align-items: stretch;
                flex-direction: column;
                gap: 14px;
                padding: 12px 0 8px;
            }

            .customer-nav-right.menu-open {
                display: flex;
            }

            .customer-menu {
                align-items: stretch;
                flex-direction: column;
                gap: 5px;
                width: 100%;
            }

            .customer-menu a {
                width: 100%;
                padding: 12px 14px;
                font-size: 14px;
            }

            .customer-menu a.active::after {
                top: 10px;
                bottom: 10px;
                left: 4px;
                right: auto;
                width: 3px;
                height: auto;
            }

            .customer-profile {
                padding: 12px 5px 0;
                border-left: 0;
                border-top: 1px solid rgba(255,255,255,.15);
            }

            .customer-profile-name {
                max-width: 220px;
            }

            .customer-container {
                padding: 25px 16px;
            }
        }

        @media (max-width: 420px) {
            .customer-navbar {
                padding: 12px 14px;
            }

            .customer-brand-icon {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }

            .customer-logo {
                font-size: 18px;
            }

            .customer-container {
                padding: 20px 12px;
            }
        }
    </style>
</head>

<body>

<nav class="customer-navbar">

    <a href="/tour-travel/customer/dashboard.php"
       class="customer-brand"
       aria-label="Tour and Travel home">

        <div class="customer-brand-icon" aria-hidden="true">✈</div>

        <div class="customer-logo">
            Tour &amp; Travel
            <span>Explore · Travel · Enjoy</span>
        </div>

    </a>

    <button
        type="button"
        class="customer-mobile-toggle"
        id="customerMobileToggle"
        aria-label="Open navigation menu"
        aria-expanded="false"
        aria-controls="customerNavigation">
        ☰
    </button>

    <div class="customer-nav-right" id="customerNavigation">

        <div class="customer-menu">

            <a href="/tour-travel/customer/dashboard.php"
               class="<?= customerNavActive('dashboard.php', $currentPage) ?>">
                <span class="nav-icon">⌂</span>
                Dashboard
            </a>

            <a href="/tour-travel/customer/packages.php"
               class="<?= customerNavActive('packages.php', $currentPage) ?>">
                <span class="nav-icon">✈</span>
                Tour Packages
            </a>

            <a href="/tour-travel/customer/bookings.php"
               class="<?= customerNavActive('bookings.php', $currentPage) ?>">
                <span class="nav-icon">▣</span>
                My Bookings
            </a>

            <a href="/tour-travel/auth/logout.php" class="logout">
                <span class="nav-icon">↪</span>
                Logout
            </a>

        </div>

        <div class="customer-profile">
            <div class="customer-avatar">
                <?= htmlspecialchars($customerInitial, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <div class="customer-profile-info">
                <div class="customer-profile-name">
                    <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="customer-profile-label">Customer Account</div>
            </div>
        </div>

    </div>

</nav>

<div class="customer-container">

<script>
(function () {
    const toggle = document.getElementById('customerMobileToggle');
    const navigation = document.getElementById('customerNavigation');

    if (!toggle || !navigation) return;

    toggle.addEventListener('click', function () {
        const isOpen = navigation.classList.toggle('menu-open');

        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute(
            'aria-label',
            isOpen ? 'Close navigation menu' : 'Open navigation menu'
        );
        toggle.textContent = isOpen ? '×' : '☰';
    });
})();
</script>
