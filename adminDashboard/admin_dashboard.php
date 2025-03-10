<?php
session_start();
include "../middleware/authMiddleware.php";
include "../db/config.php";


// Check if user is an admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch all students
$student_sql = "SELECT id, name, email FROM users WHERE role='student'";
$student_result = $conn->query($student_sql);

// Fetch all librarians (excluding admin)
$librarian_sql = "SELECT id, name, email FROM users WHERE role='librarian'";
$librarian_result = $conn->query($librarian_sql);

// Handle Role Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"])) {
    $user_id = intval($_POST["user_id"]); // Sanitize input
    $update_sql = "UPDATE users SET role='librarian' WHERE id=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?success=1");
        exit();
    } else {
        echo "<p class='error'>Failed to update role.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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

        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0px 0px 10px #ccc;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #333;
            color: white;
        }

        .assign-btn {
            background: green;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .assign-btn:hover {
            background: darkgreen;
        }

        .success-message {
            color: green;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- Navigation Bar -->
    <div class="navbar">
        <h2>Admin Dashboard</h2>
        <div class="links">
            <a href="../studentDashboard/student_dashboard.php">User Dashboard</a>
            <a href="../libraianDashboard/index.php">Librarian Dashboard</a>
            <a href="../auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <h2>Welcome Admin, <?php echo htmlspecialchars($_SESSION["user_name"]); ?></h2>

    <!-- Assign Librarian Role -->
    <h3>Assign Librarian Role</h3>
    <?php if (isset($_GET["success"])): ?>
        <p class="success-message">User promoted to Librarian!</p>
    <?php endif; ?>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Assign Librarian</th>
        </tr>
        <?php while ($student = $student_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($student["name"]); ?></td>
                <td><?php echo htmlspecialchars($student["email"]); ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="user_id" value="<?php echo $student["id"]; ?>">
                        <button type="submit" class="assign-btn">Make Librarian</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- List of Librarians -->
    <h3>List of Librarians</h3>
    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
        </tr>
        <?php while ($librarian = $librarian_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($librarian["name"]); ?></td>
                <td><?php echo htmlspecialchars($librarian["email"]); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

</body>

</html>