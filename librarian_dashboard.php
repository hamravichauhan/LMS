<?php
include "./middleware/authMiddleware.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] !== "librarian" && $_SESSION["user_role"] !== "admin")) {
    header("Location: login.php");
    exit();
}

echo "<h1>Welcome Librarian, " . $_SESSION["user_name"] . "!</h1>";
