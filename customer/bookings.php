
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../config/database.php";

// Allow only logged-in customers
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "customer"
) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

// Fetch bookings belonging to this customer only
$stmt = $conn->prepare("
    SELECT
        bookings.id,
        bookings.travel_date,
        bookings.persons,
        bookings.total_amount,
        bookings.status,
        bookings.created_at,
        tour_packages.package_name
    FROM bookings
    INNER JOIN tour_packages
        ON bookings.package_id = tour_packages.id
    WHERE bookings.user_id = ?
    ORDER BY bookings.id DESC
");

if (!$stmt) {
    die("Unable to load bookings. Please try again later.");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$totalBookings = $result->num_rows;

// Load the CUSTOMER header, not the admin header
require_once "../includes/customer_header.php";
?>

<style>
.bookings-page {
    max-width: 1150px;
    margin: 0 auto;
}

.bookings-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.bookings-heading h1 {
    color: #14213d;
    font-size: 30px;
    font-weight: 800;
    margin-bottom: 5px;
}

.bookings-heading p {
    color: #64748b;
    font-size: 14px;
}

.booking-count {
    display: inline-block;
    padding: 10px 16px;
    border-radius: 10px;
    background: #eaf1ff;
    color: #1d4ed8;
    font-size: 13px;
    font-weight: 700;
}

.booking-card {
    overflow: hidden;
    background: #fff;
    border: 1px solid #e5eaf2;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(15, 23, 42, .04);
}

.booking-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 24px;
    border-bottom: 1px solid #edf0f5;
}

.booking-card-header h2 {
    color: #172033;
    font-size: 17px;
    font-weight: 700;
}

.booking-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.booking-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 850px;
}

.booking-table th {
    padding: 15px 18px;
    text-align: left;
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .6px;
    text-transform: uppercase;
    white-space: nowrap;
}

.booking-table td {
    padding: 17px 18px;
    border-top: 1px solid #edf0f5;
    color: #334155;
    font-size: 13px;
    vertical-align: middle;
}

.booking-table tbody tr:hover {
    background: #f9fbff;
}

.booking-id {
    color: #2563eb;
    font-weight: 800;
}

.booking-package {
    color: #172033;
    font-weight: 700;
}

.booking-amount {
    color: #0f766e;
    font-weight: 800;
    white-space: nowrap;
}

.booking-status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 800;
    text-transform: capitalize;
    white-space: nowrap;
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

.status-default {
    background: #e2e8f0;
    color: #334155;
}

.booking-empty {
    padding: 55px 20px;
    text-align: center;
}

.booking-empty-icon {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    border-radius: 50%;
    background: #eff6ff;
    color: #2563eb;
    font-size: 30px;
}

.booking-empty h3 {
    margin-bottom: 8px;
    color: #172033;
    font-size: 20px;
}

.booking-empty p {
    margin-bottom: 22px;
    color: #64748b;
    font-size: 14px;
}

.booking-btn {
    display: inline-block;
    padding: 11px 18px;
    border-radius: 9px;
    background: #2563eb;
    color: white;
    font-size: 13px;
    font-weight: 700;
    transition: background .2s;
}

.booking-btn:hover {
    background: #1d4ed8;
}

@media (max-width: 600px) {
    .bookings-heading h1 {
        font-size: 25px;
    }

    .booking-card-header {
        padding: 17px;
    }
}
</style>

<main class="bookings-page">

    <div class="bookings-heading">
        <div>
            <h1>My Bookings</h1>
            <p>View and track your travel reservations in one place.</p>
        </div>

        <div class="booking-count">
            <?= (int) $totalBookings ?>
            <?= $totalBookings === 1 ? "Booking" : "Bookings" ?>
        </div>
    </div>

    <section class="booking-card">

        <div class="booking-card-header">
            <h2>Your Travel Reservations</h2>

            <a href="packages.php" class="booking-btn">
                + Explore Packages
            </a>
        </div>

        <?php if ($totalBookings > 0): ?>

            <div class="booking-table-wrap">
                <table class="booking-table">

                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Tour Package</th>
                            <th>Travel Date</th>
                            <th>Persons</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Booked On</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>

                            <?php
                            $status = strtolower(trim($row["status"] ?? "pending"));

                            $statusClass = match ($status) {
                                "confirmed" => "status-confirmed",
                                "pending"   => "status-pending",
                                "cancelled" => "status-cancelled",
                                default     => "status-default"
                            };
                            ?>

                            <tr>
                                <td class="booking-id">
                                    #<?= (int) $row["id"] ?>
                                </td>

                                <td class="booking-package">
                                    <?= htmlspecialchars(
                                        $row["package_name"] ?? "Tour Package",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </td>

                                <td>
                                    <?= !empty($row["travel_date"])
                                        ? htmlspecialchars(
                                            date("d M Y", strtotime($row["travel_date"])),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                        : "—" ?>
                                </td>

                                <td>
                                    <?= (int) $row["persons"] ?>
                                </td>

                                <td class="booking-amount">
                                    ₹<?= number_format((float) $row["total_amount"], 2) ?>
                                </td>

                                <td>
                                    <span class="booking-status <?= $statusClass ?>">
                                        <?= htmlspecialchars(
                                            ucfirst($status),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= !empty($row["created_at"])
                                        ? htmlspecialchars(
                                            date("d M Y", strtotime($row["created_at"])),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        )
                                        : "—" ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>

        <?php else: ?>

            <div class="booking-empty">
                <div class="booking-empty-icon">✈</div>

                <h3>No bookings yet</h3>

                <p>
                    You haven't booked a trip yet.
                    Explore our available packages and plan your next adventure.
                </p>

                <a href="packages.php" class="booking-btn">
                    Explore Tour Packages
                </a>
            </div>

        <?php endif; ?>

    </section>

</main>

<?php
$stmt->close();
require_once "../includes/customer_footer.php";
?>
