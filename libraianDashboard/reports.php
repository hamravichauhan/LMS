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

// Initialize filter variables from GET parameters
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build the base query
$query = "SELECT 
            b.title AS book_title,
            u.name AS user_name,
            r.reservation_date,
            r.book_returned_date AS return_date,
            r.status,
            r.late_fee,
            r.expected_return_date,
            r.book_taken_date
          FROM reservations r
          JOIN books b ON r.book_id = b.id
          JOIN users u ON r.user_id = u.id";

// Add WHERE conditions based on filters
$where = [];
$params = [];
$types = '';

if (!empty($status_filter)) {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search_query)) {
    $where[] = "(b.title LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $types .= 'ss';
}

if (!empty($date_from)) {
    $where[] = "r.reservation_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $where[] = "r.reservation_date <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY r.reservation_date DESC LIMIT 1000";

// Execute the query
$transactions = [];
$total_pending_fines = 0;
try {
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    
    // Calculate total pending fines (only for unreturned books with late fees)
    $pending_fines_query = "SELECT SUM(late_fee) AS total_pending 
                           FROM reservations 
                           WHERE book_returned_date IS NULL 
                           AND late_fee > 0";
    $pending_result = $conn->query($pending_fines_query);
    $total_pending_fines = $pending_result->fetch_assoc()['total_pending'] ?? 0;
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Failed to load transaction data. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Reports | Library System</title>
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

        // Function to confirm PDF generation
        function confirmGeneratePDF() {
            return confirm('Generate PDF report for the current filtered results?');
        }
    </script>
    <style>
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .table-container {
            max-height: calc(100vh - 400px);
            overflow-y: auto;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">

    <!-- Navbar -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-900">
                <a href="dashboard.php" class="flex items-center">
                    <i class="fas fa-book-open text-blue-600 mr-2"></i>
                    Library Management System
                </a>
            </h1>
            <nav>
                <ul class="flex space-x-6">
                    <li><a href="index.php" class="text-gray-600 hover:text-gray-900">Dashboard</a></li>
                    <li><a href="books.php" class="text-gray-600 hover:text-gray-900">Books</a></li>
                    <li><a href="../auth/logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Reports Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h2 class="text-2xl font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-chart-bar text-primary-600 mr-2"></i>
                    Book Transaction History
                </h2>
                
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <a href="generate_pdf.php?<?php echo http_build_query($_GET); ?>" 
                       onclick="return confirmGeneratePDF()" 
                       class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition flex items-center justify-center">
                        <i class="fas fa-file-pdf mr-2"></i> Generate PDF
                    </a>
                    <a href="reports.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition flex items-center justify-center">
                        <i class="fas fa-sync-alt mr-2"></i> Reset Filters
                    </a>
                </div>
            </div>
            
            <!-- Filters Section -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" 
                               placeholder="Book or user name" 
                               class="w-full p-2 border rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full p-2 border rounded-md focus:ring-primary-500 focus:border-primary-500">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <!-- Date Range -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                               class="w-full p-2 border rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                               class="w-full p-2 border rounded-md focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    
                    <div class="md:col-span-4 flex justify-end gap-2">
                        <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition flex items-center">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                    <div class="flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-book text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Transactions</p>
                            <p class="text-xl font-bold"><?php echo number_format(count($transactions)); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Completed</p>
                            <p class="text-xl font-bold">
                                <?php 
                                    $completed = array_filter($transactions, fn($t) => $t['status'] === 'completed');
                                    echo number_format(count($completed));
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                    <div class="flex items-center">
                        <div class="bg-red-100 p-3 rounded-full mr-4">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Pending Fines</p>
                            <p class="text-xl font-bold">
                                ₹<?php echo number_format($total_pending_fines, 2); ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-100">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-3 rounded-full mr-4">
                            <i class="fas fa-clock text-purple-600"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Overdue Books</p>
                            <p class="text-xl font-bold">
                                <?php 
                                    $overdue = array_filter($transactions, fn($t) => 
                                        $t['status'] === 'completed' && 
                                        !$t['return_date'] && 
                                        $t['expected_return_date'] && 
                                        strtotime($t['expected_return_date']) < time()
                                    );
                                    echo number_format(count($overdue));
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Transactions Table -->
            <div class="table-container">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrower</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Checkout Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late Fee</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-book-open text-2xl mb-2 text-gray-300"></i>
                                <p>No transactions found matching your criteria</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($transactions as $transaction): 
                            $is_overdue = !$transaction['return_date'] && 
                                         $transaction['expected_return_date'] && 
                                         strtotime($transaction['expected_return_date']) < time() && 
                                         $transaction['status'] === 'completed';
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo htmlspecialchars($transaction['book_title']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo htmlspecialchars($transaction['user_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo $transaction['book_taken_date'] ? date('M j, Y', strtotime($transaction['book_taken_date'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo $transaction['expected_return_date'] ? date('M j, Y', strtotime($transaction['expected_return_date'])) : '-'; ?>
                                <?php if ($is_overdue): ?>
                                    <span class="ml-1 text-xs text-red-500">(Overdue)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo $transaction['return_date'] ? date('M j, Y', strtotime($transaction['return_date'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($is_overdue): ?>
                                    <span class="status-badge status-overdue">
                                        <i class="fas fa-clock mr-1"></i> Overdue
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($transaction['late_fee'] > 0): ?>
                                    <span class="text-red-600 font-medium">₹<?php echo number_format($transaction['late_fee'], 2); ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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