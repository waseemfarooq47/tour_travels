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

require_once "../../includes/header.php";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid destination ID.");
}

$id = (int) $_GET["id"];

$stmt = $conn->prepare(
    "SELECT * FROM destinations WHERE id = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$destination = $result->fetch_assoc();

if (!$destination) {
    die("Destination not found.");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $location = trim($_POST["location"]);
    $description = trim($_POST["description"]);
    $status = (int) $_POST["status"];

    if ($name === "" || $location === "") {
        $message = "Please fill in all required fields.";
    } else {
        $update = $conn->prepare(
            "UPDATE destinations
             SET name = ?, location = ?, description = ?, status = ?
             WHERE id = ?"
        );

        $update->bind_param(
            "sssii",
            $name,
            $location,
            $description,
            $status,
            $id
        );

        if ($update->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $message = "Error updating destination.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Destination</title>
</head>
<body>

<h2>Edit Destination</h2>

<?php if ($message !== ""): ?>
    <p style="color:red;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<form method="POST">

    <label>Destination Name:</label><br>
    <input
        type="text"
        name="name"
        value="<?php echo htmlspecialchars($destination["name"]); ?>"
        required
    >
    <br><br>

    <label>Location:</label><br>
    <input
        type="text"
        name="location"
        value="<?php echo htmlspecialchars($destination["location"]); ?>"
        required
    >
    <br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="5"><?php
        echo htmlspecialchars($destination["description"]);
    ?></textarea>
    <br><br>

    <label>Status:</label><br>

    <select name="status">
        <option value="1"
            <?php echo $destination["status"] == 1 ? "selected" : ""; ?>>
            Active
        </option>

        <option value="0"
            <?php echo $destination["status"] == 0 ? "selected" : ""; ?>>
            Inactive
        </option>
    </select>

    <br><br>

    <button type="submit">Update Destination</button>

</form>

<br>

<a href="index.php">Back to Destinations</a>

<?php
    require_once "../../includes/footer.php";
?>

</body>
</html>