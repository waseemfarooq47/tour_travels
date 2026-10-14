<?php
session_start();

require_once "../../config/database.php";

// Check admin login before loading the page layout
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../../auth/login.php");
    exit();
}

// Helper function to safely retrieve a single count
function getReportCount($conn, $query) {
    $result = $conn->query($query);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row["total"] ?? 0);
}

// Get report statistics
$total_customers = getReportCount(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'"
);

$total_destinations = getReportCount(
    $conn,
    "SELECT COUNT(*) AS total FROM destinations"
);

$total_packages = getReportCount(
    $conn,
    "SELECT COUNT(*) AS total FROM tour_packages"
);

$total_bookings = getReportCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings"
);

$confirmed_bookings = getReportCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'confirmed'"
);

$pending_bookings = getReportCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'pending'"
);

$cancelled_bookings = getReportCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'cancelled'"
);

// Get total paid amount
$total_paid = 0;

$paidResult = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE payment_status = 'paid'
");

if ($paidResult) {
    $paidRow = $paidResult->fetch_assoc();
    $total_paid = (float) ($paidRow["total"] ?? 0);
}

// Calculate booking confirmation percentage
$confirmation_percentage = $total_bookings > 0
    ? round(($confirmed_bookings / $total_bookings) * 100)
    : 0;

require_once "../../includes/admin_header.php";
?>

<style>
.reports-page {
    min-height: 80vh;
    padding: 28px;
    background: #f5f7fb;
    color: #172033;
}

.reports-container {
    max-width: 1400px;
    margin: 0 auto;
}

.reports-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 28px;
}

.reports-heading h1 {
    margin: 0 0 8px;
    font-size: 29px;
    font-weight: 750;
    color: #172033;
}

.reports-heading p {
    margin: 0;
    color: #697386;
    font-size: 14px;
    line-height: 1.6;
}

.reports-date {
    padding: 11px 16px;
    background: #e8efff;
    color: #2458c6;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
}

.reports-section-title {
    font-size: 18px;
    font-weight: 750;
    margin: 28px 0 16px;
    color: #172033;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.report-card {
    position: relative;
    overflow: hidden;
    background: #fff;
    padding: 22px;
    border: 1px solid #e7eaf0;
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(20, 35, 70, 0.035);
    transition: transform .2s, box-shadow .2s;
}

.report-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(20, 35, 70, 0.07);
}

.report-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--accent, #2563eb);
}

.report-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 20px;
}

.report-card-label {
    color: #697386;
    font-size: 13px;
    font-weight: 650;
}

.report-icon {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 11px;
    background: var(--icon-bg, #eff6ff);
    color: var(--accent, #2563eb);
    font-size: 21px;
}

.report-number {
    color: #172033;
    font-size: 31px;
    font-weight: 800;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.report-description {
    color: #8a94a6;
    font-size: 12px;
    margin-top: 9px;
}

.report-blue {
    --accent: #2563eb;
    --icon-bg: #eff6ff;
}

.report-purple {
    --accent: #7c3aed;
    --icon-bg: #f3e8ff;
}

.report-cyan {
    --accent: #0891b2;
    --icon-bg: #ecfeff;
}

.report-orange {
    --accent: #d97706;
    --icon-bg: #fffbeb;
}

.report-green {
    --accent: #16a34a;
    --icon-bg: #f0fdf4;
}

.report-red {
    --accent: #dc2626;
    --icon-bg: #fef2f2;
}

.report-pink {
    --accent: #db2777;
    --icon-bg: #fdf2f8;
}

.report-revenue {
    --accent: #0f766e;
    --icon-bg: #f0fdfa;
}

.report-summary {
    margin-top: 25px;
    padding: 24px;
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(20, 35, 70, 0.035);
}

.report-summary h2 {
    margin: 0 0 20px;
    font-size: 18px;
    font-weight: 750;
}

.report-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 13px;
    color: #4b5563;
    font-size: 13px;
}

.report-summary-row strong {
    color: #172033;
    white-space: nowrap;
}

.report-progress {
    height: 9px;
    overflow: hidden;
    background: #edf0f5;
    border-radius: 20px;
    margin-bottom: 22px;
}

.report-progress-bar {
    height: 100%;
    width: <?php echo $confirmation_percentage; ?>%;
    max-width: 100%;
    background: #16a34a;
    border-radius: 20px;
    transition: width .3s;
}

.report-status-bars {
    display: grid;
    gap: 16px;
}

.report-status-line {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    font-size: 13px;
    margin-bottom: 7px;
}

.report-status-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4b5563;
}

.report-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
}

.dot-confirmed { background: #16a34a; }
.dot-pending { background: #f59e0b; }
.dot-cancelled { background: #dc2626; }

.report-status-value {
    font-weight: 750;
    color: #172033;
}

.report-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 28px;
}

.report-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    border-radius: 9px;
    background: #2563eb;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid #2563eb;
    transition: .2s;
}

.report-button:hover {
    background: #1d4ed8;
}

.report-button-secondary {
    background: #fff;
    color: #344054;
    border-color: #dce2eb;
}

.report-button-secondary:hover {
    background: #f8fafc;
}

@media (max-width: 1100px) {
    .reports-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .reports-page {
        padding: 18px 12px;
    }

    .reports-heading h1 {
        font-size: 24px;
    }

    .reports-grid {
        grid-template-columns: 1fr;
        gap: 13px;
    }

    .report-card {
        padding: 19px;
    }

    .report-number {
        font-size: 28px;
    }

    .report-summary {
        padding: 18px;
    }

    .report-actions {
        flex-direction: column;
    }

    .report-button {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

<div class="reports-page">
    <div class="reports-container">

        <div class="reports-heading">
            <div>
                <h1>Tour &amp; Travel Reports</h1>
                <p>
                    Overview of customers, destinations, packages,
                    bookings and collected payments.
                </p>
            </div>

            <div class="reports-date">
                Report Overview
            </div>
        </div>

        <h2 class="reports-section-title">Business Overview</h2>

        <div class="reports-grid">

            <div class="report-card report-blue">
                <div class="report-card-header">
                    <div class="report-card-label">Total Customers</div>
                    <div class="report-icon">♙</div>
                </div>
                <div class="report-number">
                    <?php echo number_format($total_customers); ?>
                </div>
                <div class="report-description">
                    Registered customer accounts
                </div>
            </div>

            <div class="report-card report-purple">
                <div class="report-card-header">
                    <div class="report-card-label">Destinations</div>
                    <div class="report-icon">⌖</div>
                </div>
                <div class="report-number">
                    <?php echo number_format($total_destinations); ?>
                </div>
                <div class="report-description">
                    Destinations listed in the system
                </div>
            </div>

            <div class="report-card report-cyan">
                <div class="report-card-header">
                    <div class="report-card-label">Tour Packages</div>
                    <div class="report-icon">▣</div>
                </div>
                <div class="report-number">
                    <?php echo number_format($total_packages); ?>
                </div>
                <div class="report-description">
                    Packages recorded in the system
                </div>
            </div>

            <div class="report-card report-orange">
                <div class="report-card-header">
                    <div class="report-card-label">Total Bookings</div>
                    <div class="report-icon">▤</div>
                </div>
                <div class="report-number">
                    <?php echo number_format($total_bookings); ?>
                </div>
                <div class="report-description">
                    All recorded reservations
                </div>
            </div>

        </div>

        <h2 class="reports-section-title">Booking Performance</h2>

        <div class="reports-grid">

            <div class="report-card report-green">
                <div class="report-card-header">
                    <div class="report-card-label">Confirmed Bookings</div>
                    <div class="report-icon">✓</div>
                </div>
                <div class="report-number">
                    <?php echo number_format($confirmed_bookings); ?>
                </div>
                <div class="report-description">
                    Reservations confirmed
                </div>
            </div>

            <div class="report-card report-orange">
                <div class="report-card-header">
                    <div class="report-card-label">Pending Bookings</div>
                    <div class="report-icon">◷</div>
                </div>
                <div class="report-number">
                    <?php echo number_format($pending_bookings); ?>
                </div>
                <div class="report-description">
                    Reservations awaiting action
                </div>
            </div>

            <div class="report-card report-red">
                <div class="report-card-header">
                    <div class="report-card-label">Cancelled Bookings</div>
                    <div class="report-icon">×</div>
                </div>
                <div class="report-number">
                    <?php echo number_format($cancelled_bookings); ?>
                </div>
                <div class="report-description">
                    Reservations cancelled
                </div>
            </div>

            <div class="report-card report-revenue">
                <div class="report-card-header">
                    <div class="report-card-label">Total Paid Amount</div>
                    <div class="report-icon">₹</div>
                </div>
                <div class="report-number">
                    ₹<?php echo number_format($total_paid, 2); ?>
                </div>
                <div class="report-description">
                    Payments marked as paid
                </div>
            </div>

        </div>

        <div class="report-summary">
            <h2>Booking Status Summary</h2>

            <div class="report-summary-row">
                <span>Confirmation rate</span>
                <strong><?php echo $confirmation_percentage; ?>%</strong>
            </div>

            <div class="report-progress">
                <div class="report-progress-bar"></div>
            </div>

            <div class="report-status-bars">

                <div>
                    <div class="report-status-line">
                        <span class="report-status-label">
                            <span class="report-dot dot-confirmed"></span>
                            Confirmed
                        </span>
                        <span class="report-status-value">
                            <?php echo number_format($confirmed_bookings); ?>
                        </span>
                    </div>
                    <div class="report-status-line">
                        <span class="report-status-label">
                            <span class="report-dot dot-pending"></span>
                            Pending
                        </span>
                        <span class="report-status-value">
                            <?php echo number_format($pending_bookings); ?>
                        </span>
                    </div>
                    <div class="report-status-line">
                        <span class="report-status-label">
                            <span class="report-dot dot-cancelled"></span>
                            Cancelled
                        </span>
                        <span class="report-status-value">
                            <?php echo number_format($cancelled_bookings); ?>
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="report-actions">
            <a class="report-button" href="../dashboard.php">
                ← Back to Dashboard
            </a>

            <button
                type="button"
                class="report-button report-button-secondary"
                onclick="window.print()"
            >
                Print Report
            </button>
        </div>

    </div>
</div>

<style>
@media print {
    .reports-page {
        background: #fff;
        padding: 0;
    }

    .report-actions,
    .admin-sidebar,
    .sidebar,
    nav {
        display: none !important;
    }

    .report-card,
    .report-summary {
        box-shadow: none;
        break-inside: avoid;
    }

    .reports-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>

<?php require_once "../../includes/admin_footer.php"; ?>
