<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if the user is not a librarian or admin
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] !== "librarian" && $_SESSION["user_role"] !== "admin")) {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once '../db/config.php';

// Get all statistics
$stats = [
    'books_count' => 0,
    'available_books' => 0,
    'issued_books' => 0,
    'users_count' => 0,
    'reservations_count' => 0,
    'today_reservations' => 0,
    'librarians_count' => 0
];

try {
    $stats['books_count'] = $conn->query("SELECT COUNT(*) FROM books")->fetch_row()[0];
    $stats['available_books'] = $conn->query("SELECT COUNT(*) FROM books WHERE status = 'available'")->fetch_row()[0];
    $stats['issued_books'] = $conn->query("SELECT COUNT(*) FROM books WHERE status = 'issued'")->fetch_row()[0];
    $stats['users_count'] = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetch_row()[0];
    $stats['reservations_count'] = $conn->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetch_row()[0];
    $stats['today_reservations'] = $conn->query("SELECT COUNT(*) FROM reservations WHERE DATE(reservation_date) = CURDATE()")->fetch_row()[0];
    $stats['pending_fines'] = $conn->query("SELECT SUM(late_fee) FROM reservations WHERE late_fee > 0 AND book_returned_date IS NULL")->fetch_row()[0];
    $stats['collected_fines'] = $conn->query("SELECT SUM(late_fee) FROM reservations WHERE late_fee > 0 AND book_returned_date IS NOT NULL")->fetch_row()[0];
    $stats['librarians_count'] = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'librarian'")->fetch_row()[0];
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarian Dashboard | Library System</title>
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
    <nav class="bg-primary-600 p-4 shadow-lg">
        <div class="container mx-auto flex flex-col md:flex-row justify-between items-center">
            <div class="flex items-center mb-4 md:mb-0">
                <i class="fas fa-book-open text-white text-2xl mr-3"></i>
                <h1 class="text-white text-2xl font-bold">Librarian Dashboard</h1>
            </div>
            
            <div class="flex items-center space-x-4">
                <span class="text-white hidden md:inline-flex items-center">
                    <i class="fas fa-user-circle mr-2"></i>
                    <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                </span>
                <a href="../auth/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition flex items-center">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Welcome Card -->
        <div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="text-center">
                <h2 class="text-2xl font-semibold text-gray-800">Welcome back, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>!</h2>
                <p class="text-gray-600 mt-2">You're logged in as a <span class="font-medium text-primary-600"><?php echo ucfirst($_SESSION["user_role"]); ?></span></p>
                
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-6">
                    <!-- Total Books -->
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <div class="flex items-center justify-center">
                            <i class="fas fa-book text-blue-600 text-2xl mr-3"></i>
                            <div>
                                <p class="text-gray-500 text-sm">Total Books</p>
                                <p class="text-xl font-bold"><?= number_format($stats['books_count']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Available Books -->
                    <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                        <div class="flex items-center justify-center">
                            <i class="fas fa-book-open text-green-600 text-2xl mr-3"></i>
                            <div>
                                <p class="text-gray-500 text-sm">Available Books</p>
                                <p class="text-xl font-bold"><?= number_format($stats['available_books']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Issued Books -->
                    <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                        <div class="flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-red-600 text-2xl mr-3"></i>
                            <div>
                                <p class="text-gray-500 text-sm">Books Issued</p>
                                <p class="text-xl font-bold"><?= number_format($stats['issued_books']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Active Students -->
                    <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                        <div class="flex items-center justify-center">
                            <i class="fas fa-users text-purple-600 text-2xl mr-3"></i>
                            <div>
                                <p class="text-gray-500 text-sm">Active Students</p>
                                <p class="text-xl font-bold"><?= number_format($stats['users_count']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                <!-- Today's Reservations -->
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-calendar-day text-yellow-600 text-2xl mr-3"></i>
                        <div>
                            <p class="text-gray-500 text-sm">Today's Reservations</p>
                            <p class="text-xl font-bold"><?= number_format($stats['today_reservations']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Unpaid Late Fees -->
                <!-- <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-red-600 text-2xl mr-3"></i>
                        <div class="text-center">
                            <p class="text-gray-700 font-medium">Unpaid Late Fees</p>
                            <p class="text-xl font-bold text-red-600">₹<?= number_format($stats['pending_fines'] ?? 0, 2) ?></p>
                        </div>
                    </div>
                </div> -->

                <!-- Paid Late Fees -->
                <!-- 1 -->
            </div>
        </div>
    </div>
</body>
</html>
        <!-- Dashboard Menu -->
        <div class="max-w-4xl mx-auto mt-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-tasks mr-2 text-primary-600"></i> Quick Actions
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Manage Books -->
                <a href="books.php" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-primary-300 transition-all hover:shadow-md group">
                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-200 transition">
                            <i class="fas fa-book text-blue-600 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-800">Manage Books</h4>
                        <p class="text-gray-500 text-sm mt-2">Add, edit or remove books</p>
                    </div>
                </a>
                
                <!-- View Users -->
                <a href="userList.php" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-green-300 transition-all hover:shadow-md group">
                    <div class="text-center">
                        <div class="bg-green-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-200 transition">
                            <i class="fas fa-users text-green-600 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-800">View Users</h4>
                        <p class="text-gray-500 text-sm mt-2">Manage library members</p>
                    </div>
                </a>
                
                <!-- Manage Reservations -->
                <a href="reservation.php" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-yellow-300 transition-all hover:shadow-md group">
                    <div class="text-center">
                        <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-yellow-200 transition">
                            <i class="fas fa-calendar-check text-yellow-600 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-800">Manage Reservations</h4>
                        <p class="text-gray-500 text-sm mt-2">Handle book loans & returns</p>
                    </div>
                </a>
                
                <!-- Add New Book -->
                <a href="add_book.php" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-purple-300 transition-all hover:shadow-md group">
                    <div class="text-center">
                        <div class="bg-purple-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-200 transition">
                            <i class="fas fa-plus-circle text-purple-600 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-800">Add New Book</h4>
                        <p class="text-gray-500 text-sm mt-2">Expand the library collection</p>
                    </div>
                </a>
                
                <!-- Reports -->
                <a href="reports.php" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-red-300 transition-all hover:shadow-md group">
                    <div class="text-center">
                        <div class="bg-red-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-red-200 transition">
                            <i class="fas fa-chart-bar text-red-600 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-800">Reports</h4>
                        <p class="text-gray-500 text-sm mt-2">View library statistics</p>
                    </div>
                </a>
                
                <!-- Settings -->
                <a href="settings.php" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-gray-300 transition-all hover:shadow-md group">
                    <div class="text-center">
                        <div class="bg-gray-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-gray-200 transition">
                            <i class="fas fa-cog text-gray-600 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-800">Settings</h4>
                        <p class="text-gray-500 text-sm mt-2">Configure your preferences</p>
                    </div>
                </a>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="max-w-4xl mx-auto mt-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-history mr-2 text-primary-600"></i> Recent Activity
            </h3>
            
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <ul class="space-y-4">
                    <li class="flex items-start pb-4 border-b border-gray-100">
                        <div class="bg-green-100 p-2 rounded-full mr-4">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-gray-800">Book returned: "The Great Gatsby"</p>
                            <p class="text-gray-500 text-sm mt-1">Today at 10:30 AM</p>
                        </div>
                    </li>
                    <li class="flex items-start pb-4 border-b border-gray-100">
                        <div class="bg-blue-100 p-2 rounded-full mr-4">
                            <i class="fas fa-book text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-gray-800">New book added: "Atomic Habits"</p>
                            <p class="text-gray-500 text-sm mt-1">Yesterday at 3:45 PM</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="bg-yellow-100 p-2 rounded-full mr-4">
                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-gray-800">Overdue book: "To Kill a Mockingbird"</p>
                            <p class="text-gray-500 text-sm mt-1">2 days ago</p>
                        </div>
                    </li>
                </ul>
                
                <a href="#" class="text-primary-600 hover:text-primary-700 text-sm font-medium mt-4 inline-block">
                    View all activity <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-100 border-t mt-12 py-6">
        <div class="container mx-auto px-4 text-center text-gray-600">
            <p>&copy; <?php echo date('Y'); ?> Library Management System. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>
