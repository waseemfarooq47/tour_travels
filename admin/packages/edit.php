
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../config/database.php";

/* Admin authentication */
if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: /tour-travel/auth/login.php");
    exit();
}

/* Validate package ID */
if (
    !isset($_GET["id"]) ||
    !ctype_digit((string) $_GET["id"]) ||
    (int) $_GET["id"] <= 0
) {
    header("Location: index.php");
    exit();
}

$id = (int) $_GET["id"];

$message = "";
$destination_id = 0;
$package_name = "";
$description = "";
$duration = "";
$price = "";
$status = 1;

/* Fetch existing package */
$stmt = $conn->prepare(
    "SELECT * FROM tour_packages WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$package = $result->fetch_assoc();
$stmt->close();

if (!$package) {
    header("Location: index.php");
    exit();
}

/* Initialize form with existing values */
$destination_id = (int) $package["destination_id"];
$package_name = $package["package_name"];
$description = $package["description"] ?? "";
$duration = (string) $package["duration"];
$price = (string) $package["price"];
$status = (int) $package["status"];

/* Fetch active destinations, including the current one
   even if it has subsequently become inactive */
$destination_result = $conn->query(
    "SELECT id, name, location, status
     FROM destinations
     WHERE status = 1 OR id = " . $destination_id . "
     ORDER BY name ASC"
);

if (!$destination_result) {
    die("Unable to load destinations. Please try again.");
}

/* Process update */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $destination_id = (int) ($_POST["destination_id"] ?? 0);
    $package_name = trim($_POST["package_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $duration = trim($_POST["duration"] ?? "");
    $price = trim($_POST["price"] ?? "");
    $status_input = $_POST["status"] ?? "";

    if (
        $destination_id <= 0 ||
        $package_name === "" ||
        !ctype_digit($duration) ||
        (int) $duration <= 0 ||
        !is_numeric($price) ||
        (float) $price < 0 ||
        !in_array($status_input, ["0", "1"], true)
    ) {
        $message = "Please complete all required fields with valid information.";
    } else {

        /* Validate selected destination */
        $check = $conn->prepare(
            "SELECT id FROM destinations
             WHERE id = ? AND (status = 1 OR id = ?)"
        );

        $check->bind_param("ii", $destination_id, $package["destination_id"]);
        $check->execute();

        $destination_exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$destination_exists) {
            $message = "Please select a valid destination.";
        } else {

            $duration_value = (int) $duration;
            $price_value = (float) $price;
            $status = (int) $status_input;

            $update = $conn->prepare(
                "UPDATE tour_packages
                 SET destination_id = ?,
                     package_name = ?,
                     description = ?,
                     duration = ?,
                     price = ?,
                     status = ?
                 WHERE id = ?"
            );

            if ($update) {

                $update->bind_param(
                    "issidii",
                    $destination_id,
                    $package_name,
                    $description,
                    $duration_value,
                    $price_value,
                    $status,
                    $id
                );

                if ($update->execute()) {
                    $update->close();

                    header("Location: index.php?updated=1");
                    exit();
                } else {
                    $message = "Unable to update the package. Please try again.";
                    $update->close();
                }

            } else {
                $message = "Unable to prepare the database query.";
            }
        }
    }
}

require_once "../../includes/admin_header.php";
?>

<style>
.package-page {
    max-width: 1100px;
    margin: 0 auto;
    padding-bottom: 35px;
}

.package-heading {
    margin-bottom: 24px;
}

.package-heading h1 {
    margin: 0 0 8px;
    color: #172033;
    font-size: 28px;
    font-weight: 700;
}

.package-heading p {
    margin: 0;
    color: #7a8496;
    font-size: 13px;
    line-height: 1.7;
}

.package-card {
    background: #fff;
    border: 1px solid #e7eaf0;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(15, 23, 42, .04);
    overflow: hidden;
}

.package-card-header {
    padding: 22px 26px;
    border-bottom: 1px solid #edf0f4;
}

.package-card-header h2 {
    margin: 0 0 6px;
    color: #172033;
    font-size: 17px;
}

.package-card-header p {
    margin: 0;
    color: #8490a3;
    font-size: 12px;
    line-height: 1.6;
}

.package-form {
    padding: 26px;
}

.package-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 22px;
}

.package-field {
    min-width: 0;
    margin-bottom: 23px;
}

.package-field.full-width {
    grid-column: 1 / -1;
}

.package-field label {
    display: block;
    margin-bottom: 9px;
    color: #344054;
    font-size: 13px;
    font-weight: 600;
}

.package-required {
    color: #dc2626;
}

.package-control {
    display: block;
    width: 100%;
    box-sizing: border-box;
    padding: 12px 14px;
    border: 1px solid #d9dee7;
    border-radius: 7px;
    color: #172033;
    background: #fff;
    font-family: inherit;
    font-size: 13px;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}

.package-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .09);
}

.package-control::placeholder {
    color: #a0a8b6;
}

select.package-control {
    cursor: pointer;
}

textarea.package-control {
    min-height: 145px;
    resize: vertical;
    line-height: 1.7;
}

.package-help {
    display: block;
    margin-top: 7px;
    color: #98a2b3;
    font-size: 11px;
    line-height: 1.6;
}

.package-error {
    margin: 24px 26px 0;
    padding: 13px 15px;
    border: 1px solid #fecaca;
    border-radius: 7px;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 13px;
    line-height: 1.6;
}

.package-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    padding-top: 22px;
    border-top: 1px solid #edf0f4;
}

.package-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 19px;
    border: none;
    border-radius: 7px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .2s, color .2s;
}

.package-save {
    background: #2563eb;
    color: #fff;
}

.package-save:hover {
    background: #1d4ed8;
    color: #fff;
}

.package-cancel {
    background: #f3f4f6;
    color: #475467;
}

.package-cancel:hover {
    background: #e5e7eb;
    color: #172033;
}

.package-note {
    margin: 18px 0 0;
    color: #8490a3;
    font-size: 11px;
    line-height: 1.6;
}

@media (max-width: 650px) {
    .package-heading h1 {
        font-size: 23px;
    }

    .package-form-grid {
        grid-template-columns: 1fr;
    }

    .package-field.full-width {
        grid-column: auto;
    }

    .package-form,
    .package-card-header {
        padding: 20px;
    }

    .package-error {
        margin: 20px 20px 0;
    }

    .package-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .package-button {
        width: 100%;
        box-sizing: border-box;
    }
}
</style>

<div class="package-page">

    <div class="package-heading">
        <h1>Edit Tour Package</h1>
        <p>
            Update package information, destination, pricing,
            duration, and availability.
        </p>
    </div>

    <div class="package-card">

        <div class="package-card-header">
            <h2>Package Information</h2>
            <p>
                Modify the details below and save your changes.
                Fields marked with
                <span class="package-required">*</span> are required.
            </p>
        </div>

        <?php if ($message !== ""): ?>
            <div class="package-error" role="alert">
                <strong>Please check:</strong>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="package-form">

            <div class="package-form-grid">

                <div class="package-field full-width">
                    <label for="destination_id">
                        Destination
                        <span class="package-required">*</span>
                    </label>

                    <select
                        name="destination_id"
                        id="destination_id"
                        class="package-control"
                        required
                    >
                        <option value="">Select a destination</option>

                        <?php while (
                            $destination = $destination_result->fetch_assoc()
                        ): ?>
                            <option
                                value="<?php echo (int) $destination["id"]; ?>"
                                <?php echo
                                    $destination_id == $destination["id"]
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                <?php
                                echo htmlspecialchars(
                                    $destination["name"] .
                                    " — " . $destination["location"]
                                );

                                if ((int) $destination["status"] === 0) {
                                    echo " (Inactive)";
                                }
                                ?>
                            </option>
                        <?php endwhile; ?>

                    </select>

                    <span class="package-help">
                        Select the destination associated with this package.
                    </span>
                </div>

                <div class="package-field full-width">
                    <label for="package_name">
                        Package Name
                        <span class="package-required">*</span>
                    </label>

                    <input
                        type="text"
                        name="package_name"
                        id="package_name"
                        class="package-control"
                        placeholder="e.g. Kashmir Summer Escape"
                        value="<?php echo htmlspecialchars($package_name); ?>"
                        maxlength="150"
                        required
                    >
                </div>

                <div class="package-field">
                    <label for="duration">
                        Duration (Days)
                        <span class="package-required">*</span>
                    </label>

                    <input
                        type="number"
                        name="duration"
                        id="duration"
                        class="package-control"
                        placeholder="e.g. 5"
                        value="<?php echo htmlspecialchars($duration); ?>"
                        min="1"
                        step="1"
                        required
                    >

                    <span class="package-help">
                        Enter the total number of days.
                    </span>
                </div>

                <div class="package-field">
                    <label for="price">
                        Package Price (₹)
                        <span class="package-required">*</span>
                    </label>

                    <input
                        type="number"
                        name="price"
                        id="price"
                        class="package-control"
                        placeholder="e.g. 15000"
                        value="<?php echo htmlspecialchars($price); ?>"
                        min="0"
                        step="0.01"
                        required
                    >

                    <span class="package-help">
                        Enter the package price in Indian rupees.
                    </span>
                </div>

                <div class="package-field full-width">
                    <label for="description">
                        Package Description
                    </label>

                    <textarea
                        name="description"
                        id="description"
                        class="package-control"
                        placeholder="Describe the itinerary, activities, accommodation, and attractions..."
                    ><?php echo htmlspecialchars($description); ?></textarea>
                </div>

                <div class="package-field full-width">
                    <label for="status">
                        Package Status
                        <span class="package-required">*</span>
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="package-control"
                        required
                    >
                        <option
                            value="1"
                            <?php echo $status === 1 ? "selected" : ""; ?>
                        >
                            Active — Available to customers
                        </option>

                        <option
                            value="0"
                            <?php echo $status === 0 ? "selected" : ""; ?>
                        >
                            Inactive — Not available to customers
                        </option>
                    </select>

                    <span class="package-help">
                        Inactive packages should be hidden from customer-facing
                        listings if your public pages filter by status.
                    </span>
                </div>

            </div>

            <div class="package-actions">

                <button
                    type="submit"
                    class="package-button package-save"
                >
                    Update Tour Package
                </button>

                <a
                    href="index.php"
                    class="package-button package-cancel"
                >
                    Cancel
                </a>

            </div>

            <p class="package-note">
                Review all changes before updating this package.
            </p>

        </form>

    </div>

</div>

<?php require_once "../../includes/admin_footer.php"; ?>

