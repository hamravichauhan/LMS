<?php
session_start();
include "../db/config.php";

// Hardcoded Admin Credentials for admin login
$admin_email = "admin@library.com";
$admin_password = password_hash("Admin@123", PASSWORD_BCRYPT);

// Redirect logged-in users based on role
if (isset($_SESSION["user_id"])) {
    if ($_SESSION["user_role"] === "admin") {
        header("Location: ../adminDashboard/admin_dashboard.php");
    } elseif ($_SESSION["user_role"] === "librarian") {
        header("Location: ../libraianDashboard/index.php");
    } else {
        header("Location: ../studentDashboard/index.php");
    }
    exit();
}

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Check if it's the admin
    if ($email === $admin_email && password_verify($password, $admin_password)) {
        $_SESSION["user_id"] = 999; // Fake ID for session
        $_SESSION["user_name"] = "Admin";
        $_SESSION["user_role"] = "admin";
        header("Location: ../adminDashboard/admin_dashboard.php");
        exit();
    }

    // Otherwise, check database for normal users
    $sql = "SELECT id, name, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Check if user exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password, $role);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $id;
            $_SESSION["user_name"] = $name;
            $_SESSION["user_role"] = $role;

            // Redirect based on role
            if ($role == "librarian") {
                header("Location: ../libraianDashboard/index.php");
            } else {
                header("Location: ../studentDashboard/index.php");
            }
            exit();
        } else {
            $message = "<span class='error'>Invalid email or password</span>";
        }
    } else {
        $message = "<span class='error'>Invalid email or password</span>";
    }

    // Close statement
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: black;
            font-family: Arial, sans-serif;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.2);
            text-align: center;
            width: 320px;
        }

        h2 {
            color: #fff;
            margin-bottom: 15px;
        }

        input {
            width: 90%;
            padding: 10px;
            margin: 8px 0;
            border: none;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            outline: none;
        }

        input:focus {
            background: rgba(255, 255, 255, 0.4);
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        button {
            width: 95%;
            padding: 10px;
            background: #ffcc00;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #121212;
            margin-top: 10px;
            transition: 0.3s;
        }

        button:hover {
            background: #e6b800;
        }

        #registerLink {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            margin-top: 10px;
            display: block;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (isset($message)): ?>
            <div class="error"><?= $message ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
            <a id="registerLink" href="reset-password.php">Forgot password?</a>
        </form>
    </div>
</body>

</html>