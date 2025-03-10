<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_page = basename($_SERVER['SCRIPT_NAME']); // Get current page name
$user_role = $_SESSION["user_role"] ?? "unknown"; // Handle undefined roles

switch ($current_page) {
    case "admin_dashboard.php":
        if ($user_role !== "admin") {
            header("Location: student_dashboard.php");
            exit();
        }
        break;

    case "./libraianDashboard/index.php":
        // Admins can access the librarian dashboard
        if ($user_role !== "librarian" && $user_role !== "admin") {
            header("Location: student_dashboard.php");
            exit();
        }
        break;

    case "student_dashboard.php":
        // Admins can access the student dashboard
        if ($user_role !== "student" && $user_role !== "admin") {
            header("Location: login.php");
            exit();
        }
        break;

    case "login.php":
    case "register.php":
        // Prevent admin from accessing login or register pages
        if ($user_role === "admin") {
            header("Location: admin_dashboard.php");
            exit();
        }
        break;

    default:
        // Redirect users with unknown roles to login
        header("Location: login.php");
        exit();
}
