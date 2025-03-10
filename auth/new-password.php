<?php
include "../db/config.php";

$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo "<script>alert('❌ No token provided.'); window.location.href='forgot-password.php';</script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Validate password match
    if ($password !== $confirm_password) {
        echo "<script>alert('❌ Passwords do not match!');</script>";
    } else {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Check if token exists
        $sql = "SELECT email FROM users WHERE token=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $email = $row['email'];

            // Update password and clear token
            $updateSQL = "UPDATE users SET password=?, token=NULL WHERE email=?";
            $updateStmt = $conn->prepare($updateSQL);
            $updateStmt->bind_param("ss", $hashedPassword, $email);

            if ($updateStmt->execute()) {
                echo "<script>alert('✅ Password updated successfully! Redirecting to login page...'); window.location.href='login.php';</script>";
                exit;
            } else {
                echo "<script>alert('❌ Error updating password: " . $updateStmt->error . "');</script>";
            }
        } else {
            echo "<script>alert('❌ Invalid or expired token. Please request a new reset link.'); window.location.href='forgot-password.php';</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-r from-green-400 to-blue-500 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96 animate-fadeIn">
        <h2 class="text-3xl font-bold text-center text-gray-800">Reset Password</h2>
        <p class="text-gray-600 text-sm text-center mt-2">Enter your new password below.</p>

        <form method="POST" class="mt-6 space-y-4">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700">New Password</label>
                <input type="password" name="password" placeholder="Enter New Password"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm New Password"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none" required>
            </div>

            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition-all duration-200 shadow-md">
                Reset Password
            </button>
        </form>
    </div>
</body>

</html>