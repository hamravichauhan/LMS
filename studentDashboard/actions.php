<?php
session_start();
include "../db/config.php";

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to reserve a book.");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['reserve_book'])) {
        $book_id = intval($_POST['book_id']);

        // Insert reservation with 'pending' status
        $stmt = $conn->prepare("INSERT INTO reservations (user_id, book_id, reservation_date, status) VALUES (?, ?, NOW(), 'pending')");
        $stmt->bind_param("ii", $user_id, $book_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Reservation sent successfully to the admin!";
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
        }

        // Redirect back to books page
        header("Location: books.php");
        exit();
    }

    if (isset($_POST['cancel_reservation'])) {
        $book_id = intval($_POST['book_id']);

        // Cancel reservation
        $stmt = $conn->prepare("DELETE FROM reservations WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $user_id, $book_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Reservation cancelled successfully.";
        } else {
            $_SESSION['message'] = "Error: " . $stmt->error;
        }

        // Redirect back to books page
        header("Location: books.php");
        exit();
    }
}
?>
