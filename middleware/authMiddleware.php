<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php"); // Update this path
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

    case "index.php": // Librarian Dashboard
        if ($user_role !== "librarian" && $user_role !== "admin") {
            header("Location: student_dashboard.php");
            exit();
        }
        break;

    case "student_dashboard.php":
        if ($user_role !== "student" && $user_role !== "admin") {
            header("Location: ../auth/login.php"); // Update this path
            exit();
        }
        break;

    case "register.php":
        // Ensure only admins can access register page
        if ($user_role !== "admin") {
            header("Location: admin_dashboard.php");
            exit();
        }
        break;

    case "login.php":
        // Prevent logged-in admins from seeing login
        if ($user_role === "admin") {
            header("Location: admin_dashboard.php");
            exit();
        }
        break;

    default:
        // Redirect users with unknown roles
        header("Location: ../auth/login.php"); // Update this path
        exit();
}
