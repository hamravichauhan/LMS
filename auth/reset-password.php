<?php
include "../db/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $token = bin2hex(random_bytes(50));

    // Check if the email exists before updating
    $checkEmail = "SELECT email FROM users WHERE email=?";
    $stmtCheck = $conn->prepare($checkEmail);
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();

    if ($result->num_rows == 0) {
        echo "<script>alert('❌ No account found with that email.');</script>";
    } else {
        // Update token
        $sql = "UPDATE users SET token=? WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $token, $email);

        if ($stmt->execute()) {
            $resetLink = "http://localhost/php/learninng/auth%20php/auth/new-password.php?token=" . urlencode($token);
            echo "<script>
                    alert('✅ Password reset link generated! Redirecting to reset page...');
                    window.location.href = '$resetLink';
                  </script>";
        } else {
            echo "<script>alert('❌ Error updating token: " . $stmt->error . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg black flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96 animate-fadeIn">
        <h2 class="text-3xl font-bold text-center text-gray-800">Reset Password</h2>
        <p class="text-gray-600 text-sm text-center mt-2">Enter your email to receive a reset link.</p>

        <form method="POST" class="mt-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" placeholder="Enter your email"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-all duration-200 shadow-md">
                Send Reset Link
            </button>
        </form>
    </div>
</body>

</html>