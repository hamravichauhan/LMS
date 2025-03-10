<?php
session_start();
include "../db/config.php";

if (isset($_SESSION["user_id"])) {
    $redirect = $_SESSION["user_role"] === "admin" ? "../adminDashboard/admin_dashboard.php"
        : ($_SESSION["user_role"] === "librarian" ? "../libraianDashboard/index.php" : "../studentDashboard/student_dashboard.php");
    header("Location: $redirect");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ajax"])) {
    $name = trim(htmlspecialchars($_POST["name"]));
    $email = trim(htmlspecialchars($_POST["email"]));
    $password = $_POST["password"];
    $role = "student"; // Default role

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format."]);
        exit();
    }

    // Check if name or email already exists
    $check_stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already in use."]);
        exit();
    }

    $check_stmt->close();

    // Password validation (8+ characters, uppercase, lowercase, number, special character)
    if (
        strlen($password) < 8 || !preg_match("/[A-Z]/", $password) ||
        !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) ||
        !preg_match("/[\W]/", $password)
    ) {
        echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters long and include an uppercase letter, lowercase letter, number, and special character."]);
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Registration successful! Redirecting..."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Registration failed. Please try again."]);
    }

    $stmt->close();
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: #1e1e1e;
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

        .message {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .success {
            color: #00ff00;
        }

        .error {
            color: #ff4d4d;
        }

        input,
        button {
            width: 90%;
            padding: 10px;
            margin: 8px 0;
            border: none;
            border-radius: 5px;
        }

        input {
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
            background: #ffcc00;
            font-size: 16px;
            font-weight: bold;
            color: #121212;
            margin-top: 10px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #e6b800;
        }

        #loginLink {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            margin-top: 10px;
            display: block;
        }
    </style>
    <script>
        function registerUser(event) {
            event.preventDefault();
            let formData = new FormData(document.getElementById("registerForm"));

            fetch("register.php", {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    let messageDiv = document.getElementById("message");
                    messageDiv.innerHTML = data.message;
                    messageDiv.className = "message " + (data.status === "success" ? "success" : "error");

                    if (data.status === "success") {
                        setTimeout(() => window.location.href = "login.php", 2000);
                    }
                })
                .catch(error => console.error("Error:", error));
        }
    </script>
</head>

<body>
    <div class="container">
        <h2>Register</h2>
        <div id="message"></div>
        <form id="registerForm" onsubmit="registerUser(event)">
            <input type="hidden" name="ajax" value="1">
            <input type="text" name="name" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
            <a id="loginLink" href="login.php">Already have an account? Login</a>
        </form>
    </div>
</body>

</html>