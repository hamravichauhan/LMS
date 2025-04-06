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

// Initialize filter variables
$status_filter = $_GET['status'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// Get all book transactions with filters
$transactions = [];
$error = null;

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Base query
    $query = "SELECT 
                b.title AS book_title,
                u.name AS user_name,
                r.reservation_date,
                r.book_returned_date AS return_date,
                r.status,
                r.late_fee,
                r.expected_return_date
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
    
    if (!empty($from_date)) {
        $where[] = "r.reservation_date >= ?";
        $params[] = $from_date;
        $types .= 's';
    }
    
    if (!empty($to_date)) {
        $where[] = "r.reservation_date <= ?";
        $params[] = $to_date . ' 23:59:59'; // Include entire day
        $types .= 's';
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    
    $query .= " ORDER BY r.reservation_date DESC LIMIT 500";
    
    // Prepare and execute query with parameters
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $error = "Database Error: " . $e->getMessage();
    error_log($error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Transaction Report</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 20px;
                font-size: 12px;
            }
            table {
                width: 100%;
                font-size: 11px;
            }
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body class="bg-gray-100 p-4 md:p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Header -->
        <div class="bg-blue-600 text-white p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">Book Transaction Report</h1>
                    <p class="mt-2">Generated on <?php echo date('F j, Y'); ?></p>
                </div>
                <div class="flex space-x-2 no-print">
                    <button onclick="window.print()" class="bg-white text-blue-600 px-4 py-2 rounded-md hover:bg-gray-100 transition">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                    <a href="reports.php" class="bg-white text-blue-600 px-4 py-2 rounded-md hover:bg-gray-100 transition">
                        <i class="fas fa-arrow-left mr-2"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 m-4" role="alert">
            <p class="font-bold">Error Generating Report</p>
            <p><?php echo htmlspecialchars($error); ?></p>
            <p>Please contact system administrator.</p>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="p-4 border-b no-print">
            <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full p-2 border rounded-md">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" class="w-full p-2 border rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" class="w-full p-2 border rounded-md">
                </div>
                <div class="flex items-end space-x-2">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition h-[42px]">
                        <i class="fas fa-filter mr-2"></i> Apply Filters
                    </button>
                    <a href="transaction_report.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 transition h-[42px] flex items-center">
                        <i class="fas fa-times mr-2"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Report Content -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrower</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Checkout Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late Fee</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No transactions found matching your filters</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($transactions as $row): 
                        $is_overdue = !$row['return_date'] && 
                                     $row['expected_return_date'] && 
                                     strtotime($row['expected_return_date']) < time() && 
                                     $row['status'] === 'completed';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['book_title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M j, Y', strtotime($row['reservation_date'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($row['expected_return_date']): ?>
                                <?php echo date('M j, Y', strtotime($row['expected_return_date'])); ?>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($row['return_date']): ?>
                                <?php echo date('M j, Y', strtotime($row['return_date'])); ?>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($is_overdue): ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium status-overdue">
                                    Overdue
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($row['late_fee'] > 0): ?>
                                <span class="text-red-600 font-medium">₹<?php echo number_format($row['late_fee'], 2); ?></span>
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

        <!-- Summary -->
        <div class="p-4 border-t">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-gray-600">
                        Showing <span class="font-medium"><?php echo count($transactions); ?></span> transactions
                        <?php if (!empty($status_filter) || !empty($from_date) || !empty($to_date)): ?>
                            (filtered)
                        <?php endif; ?>
                    </p>
                </div>
                <div class="text-sm text-gray-600 no-print">
                    <p>Total Late Fees: <span class="font-medium">₹<?php 
                        $total_late_fees = array_sum(array_column($transactions, 'late_fee'));
                        echo number_format($total_late_fees, 2);
                    ?></span></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>