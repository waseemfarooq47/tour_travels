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

require_once "../../includes/header.php"



if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid destination ID.");
}

$id = (int) $_GET["id"];

$stmt = $conn->prepare(
    "DELETE FROM destinations WHERE id = ?"
);

$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php");
    exit();
} else {
    echo "Error deleting destination.";
}
?>