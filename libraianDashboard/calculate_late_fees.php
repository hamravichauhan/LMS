<?php
require_once '../db/config.php';

// Calculate late fees for overdue books
$today = date('Y-m-d');
$overdue_query = $conn->prepare("
    SELECT id, expected_return_date 
    FROM reservations 
    WHERE status = 'completed' 
    AND book_taken_date IS NOT NULL 
    AND book_returned_date IS NULL 
    AND expected_return_date < ?
");
$overdue_query->bind_param("s", $today);
$overdue_query->execute();
$overdue_result = $overdue_query->get_result();

while ($reservation = $overdue_result->fetch_assoc()) {
    $expected_return = new DateTime($reservation['expected_return_date']);
    $current_date = new DateTime();
    $days_overdue = $current_date->diff($expected_return)->days;
    $late_fee = $days_overdue * 10; // ₹10 per day
    
    $update_query = $conn->prepare("
        UPDATE reservations 
        SET days_overdue = ?, late_fee = ? 
        WHERE id = ?
    ");
    $update_query->bind_param("idi", $days_overdue, $late_fee, $reservation['id']);
    $update_query->execute();
}

echo "Late fees calculated successfully.";
?>