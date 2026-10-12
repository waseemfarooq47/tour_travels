<?php

require_once "../includes/admin_header.php";
require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
*/

function getCount($conn, $query)
{
    $result = $conn->query($query);

    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['total'];
    }

    return 0;
}


/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

/* Customers */
$total_customers = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role = 'customer'"
);


/* Destinations */
$total_destinations = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM destinations"
);


/*
|--------------------------------------------------------------------------
| Packages
|--------------------------------------------------------------------------
|
| Your admin folder is called packages.
| We use the packages table here.
|
*/

$total_packages = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM packages"
);


/* Total Bookings */
$total_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings"
);


/* Confirmed Bookings */
$confirmed_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE status = 'confirmed'"
);


/* Pending Bookings */
$pending_bookings = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE status = 'pending'"
);


/*
|--------------------------------------------------------------------------
| Revenue
|--------------------------------------------------------------------------
*/

$total_revenue = 0;

$revenue_query = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE payment_status = 'paid'
");

if ($revenue_query) {

    $revenue_row = $revenue_query->fetch_assoc();

    $total_revenue = $revenue_row['total'] ?? 0;
}


/*
|--------------------------------------------------------------------------
| Recent Bookings
|--------------------------------------------------------------------------
*/

$recent_bookings = $conn->query("
    SELECT *
    FROM bookings
    ORDER BY id DESC
    LIMIT 8
");


/*
|--------------------------------------------------------------------------
| Booking Status
|--------------------------------------------------------------------------
*/

$status_counts = [

    'confirmed' => 0,
    'pending'   => 0,
    'cancelled' => 0

];


$status_query = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM bookings
    GROUP BY status
");


if ($status_query) {

    while ($row = $status_query->fetch_assoc()) {

        $status = strtolower(trim($row['status']));

        if (isset($status_counts[$status])) {

            $status_counts[$status] = (int)$row['total'];

        }

    }

}

?>


<style>

/* =========================================================
   Dashboard Header
   ========================================================= */

.dashboard-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}


.dashboard-title h1 {

    margin: 0 0 6px;

    font-size: 26px;

    color: #111827;
}


.dashboard-title p {

    margin: 0;

    font-size: 13px;

    color: #6b7280;
}


/* =========================================================
   Quick Actions
   ========================================================= */

.quick-actions {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;
}


.quick-actions a {

    text-decoration: none;
}


/* =========================================================
   Statistics
   ========================================================= */

.stats-grid {

    display: grid;

    grid-template-columns: repeat(4, 1fr);

    gap: 18px;

    margin-bottom: 20px;
}


.stat-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    padding: 20px;

    transition: 0.2s;
}


.stat-card:hover {

    transform: translateY(-2px);

    box-shadow: 0 5px 18px rgba(0,0,0,0.06);
}


.stat-top {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 15px;
}


.stat-icon {

    width: 42px;

    height: 42px;

    border-radius: 9px;

    background: #eff6ff;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;
}


.stat-label {

    color: #6b7280;

    font-size: 12px;

    margin-bottom: 5px;
}


.stat-value {

    color: #111827;

    font-size: 27px;

    font-weight: bold;
}


/* =========================================================
   Secondary Cards
   ========================================================= */

.secondary-grid {

    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 25px;
}


.secondary-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    padding: 18px 20px;

    display: flex;

    justify-content: space-between;

    align-items: center;
}


.secondary-label {

    font-size: 12px;

    color: #6b7280;

    margin-bottom: 5px;
}


.secondary-value {

    font-size: 21px;

    font-weight: bold;

    color: #111827;
}


.secondary-icon {

    font-size: 20px;
}


/* =========================================================
   Main Dashboard Grid
   ========================================================= */

.dashboard-grid {

    display: grid;

    grid-template-columns: 2fr 1fr;

    gap: 20px;
}


/* =========================================================
   Dashboard Cards
   ========================================================= */

.dashboard-card {

    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    overflow: hidden;
}


.dashboard-card-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 18px 20px;

    border-bottom: 1px solid #e5e7eb;
}


.dashboard-card-header h2 {

    margin: 0;

    font-size: 16px;

    color: #111827;
}


.dashboard-card-header a {

    font-size: 12px;

    color: #2563eb;

    text-decoration: none;
}


/* =========================================================
   Booking Table
   ========================================================= */

.table-wrapper {

    overflow-x: auto;
}


.dashboard-table {

    width: 100%;

    border-collapse: collapse;
}


.dashboard-table th {

    background: #f9fafb;

    color: #6b7280;

    font-size: 11px;

    font-weight: 600;

    text-align: left;

    padding: 13px 18px;

    border-bottom: 1px solid #e5e7eb;
}


.dashboard-table td {

    padding: 14px 18px;

    font-size: 12px;

    color: #374151;

    border-bottom: 1px solid #f0f0f0;
}


.dashboard-table tr:last-child td {

    border-bottom: none;
}


.booking-id {

    font-weight: bold;

    color: #2563eb;
}


/* =========================================================
   Status
   ========================================================= */

.status {

    display: inline-block;

    padding: 5px 9px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: bold;

    text-transform: capitalize;
}


.status-confirmed {

    background: #dcfce7;

    color: #166534;
}


.status-pending {

    background: #fef3c7;

    color: #92400e;
}


.status-cancelled {

    background: #fee2e2;

    color: #991b1b;
}


/* =========================================================
   Booking Summary
   ========================================================= */

.summary-content {

    padding: 15px 20px;
}


.summary-item {

    display: flex;

    justify-content: space-between;

    align-items: center;

    padding: 15px 0;

    border-bottom: 1px solid #f0f0f0;
}


.summary-item:last-child {

    border-bottom: none;
}


.summary-left {

    display: flex;

    align-items: center;

    gap: 10px;
}


.summary-dot {

    width: 9px;

    height: 9px;

    border-radius: 50%;

    background: #2563eb;
}


.summary-label {

    font-size: 13px;

    color: #4b5563;
}


.summary-number {

    font-size: 14px;

    font-weight: bold;

    color: #111827;
}


/* =========================================================
   Empty Message
   ========================================================= */

.empty-message {

    padding: 35px;

    text-align: center;

    color: #6b7280;

    font-size: 13px;
}


/* =========================================================
   Responsive
   ========================================================= */

@media (max-width: 1100px) {

    .stats-grid {

        grid-template-columns: repeat(2, 1fr);
    }

    .dashboard-grid {

        grid-template-columns: 1fr;
    }

}


@media (max-width: 700px) {

    .dashboard-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .secondary-grid {

        grid-template-columns: 1fr;
    }

}


@media (max-width: 500px) {

    .stats-grid {

        grid-template-columns: 1fr;
    }

}

</style>


<!-- =========================================================
     Dashboard Heading
     ========================================================= -->

<div class="dashboard-header">

    <div class="dashboard-title">

        <h1>
            Dashboard
        </h1>

        <p>
            Welcome to your Tour & Travel Management System.
        </p>

    </div>


    <div class="quick-actions">

        <a
            href="destinations/add.php"
            class="btn btn-primary"
        >
            + Add Destination
        </a>


        <a
            href="packages/index.php"
            class="btn btn-secondary"
        >
            Tour Packages
        </a>

    </div>

</div>


<!-- =========================================================
     Main Statistics
     ========================================================= -->

<div class="stats-grid">


    <!-- Customers -->

    <div class="stat-card">

        <div class="stat-top">

            <div>

                <div class="stat-label">
                    Total Customers
                </div>

                <div class="stat-value">
                    <?php echo number_format($total_customers); ?>
                </div>

            </div>

            <div class="stat-icon">
                👥
            </div>

        </div>

    </div>


    <!-- Destinations -->

    <div class="stat-card">

        <div class="stat-top">

            <div>

                <div class="stat-label">
                    Destinations
                </div>

                <div class="stat-value">
                    <?php echo number_format($total_destinations); ?>
                </div>

            </div>

            <div class="stat-icon">
                📍
            </div>

        </div>

    </div>


    <!-- Packages -->

    <div class="stat-card">

        <div class="stat-top">

            <div>

                <div class="stat-label">
                    Tour Packages
                </div>

                <div class="stat-value">
                    <?php echo number_format($total_packages); ?>
                </div>

            </div>

            <div class="stat-icon">
                🧳
            </div>

        </div>

    </div>


    <!-- Bookings -->

    <div class="stat-card">

        <div class="stat-top">

            <div>

                <div class="stat-label">
                    Total Bookings
                </div>

                <div class="stat-value">
                    <?php echo number_format($total_bookings); ?>
                </div>

            </div>

            <div class="stat-icon">
                📅
            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     Secondary Statistics
     ========================================================= -->

<div class="secondary-grid">


    <div class="secondary-card">

        <div>

            <div class="secondary-label">
                Confirmed Bookings
            </div>

            <div class="secondary-value">
                <?php echo number_format($confirmed_bookings); ?>
            </div>

        </div>

        <div class="secondary-icon">
            ✓
        </div>

    </div>


    <div class="secondary-card">

        <div>

            <div class="secondary-label">
                Pending Bookings
            </div>

            <div class="secondary-value">
                <?php echo number_format($pending_bookings); ?>
            </div>

        </div>

        <div class="secondary-icon">
            ⏳
        </div>

    </div>


    <div class="secondary-card">

        <div>

            <div class="secondary-label">
                Total Revenue
            </div>

            <div class="secondary-value">

                ₹<?php echo number_format(
                    $total_revenue,
                    2
                ); ?>

            </div>

        </div>

        <div class="secondary-icon">
            ₹
        </div>

    </div>

</div>


<!-- =========================================================
     Recent Bookings + Booking Summary
     ========================================================= -->

<div class="dashboard-grid">


    <!-- Recent Bookings -->

    <div class="dashboard-card">

        <div class="dashboard-card-header">

            <h2>
                Recent Bookings
            </h2>

            <a href="bookings/index.php">
                View All →
            </a>

        </div>


        <div class="table-wrapper">

            <?php if (
                $recent_bookings &&
                $recent_bookings->num_rows > 0
            ): ?>

                <table class="dashboard-table">

                    <thead>

                        <tr>

                            <th>
                                Booking ID
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Package
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php while (
                        $booking =
                        $recent_bookings->fetch_assoc()
                    ): ?>

                        <tr>

                            <td>

                                <span class="booking-id">

                                    #<?php
                                    echo htmlspecialchars(
                                        $booking['id']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(

                                    $booking['customer_name']
                                    ??
                                    $booking['user_id']
                                    ??
                                    'Customer'

                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(

                                    $booking['package_name']
                                    ??
                                    $booking['package_id']
                                    ??
                                    'Tour Package'

                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                $date =
                                    $booking['travel_date']
                                    ??
                                    $booking['booking_date']
                                    ??
                                    $booking['created_at']
                                    ??
                                    null;


                                if ($date) {

                                    echo date(
                                        "d M Y",
                                        strtotime($date)
                                    );

                                } else {

                                    echo "-";

                                }

                                ?>

                            </td>


                            <td>

                                <?php

                                $status =
                                    strtolower(
                                        trim(
                                            $booking['status']
                                            ?? 'pending'
                                        )
                                    );

                                ?>

                                <span
                                    class="status status-<?php
                                    echo htmlspecialchars($status);
                                    ?>"
                                >

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


            <?php else: ?>

                <div class="empty-message">

                    No bookings available.

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- Booking Summary -->

    <div class="dashboard-card">

        <div class="dashboard-card-header">

            <h2>
                Booking Summary
            </h2>

        </div>


        <div class="summary-content">


            <div class="summary-item">

                <div class="summary-left">

                    <span class="summary-dot"></span>

                    <span class="summary-label">
                        Confirmed
                    </span>

                </div>

                <span class="summary-number">

                    <?php
                    echo number_format(
                        $status_counts['confirmed']
                    );
                    ?>

                </span>

            </div>


            <div class="summary-item">

                <div class="summary-left">

                    <span class="summary-dot"></span>

                    <span class="summary-label">
                        Pending
                    </span>

                </div>

                <span class="summary-number">

                    <?php
                    echo number_format(
                        $status_counts['pending']
                    );
                    ?>

                </span>

            </div>


            <div class="summary-item">

                <div class="summary-left">

                    <span class="summary-dot"></span>

                    <span class="summary-label">
                        Cancelled
                    </span>

                </div>

                <span class="summary-number">

                    <?php
                    echo number_format(
                        $status_counts['cancelled']
                    );
                    ?>

                </span>

            </div>


            <div class="summary-item">

                <div class="summary-left">

                    <span class="summary-dot"></span>

                    <span class="summary-label">
                        Total Bookings
                    </span>

                </div>

                <span class="summary-number">

                    <?php
                    echo number_format(
                        $total_bookings
                    );
                    ?>

                </span>

            </div>


        </div>

    </div>

</div>


<?php

require_once "../includes/admin_footer.php";

?>