
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: /tour-travel/auth/login.php");
    exit();
}

require_once "../../config/database.php";

// Handle package deletion.
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_id"])
) {
    $delete_id = filter_var(
        $_POST["delete_id"],
        FILTER_VALIDATE_INT
    );

    if ($delete_id && $delete_id > 0) {
        $stmt = $conn->prepare(
            "DELETE FROM tour_packages WHERE id = ?"
        );

        if ($stmt) {
            $stmt->bind_param("i", $delete_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: /tour-travel/admin/packages/index.php?deleted=1");
    exit();
}

// Fetch package information and destination names.
$query = "
    SELECT
        tour_packages.*,
        destinations.name AS destination_name,
        destinations.location AS destination_location
    FROM tour_packages
    INNER JOIN destinations
        ON tour_packages.destination_id = destinations.id
    ORDER BY tour_packages.id DESC
";

$result = $conn->query($query);

// Calculate statistics.
$total_packages = 0;
$active_packages = 0;
$inactive_packages = 0;

if ($result) {
    $total_packages = $result->num_rows;

    while ($package = $result->fetch_assoc()) {
        if ((int)$package["status"] === 1) {
            $active_packages++;
        } else {
            $inactive_packages++;
        }
    }

    $result->data_seek(0);
}

require_once "../../includes/admin_header.php";
?>

<style>
.packages-page {
    max-width: 1400px;
    margin: 0 auto;
    padding-bottom: 35px;
    color: #172033;
}

.packages-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 25px;
}

.packages-heading h1 {
    margin: 0 0 8px;
    font-size: 27px;
    font-weight: 700;
}

.packages-heading p {
    margin: 0;
    color: #7a8496;
    font-size: 13px;
    line-height: 1.6;
}

.add-package-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 18px;
    background: #2563eb;
    color: #fff !important;
    border-radius: 8px;
    text-decoration: none !important;
    font-size: 13px;
    font-weight: 600;
    transition: background .2s;
}

.add-package-btn:hover {
    background: #1d4ed8;
}

.package-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.package-stat {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 22px;
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(15,23,42,.03);
}

.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #eff6ff;
    color: #2563eb;
    font-size: 23px;
    flex-shrink: 0;
}

.stat-icon.active-icon {
    background: #ecfdf3;
    color: #16a34a;
}

.stat-icon.inactive-icon {
    background: #fff7ed;
    color: #ea580c;
}

.stat-label {
    margin-bottom: 7px;
    color: #7a8496;
    font-size: 12px;
}

.stat-number {
    color: #172033;
    font-size: 25px;
    font-weight: 700;
}

.package-alert {
    padding: 14px 17px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
}

.success-alert {
    color: #166534;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.package-table-card {
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 3px 12px rgba(15,23,42,.03);
}

.table-card-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    padding: 21px 24px;
    border-bottom: 1px solid #edf0f4;
}

.table-card-heading h2 {
    margin: 0 0 5px;
    font-size: 16px;
}

.table-card-heading p {
    margin: 0;
    color: #8490a3;
    font-size: 12px;
}

.package-search {
    width: 250px;
    max-width: 100%;
    padding: 11px 13px;
    border: 1px solid #d9dee7;
    border-radius: 7px;
    outline: none;
    font-size: 12px;
    box-sizing: border-box;
}

.package-search:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,.08);
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
}

.package-table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
    text-align: left;
}

.package-table th {
    padding: 15px 17px;
    background: #f8fafc;
    color: #667085;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .4px;
    font-weight: 700;
    white-space: nowrap;
}

.package-table td {
    padding: 16px 17px;
    border-top: 1px solid #edf0f4;
    color: #475467;
    font-size: 12px;
    vertical-align: middle;
}

.package-table tbody tr:hover {
    background: #fafcff;
}

.package-id {
    color: #98a2b3;
    font-size: 11px;
}

.package-name {
    color: #172033;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 5px;
}

.destination-name {
    color: #344054;
    font-weight: 600;
    margin-bottom: 5px;
}

.destination-location {
    color: #98a2b3;
    font-size: 11px;
}

.duration-badge {
    display: inline-block;
    padding: 7px 10px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 6px;
    white-space: nowrap;
}

.package-price {
    color: #172033;
    font-size: 14px;
    font-weight: 700;
    white-space: nowrap;
}

.price-label {
    display: block;
    margin-top: 4px;
    color: #98a2b3;
    font-size: 10px;
    font-weight: 400;
}

.status-badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.status-active {
    background: #ecfdf3;
    color: #15803d;
}

.status-inactive {
    background: #fff7ed;
    color: #c2410c;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn {
    display: inline-block;
    padding: 8px 11px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none !important;
    border: none;
    cursor: pointer;
    white-space: nowrap;
}

.edit-btn {
    background: #eff6ff;
    color: #1d4ed8 !important;
}

.edit-btn:hover {
    background: #dbeafe;
}

.delete-btn {
    background: #fef2f2;
    color: #b91c1c !important;
}

.delete-btn:hover {
    background: #fee2e2;
}

.empty-state {
    text-align: center;
    padding: 55px 20px;
}

.empty-icon {
    font-size: 35px;
    margin-bottom: 12px;
}

.empty-state h3 {
    margin: 0 0 8px;
    color: #172033;
    font-size: 17px;
}

.empty-state p {
    margin: 0 0 20px;
    color: #8490a3;
    font-size: 12px;
}

.no-results {
    display: none;
    text-align: center;
    padding: 25px;
    color: #8490a3;
    font-size: 12px;
}

@media(max-width: 800px) {
    .package-stats {
        grid-template-columns: 1fr;
    }

    .packages-heading h1 {
        font-size: 23px;
    }

    .package-search {
        width: 100%;
    }
}
</style>

<div class="packages-page">

    <div class="packages-heading">
        <div>
            <h1>Manage Tour Packages</h1>
            <p>
                Create and manage travel packages, destinations,
                durations and prices.
            </p>
        </div>

        <a
            href="/tour-travel/admin/packages/add.php"
            class="add-package-btn"
        >
            <span>＋</span> Add Tour Package
        </a>
    </div>

    <?php if (isset($_GET["added"])): ?>
        <div class="package-alert success-alert">
            Tour package added successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["updated"])): ?>
        <div class="package-alert success-alert">
            Tour package updated successfully.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET["deleted"])): ?>
        <div class="package-alert success-alert">
            Package deletion request completed.
        </div>
    <?php endif; ?>

    <div class="package-stats">

        <div class="package-stat">
            <div class="stat-icon">🧳</div>
            <div>
                <div class="stat-label">Total Packages</div>
                <div class="stat-number">
                    <?php echo $total_packages; ?>
                </div>
            </div>
        </div>

        <div class="package-stat">
            <div class="stat-icon active-icon">✓</div>
            <div>
                <div class="stat-label">Active Packages</div>
                <div class="stat-number">
                    <?php echo $active_packages; ?>
                </div>
            </div>
        </div>

        <div class="package-stat">
            <div class="stat-icon inactive-icon">◷</div>
            <div>
                <div class="stat-label">Inactive Packages</div>
                <div class="stat-number">
                    <?php echo $inactive_packages; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="package-table-card">

        <div class="table-card-heading">
            <div>
                <h2>All Tour Packages</h2>
                <p>
                    <?php echo $total_packages; ?>
                    package record(s)
                </p>
            </div>

            <?php if ($total_packages > 0): ?>
                <input
                    type="search"
                    id="packageSearch"
                    class="package-search"
                    placeholder="Search packages or destinations..."
                    aria-label="Search tour packages"
                >
            <?php endif; ?>
        </div>

        <?php if ($result && $total_packages > 0): ?>

            <div class="table-responsive">

                <table class="package-table" id="packageTable">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Destination</th>
                            <th>Package Name</th>
                            <th>Duration</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <span class="package-id">
                                    #<?php echo (int)$row["id"]; ?>
                                </span>
                            </td>

                            <td>
                                <div class="destination-name">
                                    <?php
                                    echo htmlspecialchars(
                                        $row["destination_name"]
                                    );
                                    ?>
                                </div>

                                <div class="destination-location">
                                    <?php
                                    echo htmlspecialchars(
                                        $row["destination_location"] ?? ""
                                    );
                                    ?>
                                </div>
                            </td>

                            <td>
                                <div class="package-name">
                                    <?php
                                    echo htmlspecialchars(
                                        $row["package_name"]
                                    );
                                    ?>
                                </div>
                            </td>

                            <td>
                                <span class="duration-badge">
                                    <?php echo (int)$row["duration"]; ?>
                                    day(s)
                                </span>
                            </td>

                            <td>
                                <span class="package-price">
                                    ₹<?php
                                    echo number_format(
                                        (float)$row["price"],
                                        2
                                    );
                                    ?>
                                </span>

                                <span class="price-label">
                                    Per person
                                </span>
                            </td>

                            <td>
                                <?php if ((int)$row["status"] === 1): ?>
                                    <span class="status-badge status-active">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="action-buttons">

                                    <a
                                        href="/tour-travel/admin/packages/edit.php?id=<?php echo (int)$row["id"]; ?>"
                                        class="action-btn edit-btn"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="/tour-travel/admin/packages/index.php"
                                        onsubmit="return confirm('Are you sure you want to delete this tour package?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="delete_id"
                                            value="<?php echo (int)$row["id"]; ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="action-btn delete-btn"
                                        >
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

                <div class="no-results" id="noResults">
                    No matching tour packages found.
                </div>

            </div>

        <?php else: ?>

            <div class="empty-state">
                <div class="empty-icon">🧳</div>
                <h3>No tour packages available</h3>
                <p>
                    Add your first tour package to start managing travel offers.
                </p>

                <a
                    href="/tour-travel/admin/packages/add.php"
                    class="add-package-btn"
                >
                    ＋ Add Tour Package
                </a>
            </div>

        <?php endif; ?>

    </div>

</div>

<script>
const packageSearch = document.getElementById("packageSearch");
const packageTable = document.getElementById("packageTable");
const noResults = document.getElementById("noResults");

if (packageSearch && packageTable) {
    packageSearch.addEventListener("input", function () {
        const searchText = this.value.toLowerCase().trim();
        const rows = packageTable.querySelectorAll("tbody tr");
        let visibleRows = 0;

        rows.forEach(function (row) {
            const matches = row.textContent
                .toLowerCase()
                .includes(searchText);

            row.style.display = matches ? "" : "none";

            if (matches) {
                visibleRows++;
            }
        });

        noResults.style.display =
            visibleRows === 0 ? "block" : "none";
    });
}
</script>

<?php require_once "../../includes/admin_footer.php"; ?>