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

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $location = trim($_POST["location"]);
    $description = trim($_POST["description"]);

    if ($name === "" || $location === "") {
        $message = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO destinations (name, location, description)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "sss",
            $name,
            $location,
            $description
        );

        if ($stmt->execute()) {
            header("Location: index.php");
            exit();
        } else {
            $message = "Error adding destination.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Destination</title>
</head>
<body>

<h2>Add New Destination</h2>

<?php if ($message !== ""): ?>
    <p style="color:red;">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

<form method="POST">

    <label>Destination Name:</label><br>
    <input type="text" name="name" required>
    <br><br>

    <label>Location:</label><br>
    <input type="text" name="location" required>
    <br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="5"></textarea>
    <br><br>

    <button type="submit">Save Destination</button>

</form>

<br>

<a href="index.php">Back to Destinations</a>
<?php require_once "../../includes/footer.php";    ?>

</body>
</html>