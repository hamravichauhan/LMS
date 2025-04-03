<?php
include "../db/config.php";

// Update late fees for all overdue books
$update_sql = "UPDATE reservations 
               SET late_fee = GREATEST(0, DATEDIFF(CURDATE(), expected_return_date)) * 30
               WHERE book_returned_date IS NULL 
               AND expected_return_date < CURDATE()
               AND book_taken_date IS NOT NULL
               AND status = 'completed'";

if ($conn->query($update_sql)) {
    echo "Late fees updated successfully";
} else {
    echo "Error updating late fees: " . $conn->error;
}

$conn->close();
?>