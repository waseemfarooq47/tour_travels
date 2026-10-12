<?php
session_start();

require_once "../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    $_SESSION["user_role"] !== "customer"
) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../includes/header.php";

$user_id = $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT
        bookings.*,
        tour_packages.package_name
     FROM bookings
     INNER JOIN tour_packages
        ON bookings.package_id = tour_packages.id
     WHERE bookings.user_id = ?
     ORDER BY bookings.id DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bookings</title>
</head>
<body>

<h2>My Bookings</h2>

<a href="dashboard.php">Dashboard</a> |
<a href="packages.php">Available Packages</a> |
<a href="../auth/logout.php">Logout</a>

<br><br>

<table border="1" cellpadding="10" cellspacing="0">

    <tr>
        <th>ID</th>
        <th>Package</th>
        <th>Travel Date</th>
        <th>Persons</th>
        <th>Total Amount</th>
        <th>Status</th>
        <th>Booking Date</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td>
                <?php echo $row["id"]; ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row["package_name"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row["travel_date"]); ?>
            </td>

            <td>
                <?php echo $row["persons"]; ?>
            </td>

            <td>
                ₹<?php echo number_format($row["total_amount"], 2); ?>
            </td>

            <td>
                <?php echo ucfirst($row["status"]); ?>
            </td>

            <td>
                <?php echo $row["created_at"]; ?>
            </td>
        </tr>

    <?php endwhile; ?>

</table>

<?php
    require_once "../includes/footer.php"
?>

</body>
</html>