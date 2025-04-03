<?php
session_start();
include "../db/config.php";

// Redirect if not logged in or not an admin
if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ajax"])) {
    $name = trim(htmlspecialchars($_POST["name"]));
    $email = trim(htmlspecialchars($_POST["email"]));
    $password = $_POST["password"];
    $role = "student"; // Only students can be registered  

    if (empty($name) || empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format."]);
        exit();
    }

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already in use."]);
        exit();
    }

    $check_stmt->close();

    // Password validation
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
        echo json_encode(["status" => "success", "message" => "Student registered successfully!"]);
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
    <title>Admin - Register Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                    messageDiv.className = "text-sm font-bold p-2 rounded " + (data.status === "success" ? "text-green-500" : "text-red-500");
                })
                .catch(error => console.error("Error:", error));
        }
    </script>
</head>

<body class="flex items-center justify-center h-screen bg-gray-900 text-white">
    <div class="bg-gray-800 p-6 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-semibold text-center mb-4">Register Student</h2>
        <div id="message" class="text-center mb-3"></div>
        <form id="registerForm" onsubmit="registerUser(event)" class="space-y-4">
            <input type="hidden" name="ajax" value="1">
            <input type="text" name="name" placeholder="Username" required class="w-full p-2 rounded bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">
            <input type="email" name="email" placeholder="Email" required class="w-full p-2 rounded bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">
            <input type="password" name="password" placeholder="Password" required class="w-full p-2 rounded bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-yellow-400">
            <button type="submit" class="w-full bg-yellow-400 text-gray-900 font-bold py-2 rounded hover:bg-yellow-500 transition">Register</button>
            <a href="../adminDashboard/admin_dashboard.php" class="block text-center text-yellow-400 mt-2">Back to Dashboard</a>
        </form>
    </div>
</body>

</html>