<?php

/* =========================================================
   ADMIN HEADER
   ========================================================= */

require_once "../../includes/admin_header.php";
require_once "../../config/database.php";


/* =========================================================
   SEARCH
   ========================================================= */

$search = trim($_GET['search'] ?? '');


/* =========================================================
   FETCH DESTINATIONS
   ========================================================= */

if ($search !== '') {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            location,
            description,
            status
        FROM destinations
        WHERE name LIKE ?
           OR location LIKE ?
           OR description LIKE ?
        ORDER BY id DESC
    ");

    $stmt->bind_param(
        "sss",
        $search_value,
        $search_value,
        $search_value
    );

    $stmt->execute();

    $destinations = $stmt->get_result();

} else {

    $destinations = $conn->query("
        SELECT
            id,
            name,
            location,
            description,
            status
        FROM destinations
        ORDER BY id DESC
    ");
}


/* =========================================================
   DESTINATION STATISTICS
   ========================================================= */

$total_destinations = 0;
$active_destinations = 0;
$inactive_destinations = 0;

$count_result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS inactive
    FROM destinations
");

if ($count_result) {

    $count = $count_result->fetch_assoc();

    $total_destinations =
        (int)($count['total'] ?? 0);

    $active_destinations =
        (int)($count['active'] ?? 0);

    $inactive_destinations =
        (int)($count['inactive'] ?? 0);
}

?>


<style>

/* =========================================================
   DESTINATIONS PAGE
   ========================================================= */

.destinations-page {
    width: 100%;
    max-width: 1450px;
    margin: 0 auto;
}


/* =========================================================
   PAGE HEADER
   ========================================================= */

.destinations-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}


.page-heading h1 {

    margin: 0;

    font-size: 28px;

    font-weight: 700;

    color: #172033;
}


.page-heading p {

    margin: 7px 0 0;

    color: #7a8496;

    font-size: 13px;
}


/* =========================================================
   ADD BUTTON
   ========================================================= */

.add-destination-btn {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    background: #2563eb;

    color: #ffffff;

    padding: 11px 18px;

    border-radius: 7px;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;

    transition: 0.2s;
}


.add-destination-btn:hover {

    background: #1d4ed8;

    color: #ffffff;

    transform: translateY(-1px);
}


.add-icon {

    font-size: 18px;

    line-height: 1;
}


/* =========================================================
   STATISTICS
   ========================================================= */

.destination-stats {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 18px;

    margin-bottom: 24px;
}


.destination-stat-card {

    background: #ffffff;

    border: 1px solid #e7eaf0;

    border-radius: 10px;

    padding: 20px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    box-shadow:
        0 2px 8px
        rgba(15, 23, 42, 0.04);
}


.stat-label {

    font-size: 12px;

    color: #7a8496;

    margin-bottom: 7px;
}


.stat-number {

    font-size: 26px;

    font-weight: 700;

    color: #172033;
}


.stat-icon {

    width: 46px;

    height: 46px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    font-size: 19px;

    font-weight: 700;
}


.total-icon {

    background: #eff6ff;

    color: #2563eb;
}


.active-icon {

    background: #ecfdf3;

    color: #16a34a;
}


.inactive-icon {

    background: #fff1f2;

    color: #dc2626;
}


/* =========================================================
   MAIN CARD
   ========================================================= */

.destination-card {

    background: #ffffff;

    border: 1px solid #e7eaf0;

    border-radius: 10px;

    box-shadow:
        0 2px 10px
        rgba(15, 23, 42, 0.04);

    overflow: hidden;
}


/* =========================================================
   CARD HEADER
   ========================================================= */

.destination-card-header {

    padding: 19px 22px;

    display: flex;

    justify-content: space-between;

    align-items: center;

    border-bottom: 1px solid #edf0f4;
}


.card-title {

    font-size: 16px;

    font-weight: 700;

    color: #172033;
}


.card-subtitle {

    margin-top: 4px;

    font-size: 11px;

    color: #8a94a6;
}


/* =========================================================
   SEARCH
   ========================================================= */

.search-area {

    display: flex;

    align-items: center;

    gap: 7px;
}


.search-box input {

    width: 260px;

    height: 38px;

    padding: 0 13px;

    border: 1px solid #d9dee7;

    border-radius: 6px;

    font-size: 12px;

    outline: none;

    background: #ffffff;
}


.search-box input:focus {

    border-color: #2563eb;

    box-shadow:
        0 0 0 3px
        rgba(37, 99, 235, 0.08);
}


.search-btn {

    height: 38px;

    padding: 0 15px;

    border: none;

    border-radius: 6px;

    background: #172033;

    color: white;

    font-size: 12px;

    font-weight: 600;

    cursor: pointer;
}


.search-btn:hover {

    background: #0f172a;
}


.clear-btn {

    height: 38px;

    display: flex;

    align-items: center;

    padding: 0 12px;

    border-radius: 6px;

    background: #f3f4f6;

    color: #4b5563;

    text-decoration: none;

    font-size: 12px;
}


.clear-btn:hover {

    background: #e5e7eb;

    color: #111827;
}


/* =========================================================
   TABLE
   ========================================================= */

.table-container {

    width: 100%;

    overflow-x: auto;
}


.destinations-table {

    width: 100%;

    border-collapse: collapse;
}


.destinations-table thead th {

    background: #f8fafc;

    color: #667085;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: 0.3px;

    text-align: left;

    padding: 14px 18px;

    border-bottom: 1px solid #e7eaf0;

    white-space: nowrap;
}


.destinations-table tbody td {

    padding: 16px 18px;

    border-bottom: 1px solid #eef0f3;

    font-size: 12px;

    color: #344054;

    vertical-align: middle;
}


.destinations-table tbody tr {

    transition: background 0.15s;
}


.destinations-table tbody tr:hover {

    background: #fafcff;
}


.destinations-table tbody tr:last-child td {

    border-bottom: none;
}


/* =========================================================
   ID
   ========================================================= */

.destination-id {

    color: #98a2b3;

    font-weight: 600;
}


/* =========================================================
   NAME
   ========================================================= */

.destination-name {

    font-weight: 700;

    font-size: 13px;

    color: #172033;
}


/* =========================================================
   LOCATION
   ========================================================= */

.destination-location {

    display: flex;

    align-items: center;

    gap: 6px;

    color: #667085;

    font-size: 12px;
}


.location-icon {

    color: #2563eb;

    font-size: 9px;
}


/* =========================================================
   DESCRIPTION
   ========================================================= */

.destination-description {

    max-width: 440px;

    line-height: 1.5;

    color: #667085;
}


/* =========================================================
   STATUS
   ========================================================= */

.status-badge {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 5px 10px;

    border-radius: 20px;

    font-size: 10px;

    font-weight: 700;
}


.status-dot {

    width: 6px;

    height: 6px;

    border-radius: 50%;
}


.status-active {

    background: #ecfdf3;

    color: #15803d;
}


.status-active .status-dot {

    background: #22c55e;
}


.status-inactive {

    background: #fff1f2;

    color: #b91c1c;
}


.status-inactive .status-dot {

    background: #ef4444;
}


/* =========================================================
   ACTION BUTTONS
   ========================================================= */

.action-buttons {

    display: flex;

    align-items: center;

    gap: 7px;
}


.action-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 7px 11px;

    border-radius: 5px;

    text-decoration: none;

    font-size: 11px;

    font-weight: 600;

    transition: 0.15s;
}


.edit-btn {

    background: #eff6ff;

    color: #2563eb;
}


.edit-btn:hover {

    background: #dbeafe;

    color: #1d4ed8;
}


.delete-btn {

    background: #fff1f2;

    color: #dc2626;
}


.delete-btn:hover {

    background: #fee2e2;

    color: #b91c1c;
}


/* =========================================================
   EMPTY STATE
   ========================================================= */

.empty-state {

    text-align: center;

    padding: 65px 20px;
}


.empty-state-icon {

    width: 55px;

    height: 55px;

    margin: 0 auto 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eff6ff;

    border-radius: 50%;

    color: #2563eb;

    font-size: 22px;
}


.empty-state h3 {

    margin: 0 0 7px;

    font-size: 16px;

    color: #344054;
}


.empty-state p {

    margin: 0 0 18px;

    color: #98a2b3;

    font-size: 12px;
}


/* =========================================================
   CARD FOOTER
   ========================================================= */

.destination-card-footer {

    padding: 13px 20px;

    background: #fafbfc;

    border-top: 1px solid #edf0f4;

    color: #8a94a6;

    font-size: 11px;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 900px) {

    .destination-stats {

        grid-template-columns: 1fr;
    }

    .destination-card-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .search-area {

        width: 100%;
    }

    .search-box {

        flex: 1;
    }

    .search-box input {

        width: 100%;
    }
}


@media (max-width: 600px) {

    .destinations-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }

    .add-destination-btn {

        width: 100%;

        justify-content: center;
    }

    .search-area {

        flex-wrap: wrap;
    }
}

</style>


<!-- =========================================================
     DESTINATIONS PAGE
     ========================================================= -->

<div class="destinations-page">


    <!-- PAGE HEADER -->

    <div class="destinations-header">

        <div class="page-heading">

            <h1>
                Destinations
            </h1>

            <p>
                Manage and organize destinations available
                in your travel system.
            </p>

        </div>


        <a
            href="/tour-travel/admin/destinations/add.php"
            class="add-destination-btn"
        >

            <span class="add-icon">
                +
            </span>

            Add Destination

        </a>

    </div>


    <!-- =====================================================
         STATISTICS
         ===================================================== -->

    <div class="destination-stats">


        <!-- TOTAL -->

        <div class="destination-stat-card">

            <div>

                <div class="stat-label">
                    Total Destinations
                </div>

                <div class="stat-number">
                    <?php
                    echo $total_destinations;
                    ?>
                </div>

            </div>

            <div class="stat-icon total-icon">
                ◆
            </div>

        </div>


        <!-- ACTIVE -->

        <div class="destination-stat-card">

            <div>

                <div class="stat-label">
                    Active Destinations
                </div>

                <div class="stat-number">
                    <?php
                    echo $active_destinations;
                    ?>
                </div>

            </div>

            <div class="stat-icon active-icon">
                ✓
            </div>

        </div>


        <!-- INACTIVE -->

        <div class="destination-stat-card">

            <div>

                <div class="stat-label">
                    Inactive Destinations
                </div>

                <div class="stat-number">
                    <?php
                    echo $inactive_destinations;
                    ?>
                </div>

            </div>

            <div class="stat-icon inactive-icon">
                !
            </div>

        </div>


    </div>


    <!-- =====================================================
         DESTINATION TABLE CARD
         ===================================================== -->

    <div class="destination-card">


        <!-- CARD HEADER -->

        <div class="destination-card-header">


            <div>

                <div class="card-title">
                    All Destinations
                </div>

                <div class="card-subtitle">
                    View and manage registered travel destinations
                </div>

            </div>


            <!-- SEARCH -->

            <form
                method="GET"
                class="search-area"
            >


                <div class="search-box">

                    <input
                        type="text"
                        name="search"
                        placeholder="Search destination..."
                        value="<?php
                        echo htmlspecialchars($search);
                        ?>"
                    >

                </div>


                <button
                    type="submit"
                    class="search-btn"
                >
                    Search
                </button>


                <?php if ($search !== ''): ?>

                    <a
                        href="/tour-travel/admin/destinations/index.php"
                        class="clear-btn"
                    >
                        Clear
                    </a>

                <?php endif; ?>


            </form>


        </div>


        <!-- =================================================
             TABLE
             ================================================= -->

        <div class="table-container">


            <?php if (
                $destinations &&
                $destinations->num_rows > 0
            ): ?>


                <table class="destinations-table">


                    <thead>

                        <tr>

                            <th>
                                ID
                            </th>

                            <th>
                                Destination
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php while (
                        $destination =
                        $destinations->fetch_assoc()
                    ): ?>


                        <?php

                        /*
                         * Database status:
                         * 1 = Active
                         * 0 = Inactive
                         */

                        $is_active =
                            ((int)$destination['status'] === 1);

                        ?>


                        <tr>


                            <!-- ID -->

                            <td>

                                <span class="destination-id">

                                    #

                                    <?php
                                    echo htmlspecialchars(
                                        $destination['id']
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- NAME -->

                            <td>

                                <div class="destination-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $destination['name']
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- LOCATION -->

                            <td>

                                <div class="destination-location">

                                    <span class="location-icon">
                                        ●
                                    </span>

                                    <?php
                                    echo htmlspecialchars(
                                        $destination['location']
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- DESCRIPTION -->

                            <td>

                                <div class="destination-description">

                                    <?php

                                    $description =
                                        $destination['description']
                                        ?? '';

                                    if (
                                        strlen($description) > 120
                                    ) {

                                        echo htmlspecialchars(
                                            substr(
                                                $description,
                                                0,
                                                120
                                            )
                                        );

                                        echo "...";

                                    } else {

                                        echo htmlspecialchars(
                                            $description
                                        );
                                    }

                                    ?>

                                </div>

                            </td>


                            <!-- STATUS -->

                            <td>


                                <?php if ($is_active): ?>

                                    <span
                                        class="status-badge status-active"
                                    >

                                        <span class="status-dot"></span>

                                        Active

                                    </span>

                                <?php else: ?>

                                    <span
                                        class="status-badge status-inactive"
                                    >

                                        <span class="status-dot"></span>

                                        Inactive

                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div class="action-buttons">


                                    <a
                                        href="/tour-travel/admin/destinations/edit.php?id=<?php echo urlencode($destination['id']); ?>"
                                        class="action-btn edit-btn"
                                    >
                                        Edit
                                    </a>


                                    <a
                                        href="/tour-travel/admin/destinations/delete.php?id=<?php echo urlencode($destination['id']); ?>"
                                        class="action-btn delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this destination?');"
                                    >
                                        Delete
                                    </a>


                                </div>

                            </td>


                        </tr>


                    <?php endwhile; ?>


                    </tbody>


                </table>


            <?php else: ?>


                <!-- EMPTY STATE -->

                <div class="empty-state">


                    <div class="empty-state-icon">
                        ◆
                    </div>


                    <h3>
                        No Destinations Found
                    </h3>


                    <p>

                        <?php if ($search !== ''): ?>

                            No destinations match
                            "<?php
                            echo htmlspecialchars($search);
                            ?>".

                        <?php else: ?>

                            Start by adding your first
                            travel destination.

                        <?php endif; ?>

                    </p>


                    <?php if ($search === ''): ?>

                        <a
                            href="/tour-travel/admin/destinations/add.php"
                            class="add-destination-btn"
                        >

                            <span class="add-icon">
                                +
                            </span>

                            Add Destination

                        </a>

                    <?php endif; ?>


                </div>


            <?php endif; ?>


        </div>


        <!-- =================================================
             CARD FOOTER
             ================================================= -->

        <div class="destination-card-footer">

            Showing

            <strong>
                <?php
                echo $destinations
                    ? $destinations->num_rows
                    : 0;
                ?>
            </strong>

            destination(s)

            <?php if ($search !== ''): ?>

                matching your search.

            <?php else: ?>

                currently registered in the system.

            <?php endif; ?>

        </div>


    </div>


</div>


<?php

/* =========================================================
   ADMIN FOOTER
   ========================================================= */

require_once "../../includes/admin_footer.php";

?>