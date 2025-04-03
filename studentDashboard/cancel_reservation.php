<?php
session_start();
require_once '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if reservation ID is provided
if (!isset($_POST['reservation_id']) || empty($_POST['reservation_id'])) {
    $_SESSION['error'] = "Invalid reservation ID";
    header("Location: reservations.php");
    exit();
}

$reservation_id = (int)$_POST['reservation_id'];
$user_id = $_SESSION['user_id'];

// Verify the reservation belongs to the user
$verify_query = $conn->prepare("SELECT user_id, book_id FROM reservations WHERE id = ?");
$verify_query->bind_param("i", $reservation_id);
$verify_query->execute();
$verify_result = $verify_query->get_result();

if ($verify_result->num_rows === 0) {
    $_SESSION['error'] = "Reservation not found";
    header("Location: reservations.php");
    exit();
}

$reservation_data = $verify_result->fetch_assoc();
if ($reservation_data['user_id'] != $user_id) {
    $_SESSION['error'] = "You can only cancel your own reservations";
    header("Location: reservations.php");
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Update the reservation status to 'cancelled'
    $update_query = $conn->prepare("UPDATE reservations SET status = 'cancelled', book_returned_date = NOW() WHERE id = ?");
    $update_query->bind_param("i", $reservation_id);
    $update_query->execute();
    
    // If the book was marked as 'issued', make it available again
    if ($reservation_data['book_id']) {
        $book_query = $conn->prepare("UPDATE books SET status = 'available' WHERE id = ?");
        $book_query->bind_param("i", $reservation_data['book_id']);
        $book_query->execute();
        
        // Increment available copies
        $copies_query = $conn->prepare("UPDATE books SET copies = copies + 1 WHERE id = ?");
        $copies_query->bind_param("i", $reservation_data['book_id']);
        $copies_query->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Reservation cancelled successfully";
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Error cancelling reservation: " . $e->getMessage();
}

header("Location: reservations.php");
exit();
?>