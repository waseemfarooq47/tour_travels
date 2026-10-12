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

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"]) ||
    !isset($_GET["status"])
) {
    die("Invalid booking information.");
}

$id = (int) $_GET["id"];
$status = $_GET["status"];

$allowed_statuses = ["confirmed", "cancelled"];

if (!in_array($status, $allowed_statuses, true)) {
    die("Invalid booking status.");
}

$stmt = $conn->prepare(
    "UPDATE bookings
     SET status = ?
     WHERE id = ? AND status = 'pending'"
);

$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    header("Location: index.php");
    exit();
} else {
    echo "Error updating booking status.";
}
?>