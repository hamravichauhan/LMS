<?php
session_start();
require_once '../db/config.php';
require_once './navbar.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch reservations with additional details including late fees
$query = $conn->prepare("
    SELECT 
        r.id as reservation_id, 
        b.id as book_id,
        b.title, 
        b.author, 
        b.images, 
        b.ISBN,
        b.status as book_status,
        DATE_FORMAT(r.reservation_date, '%M %e, %Y') as formatted_date,
        r.status as reservation_status,
        r.expected_return_date,
        r.book_returned_date,
        r.book_taken_date,
        r.late_fee,
        r.days_overdue,
        CASE 
            WHEN r.book_returned_date IS NOT NULL THEN 'completed'
            WHEN r.book_taken_date IS NOT NULL AND r.expected_return_date < CURDATE() THEN 'overdue'
            WHEN r.book_taken_date IS NOT NULL THEN 'active'
            ELSE 'pending'
        END as display_status
    FROM reservations r
    JOIN books b ON r.book_id = b.id
    WHERE r.user_id = ?
    ORDER BY 
        CASE 
            WHEN r.book_returned_date IS NOT NULL THEN 4
            WHEN r.book_taken_date IS NOT NULL AND r.expected_return_date < CURDATE() THEN 1
            WHEN r.book_taken_date IS NOT NULL THEN 2
            ELSE 3
        END,
        r.reservation_date DESC
");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$reservations = $result->fetch_all(MYSQLI_ASSOC);
$query->close();

// Calculate counts for each status
$status_counts = [
    'pending' => 0,
    'active' => 0,
    'overdue' => 0,
    'completed' => 0
];

foreach ($reservations as $reservation) {
    $status_counts[$reservation['display_status']]++;
}

// Calculate total pending fines
$pending_fines = array_sum(array_column(
    array_filter($reservations, fn($r) => $r['display_status'] === 'overdue'),
    'late_fee'
));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations | Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .reservation-card {
            transition: all 0.3s ease;
        }
        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
        }
        .overdue-warning {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { background-color: #fee2e2; }
            50% { background-color: #fecaca; }
            100% { background-color: #fee2e2; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">My Reservations</h1>
                    <p class="text-gray-600 mt-2">View and manage your book reservations</p>
                </div>
                <a href="books.php" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 bg-primary-600 text-black rounded-lg hover:bg-primary-700 transition">
                    <i class="fas fa-book mr-2"></i> Browse Books
                </a>
            </div>

            <!-- Reservations Count -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                    <div class="mb-4 md:mb-0">
                        <span class="text-gray-500">Total Reservations:</span>
                        <span class="ml-2 font-semibold"><?= count($reservations) ?></span>
                    </div>
                    <div class="flex space-x-4">
                        <div class="text-center">
                            <div class="text-yellow-500 font-semibold">
                                <?= $status_counts['pending'] ?>
                            </div>
                            <div class="text-xs text-gray-500">Pending</div>
                        </div>
                        <div class="text-center">
                            <div class="text-blue-500 font-semibold">
                                <?= $status_counts['active'] ?>
                            </div>
                            <div class="text-xs text-gray-500">Active</div>
                        </div>
                        <div class="text-center">
                            <div class="text-red-500 font-semibold">
                                <?= $status_counts['overdue'] ?>
                            </div>
                            <div class="text-xs text-gray-500">Overdue</div>
                        </div>
                        <div class="text-center">
                            <div class="text-green-500 font-semibold">
                                <?= $status_counts['completed'] ?>
                            </div>
                            <div class="text-xs text-gray-500">Completed</div>
                        </div>
                        <?php if ($pending_fines > 0): ?>
                        <div class="text-center">
                            <div class="text-red-600 font-semibold">
                                ₹<?= number_format($pending_fines, 2) ?>
                            </div>
                            <div class="text-xs text-gray-500">Pending Fines</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Reservations List -->
            <?php if (!empty($reservations)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($reservations as $reservation): 
                        $is_overdue = $reservation['display_status'] === 'overdue';
                    ?>
                        <div class="reservation-card bg-white rounded-xl shadow-md overflow-hidden border-l-4 
                            <?= $reservation['display_status'] === 'pending' ? 'border-yellow-400' : 
                               ($reservation['display_status'] === 'active' ? 'border-blue-500' : 
                               ($is_overdue ? 'border-red-500' : 'border-green-500')) ?>
                            <?= $is_overdue ? 'overdue-warning' : '' ?>">
                            <!-- Book Image and Basic Info -->
                            <div class="p-5">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 h-32 w-24 overflow-hidden rounded-md">
                                        <img src="../images/<?= htmlspecialchars($reservation['images']) ?>" 
                                             alt="<?= htmlspecialchars($reservation['title']) ?>" 
                                             class="h-full w-full object-cover">
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($reservation['title']) ?></h3>
                                        <p class="text-gray-600 text-sm"><?= htmlspecialchars($reservation['author']) ?></p>
                                        <p class="text-gray-500 text-xs mt-1">ISBN: <?= htmlspecialchars($reservation['ISBN']) ?></p>
                                        
                                        <!-- Status Badge -->
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <span class="status-badge 
                                                <?= $reservation['display_status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($reservation['display_status'] === 'active' ? 'bg-blue-100 text-blue-800' : 
                                                   ($is_overdue ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800')) ?>">
                                                <?= ucfirst($reservation['display_status']) ?>
                                            </span>
                                            
                                            <?php if ($reservation['display_status'] === 'active' || $is_overdue): ?>
                                                <span class="status-badge bg-purple-100 text-purple-800">
                                                    Book Status: <?= ucfirst($reservation['book_status']) ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($is_overdue && $reservation['late_fee'] > 0): ?>
                                                <span class="status-badge bg-red-100 text-red-800">
                                                    Fine: ₹<?= number_format($reservation['late_fee'], 2) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Reservation Details -->
                            <div class="border-t border-gray-200 px-5 py-3 bg-gray-50">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-500">Reserved Date</p>
                                        <p class="font-medium"><?= $reservation['formatted_date'] ?></p>
                                    </div>
                                    
                                    <?php if ($reservation['book_taken_date']): ?>
                                        <div>
                                            <p class="text-gray-500">Checked Out</p>
                                            <p class="font-medium"><?= date('M j, Y', strtotime($reservation['book_taken_date'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($reservation['expected_return_date']): ?>
                                        <div>
                                            <p class="text-gray-500">
                                                <?= $is_overdue ? 'Overdue Since' : 'Due Date' ?>
                                            </p>
                                            <p class="font-medium <?= $is_overdue ? 'text-red-600' : '' ?>">
                                                <?= date('M j, Y', strtotime($reservation['expected_return_date'])) ?>
                                                <?php if ($is_overdue): ?>
                                                    <span class="text-xs">(<?= $reservation['days_overdue'] ?> days)</span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($reservation['book_returned_date']): ?>
                                        <div>
                                            <p class="text-gray-500">Returned Date</p>
                                            <p class="font-medium"><?= date('M j, Y', strtotime($reservation['book_returned_date'])) ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-4 flex justify-between">
                                    <a href="book_details.php?id=<?= $reservation['book_id'] ?>" 
                                       class="text-sm text-primary-600 hover:text-primary-800 font-medium">
                                        <!-- <i class="fas fa-info-circle mr-1"></i>  -->
                                    </a>
                                    
                                    <?php if ($reservation['display_status'] === 'pending'): ?>
                                        <form action="cancel_reservation.php" method="post" class="inline">
                                            <input type="hidden" name="reservation_id" value="<?= $reservation['reservation_id'] ?>">
                                            <button type="submit" 
                                                    class="text-sm text-red-600 hover:text-red-800 font-medium"
                                                    onclick="return confirm('Are you sure you want to cancel this reservation?');">
                                                <i class="fas fa-times-circle mr-1"></i> Cancel
                                            </button>
                                        </form>
                                    <?php elseif ($is_overdue): ?>
                                        <a href="pay_fine.php?reservation_id=<?= $reservation['reservation_id'] ?>" 
                                           class="text-sm text-red-600 hover:text-red-800 font-medium">
                                            <i class="fas fa-money-bill-wave mr-1"></i> Pay Fine
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-12 bg-white rounded-xl shadow-sm">
                    <i class="fas fa-book-open text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700">No reservations found</h3>
                    <p class="text-gray-500 mt-2">You haven't made any book reservations yet.</p>
                    <a href="books.php" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                        <i class="fas fa-book mr-2"></i> Browse Available Books
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Any JavaScript functionality can be added here
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight overdue items more prominently
            const overdueCards = document.querySelectorAll('.overdue-warning');
            overdueCards.forEach(card => {
                card.addEventListener('click', () => {
                    alert('This book is overdue! Please return it as soon as possible to avoid additional fees.');
                });
            });
        });
    </script>
</body>
</html>