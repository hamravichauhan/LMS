<?php
$host = "localhost";
$user = "root";  // Change for production
$pass = "";      // Change for production
$db   = "auth_system";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
