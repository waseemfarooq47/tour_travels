<?php
session_start();

require_once "../../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../../auth/login.php");
    exit();
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid package ID.");
}

$id = (int) $_GET["id"];

$stmt = $conn->prepare(
    "SELECT * FROM tour_packages WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$package = $result->fetch_assoc();

if (!$package) {
    die("Package not found.");
}

$destination_result = $conn->query(
    "SELECT id, name FROM destinations
     WHERE status = 1
     ORDER BY name ASC"
);

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $destination_id = (int) $_POST["destination_id"];
    $package_name = trim($_POST["package_name"]);
    $description = trim($_POST["description"]);
    $duration = (int) $_POST["duration"];
    $price = (float) $_POST["price"];
    $status = (int) $_POST["status"];

    if (
        $destination_id <= 0 ||
        $package_name === "" ||
        $duration <= 0 ||
        $price < 0
    ) {
        $message = "Please enter valid package details.";
    } else {
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

        $update->bind_param(
            "issidii",
            $destination_id,
            $package_name,
            $description,
            $duration,
            $price,
            $status,
            $id
        );

        if ($update->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $message = "Error updating package.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Tour Package</title>
</head>
<body>

<h2>Edit Tour Package</h2>

<?php if ($message !== ""): ?>
    <p style="color:red;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<form method="POST">

    <label>Destination:</label><br>

    <select name="destination_id" required>

        <?php while ($destination = $destination_result->fetch_assoc()): ?>

            <option
                value="<?php echo $destination["id"]; ?>"
                <?php
                echo $destination["id"] == $package["destination_id"]
                    ? "selected"
                    : "";
                ?>
            >
                <?php echo htmlspecialchars($destination["name"]); ?>
            </option>

        <?php endwhile; ?>

    </select>

    <br><br>

    <label>Package Name:</label><br>

    <input
        type="text"
        name="package_name"
        value="<?php echo htmlspecialchars($package["package_name"]); ?>"
        required
    >

    <br><br>

    <label>Description:</label><br>

    <textarea name="description" rows="5"><?php
        echo htmlspecialchars($package["description"]);
    ?></textarea>

    <br><br>

    <label>Duration in Days:</label><br>

    <input
        type="number"
        name="duration"
        min="1"
        value="<?php echo $package["duration"]; ?>"
        required
    >

    <br><br>

    <label>Price:</label><br>

    <input
        type="number"
        name="price"
        min="0"
        step="0.01"
        value="<?php echo $package["price"]; ?>"
        required
    >

    <br><br>

    <label>Status:</label><br>

    <select name="status">

        <option
            value="1"
            <?php echo $package["status"] == 1 ? "selected" : ""; ?>
        >
            Active
        </option>

        <option
            value="0"
            <?php echo $package["status"] == 0 ? "selected" : ""; ?>
        >
            Inactive
        </option>

    </select>

    <br><br>

    <button type="submit">Update Package</button>

</form>

<br>

<a href="index.php">Back to Packages</a>

</body>
</html>