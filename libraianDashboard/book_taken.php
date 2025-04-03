<?php
session_start();
include "../db/config.php";

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to perform this action.");
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id']);
    $expected_return_date = $_POST['expected_return_date'];

    // Update the book_taken_date and expected_return_date
    $stmt = $conn->prepare("UPDATE reservations SET book_taken_date = CURDATE(), expected_return_date = ? WHERE id = ?");
    $stmt->bind_param("si", $expected_return_date, $reservation_id);
    $stmt->execute();

    // Set a success message
    $_SESSION['message'] = "Book taken successfully. Expected return date set.";

    // Redirect back to the approved reservations page
    header("Location: approve_reservation.php");
    exit();
}
?>