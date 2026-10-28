<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication before sending any HTML
if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['user_role']) ||
    $_SESSION['user_role'] !== 'admin'
) {
    header('Location: /tour-travel/auth/login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_path = str_replace('\\', '/', $_SERVER['PHP_SELF']);

// Correct absolute URLs for the project
$base_url = '/tour-travel';

$logout_url = $base_url . '/auth/logout.php';

$titles = [
    'dashboard.php' => 'Dashboard',
    'index.php'     => 'Management',
    'add.php'       => 'Add New Record',
    'edit.php'      => 'Edit Record',
    'reports.php'   => 'Reports'
];

if (strpos($current_path, '/admin/destinations/') !== false) {
    $page_title = 'Destinations';
} elseif (strpos($current_path, '/admin/packages/') !== false) {
    $page_title = 'Tour Packages';
} elseif (strpos($current_path, '/admin/bookings/') !== false) {
    $page_title = 'Bookings';
} elseif (strpos($current_path, '/admin/reports/') !== false) {
    $page_title = 'Reports';
} else {
    $page_title = $titles[$current_page] ?? 'Admin Panel';
}

$user_name = $_SESSION['user_name'] ?? 'Administrator';
$user_initial = strtoupper(substr($user_name, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> | Tour &amp; Travel Admin</title>

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

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: 245px;
            background: #111827;
            color: white;
            padding: 22px 15px;
            overflow-y: auto;
            z-index: 100;
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
            color: #9ca3af;
            padding: 0 15px;
            margin: 20px 0 8px;
            letter-spacing: .7px;
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
            transition: background .2s, color .2s;
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

        .admin-main {
            margin-left: 245px;
            min-height: 100vh;
        }

        .admin-topbar {
            min-height: 65px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 30px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .page-title {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 37px;
            height: 37px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
        }

        .admin-user-name {
            font-size: 13px;
            font-weight: bold;
        }

        .admin-user-role {
            font-size: 11px;
            color: #6b7280;
            margin-top: 3px;
        }

        .admin-content {
            padding: 30px;
        }

        .admin-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
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

        .btn {
            display: inline-block;
            border: none;
            padding: 10px 15px;
            border-radius: 7px;
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

        .logout-link {
            color: #fecaca !important;
        }

        .logout-link:hover {
            background: #7f1d1d !important;
            color: white !important;
        }

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

            .admin-topbar {
                padding: 12px 20px;
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
                position: relative;
                padding: 15px;
            }

            .admin-content {
                padding: 15px;
            }
        }
    </style>
</head>

<body>

<aside class="admin-sidebar">
    <div class="admin-logo">
        <h2>Tour &amp; Travel</h2>
        <span>Administration Panel</span>
    </div>

    <div class="menu-title">Main Menu</div>

    <ul class="admin-menu">
        <li>
            <a href="<?= $base_url ?>/admin/dashboard.php"
               class="<?= $current_path === '/tour-travel/admin/dashboard.php' ? 'active' : '' ?>">
                <span class="menu-icon">▣</span>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="<?= $base_url ?>/admin/destinations/index.php"
               class="<?= strpos($current_path, '/admin/destinations/') !== false ? 'active' : '' ?>">
                <span class="menu-icon">📍</span>
                <span>Destinations</span>
            </a>
        </li>

        <li>
            <a href="<?= $base_url ?>/admin/packages/index.php"
               class="<?= strpos($current_path, '/admin/packages/') !== false ? 'active' : '' ?>">
                <span class="menu-icon">🧳</span>
                <span>Tour Packages</span>
            </a>
        </li>

        <li>
            <a href="<?= $base_url ?>/admin/bookings/index.php"
               class="<?= strpos($current_path, '/admin/bookings/') !== false ? 'active' : '' ?>">
                <span class="menu-icon">📋</span>
                <span>Bookings</span>
            </a>
        </li>

        <li>
            <a href="<?= $base_url ?>/admin/reports/index.php"
               class="<?= strpos($current_path, '/admin/reports/') !== false ? 'active' : '' ?>">
                <span class="menu-icon">📊</span>
                <span>Reports</span>
            </a>
        </li>
    </ul>

    <div class="menu-title">Account</div>

    <ul class="admin-menu">
        <li>
            <a href="<?= $base_url ?>/customer/dashboard.php">
                <span class="menu-icon">👥</span>
                <span>Customer View</span>
            </a>
        </li>

        <li>
            <a class="logout-link" href="<?= htmlspecialchars($logout_url) ?>">
                <span class="menu-icon">↪</span>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>

<main class="admin-main">
    <header class="admin-topbar">
        <div class="page-title">
            <?= htmlspecialchars($page_title) ?>
        </div>

        <div class="admin-user">
            <div class="admin-avatar">
                <?= htmlspecialchars($user_initial) ?>
            </div>

            <div>
                <div class="admin-user-name">
                    <?= htmlspecialchars($user_name) ?>
                </div>
                <div class="admin-user-role">Administrator</div>
            </div>
        </div>
    </header>

    <div class="admin-content">
