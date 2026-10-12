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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Bookings</title>
</head>
<body>

<h2>Manage Customer Bookings</h2>

<!-- <a href="../dashboard.php">Dashboard</a> |
<a href="../../auth/logout.php">Logout</a> -->

<br><br>

<table border="1" cellpadding="10" cellspacing="0">

    <tr>
        <th>Booking ID</th>
        <th>Customer</th>
        <th>Email</th>
        <th>Package</th>
        <th>Destination</th>
        <th>Travel Date</th>
        <th>Persons</th>
        <th>Total Amount</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td>
                <?php echo $row["id"]; ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row["customer_name"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row["customer_email"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row["package_name"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row["destination_name"]); ?>
            </td>

            <td>
                <?php echo $row["travel_date"]; ?>
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
                <?php if ($row["status"] === "pending"): ?>

                    <a
                        href="update_status.php?id=<?php echo $row["id"]; ?>&status=confirmed"
                    >
                        Confirm
                    </a>

                    |

                    <a
                        href="update_status.php?id=<?php echo $row["id"]; ?>&status=cancelled"
                        onclick="return confirm('Cancel this booking?');"
                    >
                        Cancel
                    </a>

                <?php else: ?>

                    No action

                <?php endif; ?>
            </td>
        </tr>

    <?php endwhile; ?>

</table>
<?php
require_once "../../includes/footer.php";
?>
</body>

</html>