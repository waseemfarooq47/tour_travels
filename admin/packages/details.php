
<?php

session_start();

require_once "../../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../../auth/login.php");
    exit();
}

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"])) {
    die("Invalid package ID.");
}

$package_id = (int) $_GET["id"];

$stmt = $conn->prepare(
    "SELECT
        p.*,
        d.name AS destination_name,
        d.location
     FROM packages p
     INNER JOIN destinations d
        ON p.destination_id = d.id
     WHERE p.id = ?"
);

$stmt->bind_param("i", $package_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    die("Package not found.");
}

$package = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <title>Package Details</title>

</head>

<body>

<h1>
    <?php echo htmlspecialchars($package["package_name"]); ?>
</h1>

<p>
    <strong>Destination:</strong>
    <?php
    echo htmlspecialchars(
        $package["destination_name"] . ", " . $package["location"]
    );
    ?>
</p>

<p>
    <strong>Duration:</strong>
    <?php
    echo $package["duration_days"] . " Days / " .
         $package["duration_nights"] . " Nights";
    ?>
</p>

<p>
    <strong>Price per Person:</strong>
    ₹<?php echo number_format($package["price"], 2); ?>
</p>

<p>
    <strong>Maximum People:</strong>
    <?php echo $package["max_people"]; ?>
</p>

<p>
    <strong>Available Seats:</strong>
    <?php echo $package["available_seats"]; ?>
</p>

<p>
    <strong>Status:</strong>
    <?php echo $package["status"] == 1 ? "Active" : "Inactive"; ?>
</p>

<h2>Description</h2>

<p>
    <?php
    echo nl2br(htmlspecialchars($package["description"]));
    ?>
</p>

<br>

<a href="index.php">Back to Packages</a>

</body>

</html>