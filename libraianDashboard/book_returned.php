<?php
session_start();
include "../db/config.php";

// Check if required columns exist, add them if missing
$check_columns = $conn->query("SHOW COLUMNS FROM reservations LIKE 'expected_return_date'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE reservations ADD COLUMN expected_return_date DATE AFTER reservation_date");
    $conn->query("UPDATE reservations SET expected_return_date = DATE_ADD(reservation_date, INTERVAL 14 DAY)");
}

$check_columns = $conn->query("SHOW COLUMNS FROM reservations LIKE 'late_fee'");
if ($check_columns->num_rows == 0) {
    $conn->query("ALTER TABLE reservations ADD COLUMN late_fee DECIMAL(10,2) DEFAULT 0 AFTER book_returned_date");
}

// Check if the user is logged in and has librarian/admin privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'librarian' && $_SESSION['role'] !== 'admin')) {
    $_SESSION['error'] = "You don't have permission to perform this action.";
    header("Location: ../login.php");
    exit();
}

// Check if the reservation ID is provided and valid
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['reservation_id'])) {
    $reservation_id = intval($_GET['reservation_id']);
    
    if ($reservation_id <= 0) {
        $_SESSION['error'] = "Invalid reservation ID.";
        header("Location: approve_reservation.php");
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Fetch the reservation details with proper validation
        $stmt = $conn->prepare("SELECT r.book_id, r.expected_return_date, r.book_returned_date, 
                               b.title AS book_title, u.name AS user_name
                               FROM reservations r
                               JOIN books b ON r.book_id = b.id
                               JOIN users u ON r.user_id = u.id
                               WHERE r.id = ? AND r.status = 'completed'");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Reservation not found or already returned.");
        }

        $reservation = $result->fetch_assoc();
        $book_id = $reservation['book_id'];

        // Check if book is already returned
        if ($reservation['book_returned_date']) {
            throw new Exception("This book has already been returned.");
        }

        // Calculate late fee
        $late_fee = 0;
        $today = new DateTime();
        $return_date = new DateTime($reservation['expected_return_date']);
        
        if ($today > $return_date) {
            $days_late = $return_date->diff($today)->days;
            $late_fee = $days_late * 30; // ₹30 per day late fee
        }

        // Update the reservation as returned
        $update_returned_stmt = $conn->prepare("UPDATE reservations 
                                              SET book_returned_date = CURDATE(), 
                                              late_fee = ?,
                                              status = 'completed'
                                              WHERE id = ?");
        $update_returned_stmt->bind_param("di", $late_fee, $reservation_id);
        $update_returned_stmt->execute();

        // Increase the available copies of the book
        $update_book_stmt = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE id = ?");
        $update_book_stmt->bind_param("i", $book_id);
        $update_book_stmt->execute();

        // Check if the book status needs to be updated
        $check_copies_stmt = $conn->prepare("SELECT copies, status FROM books WHERE id = ?");
        $check_copies_stmt->bind_param("i", $book_id);
        $check_copies_stmt->execute();
        $copies_result = $check_copies_stmt->get_result();
        $copies_row = $copies_result->fetch_assoc();

        if ($copies_row['copies'] > 0 && $copies_row['status'] === 'issued') {
            $update_status_stmt = $conn->prepare("UPDATE books SET status = 'available' WHERE id = ?");
            $update_status_stmt->bind_param("i", $book_id);
            $update_status_stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        // Set success message with details
        $message = "Book '{$reservation['book_title']}' returned successfully by {$reservation['user_name']}.";
        if ($late_fee > 0) {
            $message .= " Late fee: ₹" . number_format($late_fee, 2);
        }
        $_SESSION['message'] = $message;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }

    // Redirect back to the approved reservations page
    header("Location: approve_reservation.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request method or missing reservation ID.";
    header("Location: approve_reservation.php");
    exit();
}
?>