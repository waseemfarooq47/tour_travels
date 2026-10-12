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

$query = "
    SELECT
        tour_packages.*,
        destinations.name AS destination_name
    FROM tour_packages
    INNER JOIN destinations
        ON tour_packages.destination_id = destinations.id
    ORDER BY tour_packages.id DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Tour Packages</title>
</head>
<body>

<h2>Manage Tour Packages</h2>

<a href="add.php">Add New Tour Package</a>

<br><br>

<table border="1" cellpadding="10" cellspacing="0">

    <tr>
        <th>ID</th>
        <th>Destination</th>
        <th>Package Name</th>
        <th>Duration</th>
        <th>Price</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td>
                <?php echo $row["id"]; ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row["destination_name"]); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($row["package_name"]); ?>
            </td>

            <td>
                <?php echo $row["duration"]; ?> days
            </td>

            <td>
                ₹<?php echo number_format($row["price"], 2); ?>
            </td>

            <td>
                <?php
                echo $row["status"] == 1
                    ? "Active"
                    : "Inactive";
                ?>
            </td>

            <td>
                <a href="edit.php?id=<?php echo $row["id"]; ?>">
                    Edit
                </a>

                |

                <a
                    href="delete.php?id=<?php echo $row["id"]; ?>"
                    onclick="return confirm('Are you sure you want to delete this package?');"
                >
                    Delete
                </a>
            </td>
        </tr>

    <?php endwhile; ?>

</table>

<br>

<a href="../dashboard.php">Back to Dashboard</a>

</body>
</html>