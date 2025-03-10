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
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            text-align: center;
        }

        .navbar {
            background: #333;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h2 {
            margin: 0;
        }

        .navbar .links {
            display: flex;
            gap: 20px;
            padding-right: 20px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            background: #555;
        }

        .navbar a:hover {
            background: #777;
        }

        .container {
            margin: 40px auto;
            width: 80%;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px #ccc;
        }

        .dashboard-menu {
            list-style: none;
            padding: 0;
        }

        .dashboard-menu li {
            margin: 15px 0;
        }

        .dashboard-menu a {
            display: inline-block;
            text-decoration: none;
            font-size: 18px;
            color: white;
            background: #007bff;
            padding: 12px 20px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .dashboard-menu a:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>

    <!-- Navigation Bar -->
    <div class="navbar">
        <h2>Librarian Dashboard</h2>
        <div class="links">
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</h2>
        <ul class="dashboard-menu">
            <li><a href="books.php">📚 Manage Books</a></li>
            <li><a href="users.php">👥 View Users</a></li>
            <li><a href="reservations.php">📅 Manage Reservations</a></li>
        </ul>
    </div>

</body>

</html>