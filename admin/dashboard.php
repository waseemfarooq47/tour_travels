
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";

// Authenticate admin before sending any HTML
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../auth/login.php");
    exit();
}

// Helper for dashboard counts
function getCount($conn, $query)
{
    $result = $conn->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        return (int) $row["total"];
    }

    return 0;
}

// Dashboard statistics
$total_customers = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'"
);

$total_destinations = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM destinations"
);

$total_packages = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM tour_packages"
);

$total_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings"
);

$confirmed_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'confirmed'"
);

$pending_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'pending'"
);

$cancelled_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM bookings WHERE status = 'cancelled'"
);

// Revenue from completed payments
$total_revenue = 0;

$revenue_result = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE payment_status = 'paid'
");

if ($revenue_result) {
    $revenue_row = $revenue_result->fetch_assoc();
    $total_revenue = (float) ($revenue_row["total"] ?? 0);
}

// Recent bookings with customer and package information
$recent_bookings = $conn->query("
    SELECT
        b.id,
        b.user_id,
        b.package_id,
        b.travel_date,
        b.status,
        b.total_amount,
        b.created_at,
        u.name AS customer_name,
        tp.package_name
    FROM bookings b
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN tour_packages tp ON b.package_id = tp.id
    ORDER BY b.id DESC
    LIMIT 8
");

// Load shared admin layout only after authentication and queries
require_once "../includes/admin_header.php";
?>

<style>
.dashboard-page {
    color: #172033;
    max-width: 1500px;
    margin: 0 auto;
}

.dashboard-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 26px;
}

.dashboard-heading h1 {
    font-size: 28px;
    margin: 0 0 7px;
    color: #111827;
}

.dashboard-heading p {
    color: #64748b;
    font-size: 14px;
    margin: 0;
}

.quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 11px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}

.action-primary {
    background: #2563eb;
    color: white;
}

.action-primary:hover {
    background: #1d4ed8;
}

.action-secondary {
    background: white;
    color: #334155;
    border: 1px solid #dbe2ea;
}

.action-secondary:hover {
    background: #f8fafc;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 22px;
}

.stat-card {
    background: white;
    border: 1px solid #e7ebf2;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 3px 12px rgba(15, 23, 42, 0.025);
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.07);
}

.stat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 20px;
}

.stat-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.stat-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
}

.icon-blue { background: #eff6ff; }
.icon-purple { background: #f3e8ff; }
.icon-orange { background: #fff7ed; }
.icon-green { background: #ecfdf5; }

.stat-value {
    font-size: 29px;
    line-height: 1.2;
    font-weight: 750;
    color: #111827;
    overflow-wrap: anywhere;
}

.stat-foot {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 8px;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}

.summary-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 20px;
    background: white;
    border: 1px solid #e7ebf2;
    border-radius: 12px;
}

.summary-label {
    color: #64748b;
    font-size: 13px;
    margin-bottom: 7px;
}

.summary-value {
    color: #111827;
    font-size: 23px;
    font-weight: 750;
}

.summary-icon {
    font-size: 23px;
}

.dashboard-content {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(260px, 0.8fr);
    gap: 22px;
    align-items: start;
}

.panel {
    background: white;
    border: 1px solid #e7ebf2;
    border-radius: 14px;
    overflow: hidden;
    min-width: 0;
}

.panel-header {
    padding: 20px 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #edf0f5;
}

.panel-header h2 {
    margin: 0;
    color: #111827;
    font-size: 17px;
}

.panel-header p {
    margin: 5px 0 0;
    color: #94a3b8;
    font-size: 12px;
}

.view-all {
    color: #2563eb;
    text-decoration: none;
    font-weight: 600;
    font-size: 12px;
    white-space: nowrap;
}

.view-all:hover {
    text-decoration: underline;
}

.table-scroll {
    overflow-x: auto;
}

.booking-table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

.booking-table th {
    background: #f8fafc;
    color: #64748b;
    text-align: left;
    padding: 13px 16px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.35px;
}

.booking-table td {
    padding: 15px 16px;
    font-size: 12px;
    color: #475569;
    border-bottom: 1px solid #f1f5f9;
}

.booking-table tr:last-child td {
    border-bottom: none;
}

.booking-table tbody tr:hover {
    background: #fafcff;
}

.booking-number {
    color: #2563eb;
    font-weight: 700;
}

.customer-name {
    color: #1e293b;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: capitalize;
}

.status-confirmed {
    color: #166534;
    background: #dcfce7;
}

.status-pending {
    color: #92400e;
    background: #fef3c7;
}

.status-cancelled {
    color: #991b1b;
    background: #fee2e2;
}

.status-other {
    color: #475569;
    background: #f1f5f9;
}

.empty-state {
    padding: 38px 20px;
    text-align: center;
    color: #64748b;
    font-size: 13px;
}

.summary-list {
    padding: 8px 22px 18px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 17px 0;
    border-bottom: 1px solid #f1f5f9;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-name {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #475569;
    font-size: 13px;
}

.summary-dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dot-green { background: #22c55e; }
.dot-orange { background: #f59e0b; }
.dot-red { background: #ef4444; }
.dot-blue { background: #3b82f6; }

.summary-count {
    font-size: 14px;
    font-weight: 750;
    color: #172033;
}

.revenue-panel {
    padding: 20px 22px;
    background: linear-gradient(135deg, #eff6ff, #f8faff);
    border-top: 1px solid #e7ebf2;
}

.revenue-panel span {
    display: block;
    color: #64748b;
    font-size: 12px;
    margin-bottom: 8px;
}

.revenue-panel strong {
    font-size: 24px;
    color: #1d4ed8;
    overflow-wrap: anywhere;
}

@media (max-width: 1100px) {
    .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 650px) {
    .dashboard-heading h1 {
        font-size: 24px;
    }

    .summary-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .stats-grid {
        gap: 12px;
    }

    .stat-card {
        padding: 16px;
    }

    .stat-value {
        font-size: 24px;
    }

    .stat-icon {
        width: 39px;
        height: 39px;
        font-size: 18px;
    }

    .panel-header {
        padding: 17px;
    }

    .summary-list {
        padding-left: 17px;
        padding-right: 17px;
    }
}

@media (max-width: 420px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .quick-actions {
        width: 100%;
    }

    .action-button {
        flex: 1;
    }
}
</style>

<div class="dashboard-page">

    <div class="dashboard-heading">
        <div>
            <h1>Admin Dashboard</h1>
            <p>Monitor customers, destinations, tour packages and bookings.</p>
        </div>

        <div class="quick-actions">
            <a class="action-button action-primary"
               href="destinations/add.php">
                + Add Destination
            </a>

            <a class="action-button action-secondary"
               href="packages/index.php">
                Manage Packages
            </a>
        </div>
    </div>

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Customers</span>
                <span class="stat-icon icon-blue">👥</span>
            </div>
            <div class="stat-value">
                <?= number_format($total_customers) ?>
            </div>
            <div class="stat-foot">Registered customer accounts</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Destinations</span>
                <span class="stat-icon icon-purple">📍</span>
            </div>
            <div class="stat-value">
                <?= number_format($total_destinations) ?>
            </div>
            <div class="stat-foot">Destinations in the system</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Tour Packages</span>
                <span class="stat-icon icon-orange">🧳</span>
            </div>
            <div class="stat-value">
                <?= number_format($total_packages) ?>
            </div>
            <div class="stat-foot">Available package records</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <span class="stat-label">Total Bookings</span>
                <span class="stat-icon icon-green">📅</span>
            </div>
            <div class="stat-value">
                <?= number_format($total_bookings) ?>
            </div>
            <div class="stat-foot">All booking requests</div>
        </div>

    </div>

    <div class="summary-grid">

        <div class="summary-card">
            <div>
                <div class="summary-label">Confirmed Bookings</div>
                <div class="summary-value">
                    <?= number_format($confirmed_bookings) ?>
                </div>
            </div>
            <span class="summary-icon">✅</span>
        </div>

        <div class="summary-card">
            <div>
                <div class="summary-label">Pending Bookings</div>
                <div class="summary-value">
                    <?= number_format($pending_bookings) ?>
                </div>
            </div>
            <span class="summary-icon">⏳</span>
        </div>

        <div class="summary-card">
            <div>
                <div class="summary-label">Cancelled Bookings</div>
                <div class="summary-value">
                    <?= number_format($cancelled_bookings) ?>
                </div>
            </div>
            <span class="summary-icon">❌</span>
        </div>

    </div>

    <div class="dashboard-content">

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Recent Bookings</h2>
                    <p>The latest booking activity</p>
                </div>

                <a class="view-all" href="bookings/index.php">
                    View all →
                </a>
            </div>

            <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                <div class="table-scroll">
                    <table class="booking-table">
                        <thead>
                            <tr>
                                <th>Booking</th>
                                <th>Customer</th>
                                <th>Package</th>
                                <th>Travel Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                                <?php
                                $status = strtolower(trim($booking["status"] ?? "pending"));
                                $status_class = in_array(
                                    $status,
                                    ["confirmed", "pending", "cancelled"],
                                    true
                                ) ? "status-" . $status : "status-other";

                                $travel_date = $booking["travel_date"] ?? "";
                                ?>
                                <tr>
                                    <td class="booking-number">
                                        #<?= (int) $booking["id"] ?>
                                    </td>

                                    <td class="customer-name">
                                        <?= htmlspecialchars($booking["customer_name"] ?? "Customer") ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($booking["package_name"] ?? "Tour package unavailable") ?>
                                    </td>

                                    <td>
                                        <?php
                                        if (!empty($travel_date) && strtotime($travel_date)) {
                                            echo htmlspecialchars(date("d M Y", strtotime($travel_date)));
                                        } else {
                                            echo "—";
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <span class="status-badge <?= htmlspecialchars($status_class) ?>">
                                            <?= htmlspecialchars(ucfirst($status)) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    No bookings have been recorded yet.
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Booking Summary</h2>
                    <p>Current booking status overview</p>
                </div>
            </div>

            <div class="summary-list">

                <div class="summary-row">
                    <div class="summary-name">
                        <span class="summary-dot dot-green"></span>
                        Confirmed
                    </div>
                    <span class="summary-count">
                        <?= number_format($confirmed_bookings) ?>
                    </span>
                </div>

                <div class="summary-row">
                    <div class="summary-name">
                        <span class="summary-dot dot-orange"></span>
                        Pending
                    </div>
                    <span class="summary-count">
                        <?= number_format($pending_bookings) ?>
                    </span>
                </div>

                <div class="summary-row">
                    <div class="summary-name">
                        <span class="summary-dot dot-red"></span>
                        Cancelled
                    </div>
                    <span class="summary-count">
                        <?= number_format($cancelled_bookings) ?>
                    </span>
                </div>

                <div class="summary-row">
                    <div class="summary-name">
                        <span class="summary-dot dot-blue"></span>
                        Total Bookings
                    </div>
                    <span class="summary-count">
                        <?= number_format($total_bookings) ?>
                    </span>
                </div>

            </div>

            <div class="revenue-panel">
                <span>Total Revenue from Paid Payments</span>
                <strong>₹<?= number_format($total_revenue, 2) ?></strong>
            </div>
        </section>

    </div>

</div>

<?php require_once "../includes/admin_footer.php"; ?>
