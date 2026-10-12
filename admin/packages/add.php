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

$message = "";

$destination_result = $conn->query(
    "SELECT id, name FROM destinations
     WHERE status = 1
     ORDER BY name ASC"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $destination_id = (int) $_POST["destination_id"];
    $package_name = trim($_POST["package_name"]);
    $description = trim($_POST["description"]);
    $duration = (int) $_POST["duration"];
    $price = (float) $_POST["price"];

    if (
        $destination_id <= 0 ||
        $package_name === "" ||
        $duration <= 0 ||
        $price < 0
    ) {
        $message = "Please enter valid package details.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO tour_packages
            (destination_id, package_name, description, duration, price)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "issid",
            $destination_id,
            $package_name,
            $description,
            $duration,
            $price
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $message = "Error adding tour package.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Tour Package</title>
</head>
<body>

<h2>Add New Tour Package</h2>

<?php if ($message !== ""): ?>
    <p style="color:red;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<form method="POST">

    <label>Destination:</label><br>

    <select name="destination_id" required>
        <option value="">Select Destination</option>

        <?php while ($destination = $destination_result->fetch_assoc()): ?>
            <option value="<?php echo $destination["id"]; ?>">
                <?php echo htmlspecialchars($destination["name"]); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <br><br>

    <label>Package Name:</label><br>
    <input type="text" name="package_name" required>

    <br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="5"></textarea>

    <br><br>

    <label>Duration in Days:</label><br>
    <input type="number" name="duration" min="1" required>

    <br><br>

    <label>Price:</label><br>
    <input type="number" name="price" min="0" step="0.01" required>

    <br><br>

    <button type="submit">Save Package</button>

</form>

<br>

<a href="index.php">Back to Packages</a>

</body>
</html>