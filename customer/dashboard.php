
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "customer"
) {
    header("Location: /tour-travel/auth/login.php");
    exit();
}

require_once "../config/database.php";

$user_id = (int) $_SESSION["user_id"];
$user_name = $_SESSION["user_name"] ?? "Traveller";

// Get customer booking statistics.
$stats_query = "
    SELECT
        COUNT(*) AS total_bookings,
        COALESCE(SUM(status = 'confirmed'), 0) AS confirmed_bookings,
        COALESCE(SUM(status = 'pending'), 0) AS pending_bookings,
        COALESCE(SUM(status = 'cancelled'), 0) AS cancelled_bookings,
        COALESCE(
            SUM(
                CASE
                    WHEN status = 'confirmed' THEN total_amount
                    ELSE 0
                END
            ), 0
        ) AS confirmed_amount
    FROM bookings
    WHERE user_id = ?
";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();

$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

// Get the customer's five most recent bookings.
$recent_query = "
    SELECT
        bookings.id,
        bookings.travel_date,
        bookings.persons,
        bookings.total_amount,
        bookings.status,
        tour_packages.package_name,
        destinations.name AS destination_name
    FROM bookings
    INNER JOIN tour_packages
        ON bookings.package_id = tour_packages.id
    INNER JOIN destinations
        ON tour_packages.destination_id = destinations.id
    WHERE bookings.user_id = ?
    ORDER BY bookings.created_at DESC
    LIMIT 5
";

$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->bind_param("i", $user_id);
$recent_stmt->execute();
$recent_bookings = $recent_stmt->get_result();

$total_bookings = (int) ($stats["total_bookings"] ?? 0);
$confirmed_bookings = (int) ($stats["confirmed_bookings"] ?? 0);
$pending_bookings = (int) ($stats["pending_bookings"] ?? 0);
$cancelled_bookings = (int) ($stats["cancelled_bookings"] ?? 0);
$confirmed_amount = (float) ($stats["confirmed_amount"] ?? 0);

require_once "../includes/customer_header.php";
?>

<style>
/* Dashboard styles only; retain the existing customer header/footer. */

.customer-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 10px 0 35px;
    color: #172033;
}

.dashboard-welcome {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    padding: 35px;
    margin-bottom: 25px;
    border-radius: 16px;
    background: linear-gradient(120deg, #102a56, #2459a9);
    color: #fff;
}

.dashboard-welcome::after {
    content: "";
    position: absolute;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    border: 35px solid rgba(255,255,255,.07);
    right: -55px;
    top: -95px;
    pointer-events: none;
}

.welcome-content {
    position: relative;
    z-index: 1;
}

.welcome-label {
    margin-bottom: 12px;
    color: #dbeafe;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
}

.welcome-content h1 {
    margin: 0 0 12px;
    font-size: 30px;
    line-height: 1.3;
    font-weight: 700;
}

.welcome-content p {
    max-width: 580px;
    margin: 0;
    color: #e3edff;
    font-size: 14px;
    line-height: 1.8;
}

.welcome-action {
    position: relative;
    z-index: 1;
    flex-shrink: 0;
}

.primary-action {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 13px 19px;
    background: #fff;
    color: #19468d !important;
    border-radius: 8px;
    text-decoration: none !important;
    font-size: 13px;
    font-weight: 700;
    transition: background .2s, transform .2s;
}

.primary-action:hover {
    background: #eff6ff;
    transform: translateY(-2px);
}

.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
    margin: 28px 0 16px;
}

.section-heading h2 {
    margin: 0 0 6px;
    color: #172033;
    font-size: 18px;
    font-weight: 700;
}

.section-heading p {
    margin: 0;
    color: #8490a3;
    font-size: 12px;
}

.text-link {
    color: #2563eb !important;
    text-decoration: none !important;
    font-size: 12px;
    font-weight: 700;
}

.text-link:hover {
    text-decoration: underline !important;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
}

.dashboard-stat {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    padding: 22px 18px;
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(15,23,42,.03);
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    border-radius: 11px;
    font-size: 22px;
}

.stat-blue {
    background: #eff6ff;
    color: #2563eb;
}

.stat-green {
    background: #ecfdf3;
    color: #15803d;
}

.stat-orange {
    background: #fff7ed;
    color: #c2410c;
}

.stat-purple {
    background: #f5f3ff;
    color: #7c3aed;
}

.stat-label {
    margin-bottom: 7px;
    color: #8490a3;
    font-size: 11px;
    line-height: 1.5;
}

.stat-value {
    color: #172033;
    font-size: 24px;
    font-weight: 700;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.stat-caption {
    margin-top: 5px;
    color: #98a2b3;
    font-size: 10px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}

.quick-action-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 23px;
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 12px;
    text-decoration: none !important;
    transition: transform .2s, box-shadow .2s, border-color .2s;
}

.quick-action-card:hover {
    transform: translateY(-3px);
    border-color: #bfdbfe;
    box-shadow: 0 8px 22px rgba(15,23,42,.06);
}

.quick-action-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    background: #eff6ff;
    color: #2563eb;
    border-radius: 11px;
    font-size: 22px;
}

.quick-action-text {
    min-width: 0;
    flex: 1;
}

.quick-action-text h3 {
    margin: 0 0 7px;
    color: #172033;
    font-size: 14px;
    font-weight: 700;
}

.quick-action-text p {
    margin: 0;
    color: #8490a3;
    font-size: 11px;
    line-height: 1.6;
}

.quick-arrow {
    color: #98a2b3;
    font-size: 20px;
}

.recent-card {
    overflow: hidden;
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(15,23,42,.03);
}

.recent-card-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 21px 23px;
    border-bottom: 1px solid #edf0f4;
}

.recent-card-heading h2 {
    margin: 0 0 6px;
    font-size: 16px;
}

.recent-card-heading p {
    margin: 0;
    color: #8490a3;
    font-size: 11px;
}

.dashboard-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.dashboard-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
    text-align: left;
}

.dashboard-table th {
    padding: 14px 18px;
    background: #f8fafc;
    color: #667085;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}

.dashboard-table td {
    padding: 16px 18px;
    border-top: 1px solid #edf0f4;
    color: #475467;
    font-size: 12px;
    vertical-align: middle;
}

.dashboard-table tbody tr:hover {
    background: #fafcff;
}

.booking-package {
    margin-bottom: 5px;
    color: #172033;
    font-size: 12px;
    font-weight: 700;
}

.booking-destination {
    color: #8490a3;
    font-size: 11px;
}

.booking-id {
    color: #2563eb;
    font-weight: 700;
}

.booking-status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.status-confirmed {
    background: #ecfdf3;
    color: #15803d;
}

.status-pending {
    background: #fff7ed;
    color: #c2410c;
}

.status-cancelled {
    background: #fef2f2;
    color: #b91c1c;
}

.booking-amount {
    color: #172033;
    font-weight: 700;
    white-space: nowrap;
}

.empty-bookings {
    padding: 45px 20px;
    text-align: center;
}

.empty-bookings-icon {
    margin-bottom: 12px;
    font-size: 38px;
}

.empty-bookings h3 {
    margin: 0 0 8px;
    color: #172033;
    font-size: 16px;
}

.empty-bookings p {
    margin: 0 auto 20px;
    max-width: 440px;
    color: #8490a3;
    font-size: 12px;
    line-height: 1.8;
}

.empty-bookings .primary-action {
    background: #2563eb;
    color: #fff !important;
}

.empty-bookings .primary-action:hover {
    background: #1d4ed8;
}

.dashboard-note {
    margin-top: 22px;
    padding: 15px 18px;
    background: #f8fafc;
    border: 1px solid #e7eaf0;
    border-radius: 9px;
    color: #667085;
    font-size: 11px;
    line-height: 1.7;
}

@media(max-width: 1050px) {
    .dashboard-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .quick-actions {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 650px) {
    .dashboard-welcome {
        align-items: flex-start;
        flex-direction: column;
        padding: 25px 22px;
    }

    .welcome-content h1 {
        font-size: 24px;
    }

    .dashboard-stats {
        grid-template-columns: 1fr;
    }

    .dashboard-stat {
        padding: 18px;
    }

    .section-heading h2 {
        font-size: 17px;
    }

    .quick-action-card {
        padding: 18px;
    }
}
</style>

<div class="customer-dashboard">

    <!-- Welcome section -->
    <section class="dashboard-welcome">
        <div class="welcome-content">
            <div class="welcome-label">Your travel space</div>

            <h1>
                Welcome back,
                <?php echo htmlspecialchars($user_name); ?>!
            </h1>

            <p>
                Ready for your next adventure? Explore beautiful
                destinations, discover travel packages and keep track
                of your bookings from one convenient place.
            </p>
        </div>

        <div class="welcome-action">
            <a
                href="/tour-travel/customer/packages.php"
                class="primary-action"
            >
                Explore Packages <span>→</span>
            </a>
        </div>
    </section>

    <!-- Booking statistics -->
    <div class="section-heading">
        <div>
            <h2>Your Booking Overview</h2>
            <p>A quick summary of your travel activity.</p>
        </div>

        <a
            href="/tour-travel/customer/bookings.php"
            class="text-link"
        >
            View All Bookings →
        </a>
    </div>

    <section class="dashboard-stats">

        <div class="dashboard-stat">
            <div class="stat-icon stat-blue">▤</div>
            <div>
                <div class="stat-label">Total Bookings</div>
                <div class="stat-value">
                    <?php echo $total_bookings; ?>
                </div>
                <div class="stat-caption">All your reservations</div>
            </div>
        </div>

        <div class="dashboard-stat">
            <div class="stat-icon stat-green">✓</div>
            <div>
                <div class="stat-label">Confirmed Bookings</div>
                <div class="stat-value">
                    <?php echo $confirmed_bookings; ?>
                </div>
                <div class="stat-caption">Confirmed reservations</div>
            </div>
        </div>

        <div class="dashboard-stat">
            <div class="stat-icon stat-orange">◷</div>
            <div>
                <div class="stat-label">Pending Bookings</div>
                <div class="stat-value">
                    <?php echo $pending_bookings; ?>
                </div>
                <div class="stat-caption">Awaiting confirmation</div>
            </div>
        </div>

        <div class="dashboard-stat">
            <div class="stat-icon stat-purple">₹</div>
            <div>
                <div class="stat-label">Confirmed Booking Value</div>
                <div class="stat-value">
                    ₹<?php echo number_format($confirmed_amount, 2); ?>
                </div>
                <div class="stat-caption">
                    Value of confirmed bookings
                </div>
            </div>
        </div>

    </section>

    <!-- Quick actions -->
    <div class="section-heading">
        <div>
            <h2>Quick Actions</h2>
            <p>Everything you need for your next trip.</p>
        </div>
    </div>

    <section class="quick-actions">

        <a
            href="/tour-travel/customer/packages.php"
            class="quick-action-card"
        >
            <div class="quick-action-icon">⌖</div>

            <div class="quick-action-text">
                <h3>Explore Destinations</h3>
                <p>Browse available packages and find your next destination.</p>
            </div>

            <span class="quick-arrow">›</span>
        </a>

        <a
            href="/tour-travel/customer/bookings.php"
            class="quick-action-card"
        >
            <div class="quick-action-icon">▤</div>

            <div class="quick-action-text">
                <h3>My Bookings</h3>
                <p>Review your reservations, travel dates and booking statuses.</p>
            </div>

            <span class="quick-arrow">›</span>
        </a>

        <a
            href="/tour-travel/customer/packages.php"
            class="quick-action-card"
        >
            <div class="quick-action-icon">✈</div>

            <div class="quick-action-text">
                <h3>Plan Another Trip</h3>
                <p>Discover another package for your next holiday.</p>
            </div>

            <span class="quick-arrow">›</span>
        </a>

    </section>

    <!-- Recent bookings -->
    <div class="section-heading">
        <div>
            <h2>Recent Bookings</h2>
            <p>Your five most recent reservations.</p>
        </div>

        <a
            href="/tour-travel/customer/bookings.php"
            class="text-link"
        >
            Booking History →
        </a>
    </div>

    <section class="recent-card">

        <div class="recent-card-heading">
            <div>
                <h2>Recent Travel Activity</h2>
                <p>Check your latest booking details and statuses.</p>
            </div>
        </div>

        <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>

            <div class="dashboard-table-wrap">
                <table class="dashboard-table">

                    <thead>
                        <tr>
                            <th>Booking</th>
                            <th>Package / Destination</th>
                            <th>Travel Date</th>
                            <th>Travellers</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($booking = $recent_bookings->fetch_assoc()): ?>

                        <?php
                        $status = strtolower($booking["status"] ?? "pending");

                        $status_class = "status-pending";

                        if ($status === "confirmed") {
                            $status_class = "status-confirmed";
                        } elseif ($status === "cancelled") {
                            $status_class = "status-cancelled";
                        }
                        ?>

                        <tr>

                            <td>
                                <span class="booking-id">
                                    #<?php echo (int)$booking["id"]; ?>
                                </span>
                            </td>

                            <td>
                                <div class="booking-package">
                                    <?php
                                    echo htmlspecialchars(
                                        $booking["package_name"]
                                    );
                                    ?>
                                </div>

                                <div class="booking-destination">
                                    <?php
                                    echo htmlspecialchars(
                                        $booking["destination_name"]
                                    );
                                    ?>
                                </div>
                            </td>

                            <td>
                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime($booking["travel_date"])
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo (int)$booking["persons"]; ?>
                            </td>

                            <td>
                                <span class="booking-amount">
                                    ₹<?php
                                    echo number_format(
                                        (float)$booking["total_amount"],
                                        2
                                    );
                                    ?>
                                </span>
                            </td>

                            <td>
                                <span class="booking-status <?php echo $status_class; ?>">
                                    <?php
                                    echo htmlspecialchars(
                                        ucfirst($status)
                                    );
                                    ?>
                                </span>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>
                </table>
            </div>

        <?php else: ?>

            <div class="empty-bookings">
                <div class="empty-bookings-icon">✈</div>

                <h3>Your next adventure starts here</h3>

                <p>
                    You have not made any bookings yet. Explore our
                    available tour packages and choose a destination
                    for your next trip.
                </p>

                <a
                    href="/tour-travel/customer/packages.php"
                    class="primary-action"
                >
                    Browse Tour Packages →
                </a>
            </div>

        <?php endif; ?>

    </section>

    <div class="dashboard-note">
        <strong>Travel tip:</strong>
        Review your booking status and travel date before your journey.
        For payment and transaction details, open the relevant booking
        or confirmation page.
    </div>

</div>

<?php
$recent_stmt->close();
require_once "../includes/customer_footer.php";
?>
