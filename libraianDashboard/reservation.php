<?php
session_start();
include "../db/config.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view this page.");
}

// Handle reservation approval
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['approve_reservation'])) {
    $reservation_id = intval($_GET['approve_reservation']);

    // Fetch the reservation details
    $stmt = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();

    if ($reservation) {
        $book_id = $reservation['book_id'];
        $user_id = $reservation['user_id'];

        // Update the reservation status to 'completed'
        $update_reservation_stmt = $conn->prepare("UPDATE reservations SET status = 'completed' WHERE id = ?");
        $update_reservation_stmt->bind_param("i", $reservation_id);
        $update_reservation_stmt->execute();

        // Decrease the available copies of the book
        $update_book_stmt = $conn->prepare("UPDATE books SET copies = copies - 1 WHERE id = ?");
        $update_book_stmt->bind_param("i", $book_id);
        $update_book_stmt->execute();

        // Check if available copies reached zero
        $check_copies_stmt = $conn->prepare("SELECT copies FROM books WHERE id = ?");
        $check_copies_stmt->bind_param("i", $book_id);
        $check_copies_stmt->execute();
        $copies_result = $check_copies_stmt->get_result();
        $copies_row = $copies_result->fetch_assoc();

        if ($copies_row['copies'] <= 0) {
            // Mark the book as 'issued'
            $issued_stmt = $conn->prepare("UPDATE books SET status = 'issued' WHERE id = ?");
            $issued_stmt->bind_param("i", $book_id);
            $issued_stmt->execute();
        }

        // Set a success message
        $message = "Reservation approved successfully. The book has been issued to the user.";
    } else {
        // Set an error message
        $error = "Reservation not found.";
    }
}

// Fetch all pending reservations
$sql = "SELECT r.id, u.name AS user_name, b.title AS book_title, r.reservation_date 
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN books b ON r.book_id = b.id
        WHERE r.status = 'pending'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
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

<body class="bg-gray-100 p-6">
    
    <div class="container mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Manage Reservations</h2>


        <!-- Display success or error messages -->
        <?php if (isset($message)): ?>
            <p class="text-center text-green-600 font-semibold"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <p class="text-center text-red-600 font-semibold"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <!-- Button to view approved reservations -->
        <a href="approve_reservation.php" class="bg-blue-500 text-white px-4 py-2 rounded mb-4 inline-block">
            View Approved Reservations
        </a>

        <!-- Pending Reservations -->
        <?php if ($result->num_rows > 0): ?>
            <table class="w-full bg-white shadow-md rounded-lg overflow-hidden">
                <thead class="bg-blue-500 text-white">
                    <tr>
                        <th class="p-2">User</th>
                        <th class="p-2">Book</th>
                        <th class="p-2">Reservation Date</th>
                        <th class="p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="p-2"><?= htmlspecialchars($row['user_name']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($row['book_title']) ?></td>
                            <td class="p-2"><?= $row['reservation_date'] ?></td>
                            <td class="p-2">
                                <a href="reservation.php?approve_reservation=<?= $row['id'] ?>" 
                                   class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 transition duration-200">
                                   Approve
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center text-gray-600">No pending reservations found.</p>
        <?php endif; ?>
    </div>
</body>
</html>