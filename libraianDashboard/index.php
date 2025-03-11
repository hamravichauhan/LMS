<?php
// include "../middleware/authMiddleware.php";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is a librarian or admin
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] !== "librarian" && $_SESSION["user_role"] !== "admin")) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }

        .navbar {
            background: #007bff;
        }

        .navbar-brand {
            font-weight: bold;
            color: white;
        }

        .nav-link {
            color: white !important;
            transition: 0.3s;
        }

        .nav-link:hover {
            color: #ddd !important;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 40px auto;
        }

        .dashboard-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .dashboard-menu {
            list-style: none;
            padding: 0;
        }

        .dashboard-menu li {
            margin: 15px 0;
        }

        .dashboard-menu a {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            background: #007bff;
            padding: 12px 20px;
            border-radius: 5px;
            transition: 0.3s;
            text-decoration: none;
        }

        .dashboard-menu a:hover {
            background: #0056b3;
        }

        .dashboard-menu i {
            margin-right: 10px;
        }
    </style>
</head>

<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand">📖 Librarian Dashboard</a>
            <div class="ms-auto">
                <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container dashboard-container">
        <div class="dashboard-card">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</h2>
            <ul class="dashboard-menu">
                <li><a href="books.php"><i class="fas fa-book"></i> Manage Books</a></li>
                <li><a href="userList.php"><i class="fas fa-users"></i> View Users</a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> Manage Reservations</a></li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>