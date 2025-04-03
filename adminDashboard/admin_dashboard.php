<?php
session_start();
include "../middleware/authMiddleware.php";
include "../db/config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] != "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$student_sql = "SELECT id, name, email FROM users WHERE role='student'";
$student_result = $conn->query($student_sql);

$librarian_sql = "SELECT id, name, email FROM users WHERE role='librarian'";
$librarian_result = $conn->query($librarian_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"])) {
    $user_id = intval($_POST["user_id"]);
    $update_sql = "UPDATE users SET role='librarian' WHERE id=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        header("Location: admin_dashboard.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Library Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        secondary: {
                            600: '#7c3aed',
                            700: '#6d28d9',
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-sans">

    <!-- Navbar -->
    <nav class="bg-primary-600 p-4 shadow-md flex flex-col md:flex-row justify-between items-center text-white">
        <div class="flex items-center mb-4 md:mb-0">
            <i class="fas fa-book-open text-2xl mr-3"></i>
            <h1 class="text-2xl font-bold">Library Admin</h1>
        </div>
        <div class="flex flex-wrap justify-center gap-2 md:gap-4">
            <a href="../studentDashboard/index.php" class="px-3 py-2 bg-gray-700 rounded-md hover:bg-gray-600 transition flex items-center">
                <i class="fas fa-user-graduate mr-2"></i> User
            </a>
            <a href="../libraianDashboard/index.php" class="px-3 py-2 bg-gray-700 rounded-md hover:bg-gray-600 transition flex items-center">
                <i class="fas fa-user-tie mr-2"></i> Librarian
            </a>
            <a href="../auth/register.php" class="px-3 py-2 bg-green-600 rounded-md hover:bg-green-500 transition flex items-center">
                <i class="fas fa-user-plus mr-2"></i> Register
            </a>
            <a href="../auth/logout.php" class="px-3 py-2 bg-red-600 rounded-md hover:bg-red-500 transition flex items-center">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-6">
        <!-- Welcome Message -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-6 border border-gray-100">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Welcome back, <?php echo htmlspecialchars($_SESSION["user_name"]); ?></h2>
                    <p class="text-gray-600 mt-1">Manage users and library settings from this dashboard</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <span class="inline-block bg-primary-100 text-primary-800 text-sm px-3 py-1 rounded-full">
                        <i class="fas fa-shield-alt mr-1"></i> Administrator
                    </span>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_GET["success"])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <p>User promoted to Librarian successfully!</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Total Students</p>
                        <h3 class="text-2xl font-bold mt-1"><?php echo $student_result->num_rows; ?></h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Total Librarians</p>
                        <h3 class="text-2xl font-bold mt-1"><?php echo $librarian_result->num_rows; ?></h3>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-user-tie text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500">Actions</p>
                        <h3 class="text-2xl font-bold mt-1">Manage Users</h3>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-cog text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assign Librarian Role -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-user-graduate mr-2 text-primary-600"></i> Student List
                </h3>
                <p class="text-sm text-gray-500"><?php echo $student_result->num_rows; ?> students</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-left">
                            <th class="py-3 px-4 font-medium">Name</th>
                            <th class="py-3 px-4 font-medium">Email</th>
                            <th class="py-3 px-4 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while ($student = $student_result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4">
                                    <div class="flex items-center">
                                        <div class="bg-blue-100 p-2 rounded-full mr-3">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($student["name"]); ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-gray-600"><?php echo htmlspecialchars($student["email"]); ?></td>
                                <td class="py-4 px-4 text-right">
                                    <form method="POST">
                                        <input type="hidden" name="user_id" value="<?php echo $student["id"]; ?>">
                                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 transition flex items-center ml-auto">
                                            <i class="fas fa-user-tie mr-2"></i> Promote
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($student_result->num_rows == 0): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-user-slash text-3xl mb-2"></i>
                    <p>No students found</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- List of Librarians -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-user-tie mr-2 text-secondary-600"></i> Librarian List
                </h3>
                <p class="text-sm text-gray-500"><?php echo $librarian_result->num_rows; ?> librarians</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-left">
                            <th class="py-3 px-4 font-medium">Name</th>
                            <th class="py-3 px-4 font-medium">Email</th>
                            <th class="py-3 px-4 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php while ($librarian = $librarian_result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-4 px-4">
                                    <div class="flex items-center">
                                        <div class="bg-purple-100 p-2 rounded-full mr-3">
                                            <i class="fas fa-user-tie text-purple-600"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($librarian["name"]); ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-gray-600"><?php echo htmlspecialchars($librarian["email"]); ?></td>
                                <td class="py-4 px-4">
                                    <span class="inline-block bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full">
                                        <i class="fas fa-check-circle mr-1"></i> Active
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($librarian_result->num_rows == 0): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-user-tie text-3xl mb-2"></i>
                    <p>No librarians found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-100 border-t mt-8 py-6">
        <div class="container mx-auto px-4 text-center text-gray-600">
            <p>&copy; <?php echo date('Y'); ?> Library Management System. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>