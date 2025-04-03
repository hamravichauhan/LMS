<?php
session_start();
include "../db/config.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if reservation ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No reservation specified";
    header("Location: approve_reservation.php");
    exit();
}

$reservation_id = intval($_GET['id']);

// Get reservation details
$query = "SELECT r.*, 
          u.name AS user_name, u.email AS user_email,
          b.title AS book_title, b.author AS book_author, b.isbn AS book_isbn,
          DATEDIFF(CURDATE(), r.expected_return_date) AS days_overdue,
          CASE 
              WHEN r.book_returned_date IS NULL AND r.expected_return_date < CURDATE() 
              THEN DATEDIFF(CURDATE(), r.expected_return_date) * 30
              ELSE 0 
          END AS calculated_late_fee
          FROM reservations r
          JOIN users u ON r.user_id = u.id
          JOIN books b ON r.book_id = b.id
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Reservation not found";
    header("Location: approve_reservation.php");
    exit();
}

$reservation = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details | Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navbar -->
        <nav class="bg-blue-600 p-4 shadow-lg">
            <div class="container mx-auto flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-book-open text-white text-2xl mr-3"></i>
                    <h1 class="text-white text-2xl font-bold">Reservation Details</h1>
                </div>
                <a href="approve_reservation.php" class="text-white hover:text-gray-200">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- User Information -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h2 class="text-lg font-semibold mb-4 border-b pb-2">
                            <i class="fas fa-user mr-2"></i> User Details
                        </h2>
                        <div class="space-y-3">
                            <p><span class="font-medium">Name:</span> <?= htmlspecialchars($reservation['user_name']) ?></p>
                            <p><span class="font-medium">Email:</span> <?= htmlspecialchars($reservation['user_email']) ?></p>
                        </div>
                    </div>

                    <!-- Book Information -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h2 class="text-lg font-semibold mb-4 border-b pb-2">
                            <i class="fas fa-book mr-2"></i> Book Details
                        </h2>
                        <div class="space-y-3">
                            <p><span class="font-medium">Title:</span> <?= htmlspecialchars($reservation['book_title']) ?></p>
                            <p><span class="font-medium">Author:</span> <?= htmlspecialchars($reservation['book_author']) ?></p>
                            <p><span class="font-medium">ISBN:</span> <?= htmlspecialchars($reservation['book_isbn']) ?></p>
                        </div>
                    </div>

                    <!-- Reservation Timeline -->
                    <div class="border border-gray-200 rounded-lg p-4 md:col-span-2">
                        <h2 class="text-lg font-semibold mb-4 border-b pb-2">
                            <i class="fas fa-calendar-alt mr-2"></i> Reservation Timeline
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="font-medium">Reserved On:</p>
                                <p><?= date('M j, Y g:i A', strtotime($reservation['reservation_date'])) ?></p>
                            </div>
                            <div>
                                <p class="font-medium">Due Date:</p>
                                <p class="<?= ($reservation['expected_return_date'] < date('Y-m-d') && !$reservation['book_returned_date']) ? 'text-red-600' : '' ?>">
                                    <?= date('M j, Y', strtotime($reservation['expected_return_date'])) ?>
                                    <?php if ($reservation['expected_return_date'] < date('Y-m-d') && !$reservation['book_returned_date']): ?>
                                        <span class="text-sm">(Overdue)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <p class="font-medium">Status:</p>
                                <p>
                                    <?php if ($reservation['book_returned_date']): ?>
                                        <span class="text-green-600">Returned on <?= date('M j, Y', strtotime($reservation['book_returned_date'])) ?></span>
                                    <?php elseif ($reservation['book_taken_date']): ?>
                                        <span class="text-blue-600">Checked Out</span>
                                    <?php else: ?>
                                        <span class="text-yellow-600">Pending Pickup</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Late Fee Information -->
                    <?php if ($reservation['calculated_late_fee'] > 0 || $reservation['late_fee'] > 0): ?>
                    <div class="border border-gray-200 rounded-lg p-4 md:col-span-2">
                        <h2 class="text-lg font-semibold mb-4 border-b pb-2">
                            <i class="fas fa-money-bill-wave mr-2"></i> Late Fee Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="font-medium">Days Overdue:</p>
                                <p><?= $reservation['days_overdue'] > 0 ? $reservation['days_overdue'] : '0' ?></p>
                            </div>
                            <div>
                                <p class="font-medium">Late Fee:</p>
                                <p class="text-red-600 font-bold">
                                    ₹<?= number_format(max($reservation['calculated_late_fee'], $reservation['late_fee']), 2) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
