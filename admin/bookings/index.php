
<?php
session_start();

require_once "../../config/database.php";

// Admin authentication
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../../auth/login.php");
    exit();
}

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";

// Handle booking status update on this same page
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $csrfToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($csrfToken) ||
        !isset($_SESSION["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $csrfToken)
    ) {
        $message = "Invalid request. Please try again.";
        $messageType = "error";

    } else {
        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $status = $_POST["status"] ?? "";
        $allowedStatuses = ["confirmed", "cancelled"];

        if ($id === false || $id === null || $id < 1) {
            $message = "Invalid booking ID.";
            $messageType = "error";

        } elseif (!in_array($status, $allowedStatuses, true)) {
            $message = "Invalid booking status.";
            $messageType = "error";

        } else {
            $stmt = $conn->prepare(
                "UPDATE bookings
                 SET status = ?
                 WHERE id = ? AND status = 'pending'"
            );

            if ($stmt) {
                $stmt->bind_param("si", $status, $id);

                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $message = "Booking #" . $id .
                            " has been " . $status . " successfully.";
                        $messageType = "success";
                    } else {
                        $message = "Booking was not updated. It may not exist or may no longer be pending.";
                        $messageType = "error";
                    }
                } else {
                    $message = "Unable to update booking status.";
                    $messageType = "error";
                }

                $stmt->close();

            } else {
                $message = "Unable to prepare the booking update.";
                $messageType = "error";
            }
        }
    }
}

// Fetch bookings
$query = "
    SELECT
        bookings.*,
        users.name AS customer_name,
        users.email AS customer_email,
        tour_packages.package_name,
        destinations.name AS destination_name
    FROM bookings
    INNER JOIN users
        ON bookings.user_id = users.id
    INNER JOIN tour_packages
        ON bookings.package_id = tour_packages.id
    INNER JOIN destinations
        ON tour_packages.destination_id = destinations.id
    ORDER BY bookings.id DESC
";

$result = $conn->query($query);

if (!$result) {
    die("Unable to load bookings. Please check your database query.");
}

$bookings = [];
$totalBookings = 0;
$pendingBookings = 0;
$confirmedBookings = 0;
$cancelledBookings = 0;

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
    $totalBookings++;

    switch (strtolower($row["status"] ?? "")) {
        case "pending":
            $pendingBookings++;
            break;

        case "confirmed":
            $confirmedBookings++;
            break;

        case "cancelled":
            $cancelledBookings++;
            break;
    }
}

require_once "../../includes/admin_header.php";
?>

<style>
.booking-page {
    padding: 28px;
    background: #f5f7fb;
    min-height: 80vh;
    color: #172033;
}

.booking-container {
    max-width: 1500px;
    margin: 0 auto;
}

.booking-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 25px;
}

.booking-heading h1 {
    margin: 0 0 7px;
    font-size: 28px;
    font-weight: 750;
}

.booking-heading p {
    margin: 0;
    color: #697386;
    font-size: 14px;
}

.booking-count {
    background: #e8efff;
    color: #2458c6;
    padding: 10px 16px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
}

.booking-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.booking-stat {
    background: #fff;
    padding: 22px;
    border: 1px solid #e7eaf0;
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(20, 35, 70, 0.035);
}

.booking-stat-label {
    color: #6b7280;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 12px;
}

.booking-stat-number {
    font-size: 30px;
    font-weight: 750;
}

.booking-stat-total { border-top: 4px solid #2563eb; }
.booking-stat-pending { border-top: 4px solid #f59e0b; }
.booking-stat-confirmed { border-top: 4px solid #16a34a; }
.booking-stat-cancelled { border-top: 4px solid #dc2626; }

.booking-card {
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 14px;
    box-shadow: 0 3px 12px rgba(20, 35, 70, 0.035);
    overflow: hidden;
}

.booking-toolbar {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    border-bottom: 1px solid #edf0f5;
}

.booking-toolbar h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
}

.booking-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.booking-input,
.booking-select {
    padding: 11px 13px;
    border: 1px solid #dce2eb;
    border-radius: 8px;
    font-size: 13px;
    background: #fff;
    color: #172033;
    outline: none;
}

.booking-input {
    width: 260px;
    max-width: 100%;
}

.booking-input:focus,
.booking-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.booking-table-wrap {
    width: 100%;
    overflow-x: auto;
}

.booking-table {
    width: 100%;
    min-width: 1150px;
    border-collapse: collapse;
    text-align: left;
}

.booking-table th {
    background: #f8fafc;
    color: #647084;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .55px;
    font-weight: 750;
    padding: 15px 16px;
    border-bottom: 1px solid #e7eaf0;
    white-space: nowrap;
}

.booking-table td {
    padding: 17px 16px;
    font-size: 13px;
    color: #3b4658;
    border-bottom: 1px solid #edf0f5;
    vertical-align: middle;
}

.booking-table tbody tr:hover {
    background: #fafcff;
}

.booking-table tbody tr:last-child td {
    border-bottom: none;
}

.booking-id {
    font-weight: 750;
    color: #2563eb !important;
}

.customer-name {
    font-weight: 700;
    color: #172033;
    margin-bottom: 5px;
}

.customer-email,
.destination-name {
    color: #778196;
    font-size: 12px;
}

.package-name {
    font-weight: 650;
    color: #263248;
    margin-bottom: 5px;
}

.booking-amount {
    font-weight: 750;
    color: #172033 !important;
    white-space: nowrap;
}

.booking-status {
    display: inline-flex;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 750;
    text-transform: capitalize;
    white-space: nowrap;
}

.status-pending { color: #92400e; background: #fef3c7; }
.status-confirmed { color: #166534; background: #dcfce7; }
.status-cancelled { color: #991b1b; background: #fee2e2; }
.status-other { color: #475569; background: #e2e8f0; }

.booking-actions {
    display: flex;
    gap: 7px;
    align-items: center;
}

.booking-actions form {
    margin: 0;
}

.booking-action {
    display: inline-block;
    padding: 8px 11px;
    border-radius: 7px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    transition: .2s;
}

.action-confirm {
    color: #fff;
    background: #16a34a;
}

.action-confirm:hover { background: #15803d; }

.action-cancel {
    color: #dc2626;
    background: #fff1f2;
}

.action-cancel:hover { background: #ffe4e6; }

.no-action {
    color: #9aa3b2;
    font-size: 12px;
}

.booking-alert {
    padding: 14px 18px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 600;
}

.booking-alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.booking-alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.booking-empty {
    text-align: center;
    padding: 55px 20px !important;
    color: #778196 !important;
}

.booking-empty strong {
    display: block;
    color: #263248;
    font-size: 16px;
    margin-bottom: 7px;
}

.booking-footer {
    padding: 16px 20px;
    color: #778196;
    font-size: 12px;
    border-top: 1px solid #edf0f5;
}

@media (max-width: 1000px) {
    .booking-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .booking-page { padding: 16px 10px; }
    .booking-heading h1 { font-size: 23px; }
    .booking-stats { gap: 10px; }
    .booking-stat { padding: 15px; }
    .booking-stat-number { font-size: 25px; }
    .booking-toolbar { padding: 15px; }
    .booking-filters { width: 100%; }
    .booking-input, .booking-select { width: 100%; }
}
</style>

<div class="booking-page">
    <div class="booking-container">

        <div class="booking-heading">
            <div>
                <h1>Manage Customer Bookings</h1>
                <p>Review customer reservations and manage booking status.</p>
            </div>

            <div class="booking-count">
                Total Bookings: <?php echo $totalBookings; ?>
            </div>
        </div>

        <?php if ($message !== ""): ?>
            <div class="booking-alert <?php
                echo $messageType === "success"
                    ? "booking-alert-success"
                    : "booking-alert-error";
            ?>">
                <?php
                echo htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </div>
        <?php endif; ?>

        <div class="booking-stats">
            <div class="booking-stat booking-stat-total">
                <div class="booking-stat-label">Total Bookings</div>
                <div class="booking-stat-number"><?php echo $totalBookings; ?></div>
            </div>

            <div class="booking-stat booking-stat-pending">
                <div class="booking-stat-label">Pending Bookings</div>
                <div class="booking-stat-number"><?php echo $pendingBookings; ?></div>
            </div>

            <div class="booking-stat booking-stat-confirmed">
                <div class="booking-stat-label">Confirmed Bookings</div>
                <div class="booking-stat-number"><?php echo $confirmedBookings; ?></div>
            </div>

            <div class="booking-stat booking-stat-cancelled">
                <div class="booking-stat-label">Cancelled Bookings</div>
                <div class="booking-stat-number"><?php echo $cancelledBookings; ?></div>
            </div>
        </div>

        <div class="booking-card">
            <div class="booking-toolbar">
                <h2>All Reservations</h2>

                <div class="booking-filters">
                    <input
                        type="search"
                        id="bookingSearch"
                        class="booking-input"
                        placeholder="Search customer, package, email..."
                        aria-label="Search bookings"
                    >

                    <select
                        id="statusFilter"
                        class="booking-select"
                        aria-label="Filter bookings by status"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="booking-table-wrap">
                <table class="booking-table" id="bookingTable">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Package / Destination</th>
                            <th>Travel Date</th>
                            <th>Persons</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($totalBookings > 0): ?>

                        <?php foreach ($bookings as $row): ?>
                            <?php
                            $status = strtolower(trim($row["status"] ?? ""));
                            $statusClass = "status-other";

                            if ($status === "pending") {
                                $statusClass = "status-pending";
                            } elseif ($status === "confirmed") {
                                $statusClass = "status-confirmed";
                            } elseif ($status === "cancelled") {
                                $statusClass = "status-cancelled";
                            }

                            $searchText = strtolower(
                                ($row["id"] ?? "") . " " .
                                ($row["customer_name"] ?? "") . " " .
                                ($row["customer_email"] ?? "") . " " .
                                ($row["package_name"] ?? "") . " " .
                                ($row["destination_name"] ?? "")
                            );
                            ?>

                            <tr
                                class="booking-row"
                                data-status="<?php echo htmlspecialchars($status, ENT_QUOTES, "UTF-8"); ?>"
                                data-search="<?php echo htmlspecialchars($searchText, ENT_QUOTES, "UTF-8"); ?>"
                            >
                                <td class="booking-id">
                                    #<?php echo (int) $row["id"]; ?>
                                </td>

                                <td>
                                    <div class="customer-name">
                                        <?php echo htmlspecialchars($row["customer_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                    </div>
                                    <div class="customer-email">
                                        <?php echo htmlspecialchars($row["customer_email"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="package-name">
                                        <?php echo htmlspecialchars($row["package_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                    </div>
                                    <div class="destination-name">
                                        <?php echo htmlspecialchars($row["destination_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                    </div>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["travel_date"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                </td>

                                <td><?php echo (int) ($row["persons"] ?? 0); ?></td>

                                <td class="booking-amount">
                                    ₹<?php echo number_format((float) ($row["total_amount"] ?? 0), 2); ?>
                                </td>

                                <td>
                                    <span class="booking-status <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars(ucfirst($status), ENT_QUOTES, "UTF-8"); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($status === "pending"): ?>
                                        <div class="booking-actions">

                                            <form
                                                method="POST"
                                                action="index.php"
                                                onsubmit="return confirm('Confirm this booking?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo (int) $row["id"]; ?>"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="confirmed"
                                                >

                                                <button
                                                    type="submit"
                                                    class="booking-action action-confirm"
                                                >
                                                    Confirm
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                action="index.php"
                                                onsubmit="return confirm('Cancel this booking?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8"); ?>"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?php echo (int) $row["id"]; ?>"
                                                >
                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value="cancelled"
                                                >

                                                <button
                                                    type="submit"
                                                    class="booking-action action-cancel"
                                                >
                                                    Cancel
                                                </button>
                                            </form>

                                        </div>
                                    <?php else: ?>
                                        <span class="no-action">No action</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="noSearchResults" style="display:none;">
                            <td colspan="8" class="booking-empty">
                                <strong>No matching bookings found</strong>
                                Try changing your search or status filter.
                            </td>
                        </tr>

                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="booking-empty">
                                <strong>No bookings available yet</strong>
                                Customer reservations will appear here.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="booking-footer">
                Showing <span id="visibleBookings"><?php echo $totalBookings; ?></span>
                of <?php echo $totalBookings; ?> bookings
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("bookingSearch");
    const statusFilter = document.getElementById("statusFilter");
    const rows = document.querySelectorAll(".booking-row");
    const noResults = document.getElementById("noSearchResults");
    const visibleCount = document.getElementById("visibleBookings");

    function filterBookings() {
        const search = searchInput.value.trim().toLowerCase();
        const selectedStatus = statusFilter.value.toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const rowSearch = row.dataset.search || "";
            const rowStatus = row.dataset.status || "";

            const matchesSearch = rowSearch.includes(search);
            const matchesStatus =
                selectedStatus === "" || rowStatus === selectedStatus;

            const show = matchesSearch && matchesStatus;
            row.style.display = show ? "" : "none";

            if (show) {
                visible++;
            }
        });

        if (noResults) {
            noResults.style.display =
                rows.length > 0 && visible === 0 ? "" : "none";
        }

        visibleCount.textContent = visible;
    }

    searchInput.addEventListener("input", filterBookings);
    statusFilter.addEventListener("change", filterBookings);
});
</script>

<?php require_once "../../includes/admin_footer.php"; ?>
