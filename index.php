<?php

require_once "config/database.php";

$sql = "SELECT id, name, email, role FROM users";

$result = $conn->query($sql);

if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        echo "ID: " . $row["id"] . "<br>";
        echo "Name: " . $row["name"] . "<br>";
        echo "Email: " . $row["email"] . "<br>";
        echo "Role: " . $row["role"] . "<br>";

        echo "<hr>";
    }

} else {
    echo "No users found.";
}

?>