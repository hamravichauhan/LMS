<?php
session_start();
include "../db/config.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view this page.");
}

// Display success or error messages
if (isset($_SESSION['message'])) {
    echo '<div class="fixed top-4 right-4 z-50">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">' . htmlspecialchars($_SESSION['message']) . '</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
          </div>';
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="fixed top-4 right-4 z-50">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">' . htmlspecialchars($_SESSION['error']) . '</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.remove()">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
          </div>';
    unset($_SESSION['error']);
}

// Fetch all approved reservations with late fee calculation
$approved_sql = "SELECT r.id, u.name AS user_name, b.title AS book_title, 
                r.reservation_date, r.book_taken_date, r.expected_return_date, 
                r.book_returned_date, r.late_fee,
                CASE 
                    WHEN r.book_returned_date IS NULL AND r.expected_return_date < CURDATE() THEN 
                        DATEDIFF(CURDATE(), r.expected_return_date) * 30
                    ELSE r.late_fee
                END AS calculated_late_fee
                FROM reservations r
                JOIN users u ON r.user_id = u.id
                JOIN books b ON r.book_id = b.id
                WHERE r.status = 'completed'";
$approved_result = $conn->query($approved_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Reservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
        // JavaScript to handle the Book Taken modal
        function openBookTakenModal(reservationId) {
            document.getElementById("reservationId").value = reservationId;
            document.getElementById("bookTakenModal").classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
        }

        function closeBookTakenModal() {
            document.getElementById("bookTakenModal").classList.add("hidden");
            document.body.classList.remove("overflow-hidden");
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('bookTakenModal');
            if (event.target === modal) {
                closeBookTakenModal();
            }
        }
    </script>
    <style>
        .table-container {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        /* Custom scrollbar */
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-taken {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-returned {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        .badge-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900">Library Management System</h1>
                <nav>
                    <ul class="flex space-x-6">
                        <li><a href="index.php" class="text-gray-600 hover:text-gray-900">Dashboard</a></li>
                        <li><a href="reservation.php" class="text-gray-600 hover:text-gray-900">Reservations</a></li>
                        <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Approved Reservations</h2>
                    <p class="text-gray-600">Manage books that have been approved for borrowing</p>
                </div>
                <a href="reservation.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Reservations
                </a>
            </div>

            <!-- Late Fees Section -->
            <div class="bg-white shadow rounded-lg overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Overdue Books with Late Fees</h3>
                </div>
                <div class="table-container">
                    <?php 
                    $late_fees_sql = "SELECT r.id, u.name AS user_name, b.title AS book_title, 
                                     r.expected_return_date, 
                                     DATEDIFF(CURDATE(), r.expected_return_date) AS days_late,
                                     DATEDIFF(CURDATE(), r.expected_return_date) * 30 AS late_fee
                                     FROM reservations r
                                     JOIN users u ON r.user_id = u.id
                                     JOIN books b ON r.book_id = b.id
                                     WHERE r.book_returned_date IS NULL 
                                     AND r.expected_return_date < CURDATE()
                                     AND r.book_taken_date IS NOT NULL
                                     AND r.status = 'completed'";
                    $late_fees_result = $conn->query($late_fees_sql);
                    ?>
                    
                    <?php if ($late_fees_result->num_rows > 0): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Late</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late Fee (₹)</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($row = $late_fees_result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-red-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-user text-red-600"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['user_name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['book_title']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?= date('M j, Y', strtotime($row['expected_return_date'])) ?>
                                                <span class="ml-1 text-xs text-red-500">(Overdue)</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= $row['days_late'] ?> days
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-bold text-red-600">
                                                ₹<?= number_format($row['late_fee'], 2) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="book_returned.php?reservation_id=<?= $row['id'] ?>" 
                                               class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                <i class="fas fa-undo mr-1"></i> Mark Returned
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-4xl text-green-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900">No overdue books</h3>
                            <p class="mt-1 text-sm text-gray-500">There are currently no books with late fees.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approved Reservations Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="table-container">
                    <?php if ($approved_result->num_rows > 0): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reservation Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Return</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Late Fee</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($row = $approved_result->fetch_assoc()): 
                                    $today = new DateTime();
                                    $expectedReturn = $row['expected_return_date'] ? new DateTime($row['expected_return_date']) : null;
                                    $isOverdue = $expectedReturn && $today > $expectedReturn && !$row['book_returned_date'];
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-user text-blue-600"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['user_name']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['book_title']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?= date('M j, Y', strtotime($row['reservation_date'])) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if (!$row['book_taken_date']): ?>
                                                <span class="status-badge badge-pending">
                                                    <i class="fas fa-clock mr-1"></i> Pending Pickup
                                                </span>
                                            <?php elseif ($row['book_returned_date']): ?>
                                                <span class="status-badge badge-returned">
                                                    <i class="fas fa-check-circle mr-1"></i> Returned
                                                </span>
                                            <?php elseif ($isOverdue): ?>
                                                <span class="status-badge badge-overdue">
                                                    <i class="fas fa-exclamation-circle mr-1"></i> Overdue
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge badge-taken">
                                                    <i class="fas fa-book mr-1"></i> Taken
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?= $row['expected_return_date'] ? date('M j, Y', strtotime($row['expected_return_date'])) : 'Not Set' ?>
                                                <?php if ($isOverdue): ?>
                                                    <span class="ml-1 text-xs text-red-500">(Overdue)</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-bold <?= $row['calculated_late_fee'] > 0 ? 'text-red-600' : 'text-gray-500' ?>">
                                                ₹<?= number_format($row['calculated_late_fee'], 2) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <!-- Book Taken Button -->
                                                <?php if (!$row['book_taken_date']): ?>
                                                    <button onclick="openBookTakenModal(<?= $row['id'] ?>)" 
                                                            class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                        <i class="fas fa-book-open mr-1"></i> Mark Taken
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Book Returned Button -->
                                                <?php if ($row['book_taken_date'] && !$row['book_returned_date']): ?>
                                                    <a href="book_returned.php=<?= $row['id'] ?>" 
                                                       class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                        <i class="fas fa-undo mr-1"></i> Mark Returned
                                                    </a>
                                                <?php endif; ?>

                                                <!-- View Details Button -->
                                                <a href="get_reservation_details.php?id=<?= $row['id'] ?>" 
                                                   class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                    <i class="fas fa-eye mr-1"></i> Details
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900">No approved reservations</h3>
                            <p class="mt-1 text-sm text-gray-500">There are currently no approved reservations to display.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- Book Taken Modal -->
        <div id="bookTakenModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Confirm Book Pickup</h3>
                </div>
                <form id="bookTakenForm" action="book_taken.php" method="POST" class="px-6 py-4">
                    <input type="hidden" id="reservationId" name="reservation_id">
                    <div class="mb-4">
                        <label for="expectedReturnDate" class="block text-sm font-medium text-gray-700 mb-1">Expected Return Date</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="date" id="expectedReturnDate" name="expected_return_date" 
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-3 pr-10 py-2 sm:text-sm border-gray-300 rounded-md" 
                                   required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-alt text-gray-400"></i>
                            </div>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">Please set the date when the book should be returned.</p>
                    </div>
                    <div class="px-6 py-3 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeBookTakenModal()" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-check-circle mr-2"></i> Confirm Pickup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>