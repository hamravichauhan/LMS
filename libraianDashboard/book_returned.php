<?php
session_start();
require_once "../db/config.php";

// Validate user permissions
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'librarian' && $_SESSION['user_role'] !== 'admin')) {
    $_SESSION['error'] = "Unauthorized access. Please login with proper credentials.";
    header("Location: ../auth/login.php");
    exit();
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['reservation_id'])) {
    $_SESSION['error'] = "Invalid request method or missing parameters.";
    header("Location: approve_reservation.php");
    exit();
}

$reservation_id = filter_var($_GET['reservation_id'], FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$reservation_id) {
    $_SESSION['error'] = "Invalid reservation ID format.";
    header("Location: approve_reservation.php");
    exit();
}

// Begin database transaction
$conn->begin_transaction();

try {
    // Get reservation details with proper joins
    $stmt = $conn->prepare("SELECT 
        r.id, r.book_id, r.user_id, 
        r.expected_return_date, r.book_returned_date, 
        r.status, r.late_fee,
        b.title AS book_title, b.copies, b.status AS book_status,
        u.name AS user_name, u.email AS user_email
        FROM reservations r
        JOIN books b ON r.book_id = b.id
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ? FOR UPDATE");
    
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Reservation record not found.");
    }

    $reservation = $result->fetch_assoc();

    // Validate reservation state
    if ($reservation['book_returned_date'] !== null) {
        throw new Exception("This book was already returned on " . $reservation['book_returned_date']);
    }

    if ($reservation['status'] !== 'completed') {
        throw new Exception("Only completed reservations can be marked as returned.");
    }

    // Calculate late fee if applicable
    $late_fee = 0;
    $days_overdue = 0;
    $current_date = new DateTime();
    
    if ($reservation['expected_return_date']) {
        $return_date = new DateTime($reservation['expected_return_date']);
        
        if ($current_date > $return_date) {
            $days_overdue = $return_date->diff($current_date)->days;
            $late_fee = $days_overdue * 30; // ₹30 per day late fee
        }
    }

    // Update reservation record
    $update_reservation = $conn->prepare("UPDATE reservations SET
        book_returned_date = CURDATE(),
        late_fee = ?,
        days_overdue = ?
        WHERE id = ?");
    
    $update_reservation->bind_param("dii", $late_fee, $days_overdue, $reservation_id);
    $update_reservation->execute();

    // Update book inventory
    $update_book = $conn->prepare("UPDATE books SET 
        copies = copies + 1,
        status = CASE 
            WHEN (copies + 1) > 0 THEN 'available' 
            ELSE status 
        END
        WHERE id = ?");
    
    $update_book->bind_param("i", $reservation['book_id']);
    $update_book->execute();

    // Commit transaction
    $conn->commit();

    // Prepare success notification
    $return_details = [
        'book_title' => $reservation['book_title'],
        'user_name' => $reservation['user_name'],
        'return_date' => $current_date->format('Y-m-d'),
        'late_fee' => $late_fee,
        'days_overdue' => $days_overdue
    ];

    $_SESSION['return_success'] = $return_details;
    
    // Optional: Send email notification
    if ($late_fee > 0) {
        $to = $reservation['user_email'];
        $subject = "Book Return Confirmation - Late Fee Applied";
        $message = "Dear {$reservation['user_name']},\n\n";
        $message .= "Your book '{$reservation['book_title']}' has been returned with a late fee of ₹{$late_fee} ";
        $message .= "({$days_overdue} days overdue).\n\n";
        $message .= "Thank you,\nLibrary Management System";
        
        // In production, use a proper mailer library
        // mail($to, $subject, $message);
    }

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Return failed: " . $e->getMessage();
}

header("Location: approve_reservation.php");
exit();
?>