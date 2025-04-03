<?php
include "../db/config.php";
session_start();

// 1. Check if user is authorized (librarian or admin)
if (!isset($_SESSION["user_id"]) || ($_SESSION["user_role"] != "librarian" && $_SESSION["user_role"] != "admin")) {
    $_SESSION['error'] = "Unauthorized access";
    header("Location: ../auth/login.php");
    exit();
}

// 2. Validate book ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    $_SESSION['error'] = "Invalid book ID";
    header("Location: books.php");
    exit();
}

$book_id = intval($_GET["id"]);

try {
    // 3. Start transaction for atomic operations
    $conn->begin_transaction();

    // 4. First delete related reservations (if any)
    $delete_reservations = $conn->prepare("DELETE FROM reservations WHERE book_id = ?");
    $delete_reservations->bind_param("i", $book_id);
    $delete_reservations->execute();

    // 5. Get book image path before deletion (to delete the file later)
    $get_book = $conn->prepare("SELECT images FROM books WHERE id = ?");
    $get_book->bind_param("i", $book_id);
    $get_book->execute();
    $book = $get_book->get_result()->fetch_assoc();

    // 6. Delete the book
    $delete_book = $conn->prepare("DELETE FROM books WHERE id = ?");
    $delete_book->bind_param("i", $book_id);
    $delete_book->execute();

    // 7. If we got here, commit all changes
    $conn->commit();

    // 8. Delete the book image file if it exists
    if (!empty($book['images'])) {
        $image_path = "../images/" . $book['images'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    $_SESSION['success'] = "Book deleted successfully";
} catch (Exception $e) {
    // 9. If anything fails, roll back all changes
    $conn->rollback();
    $_SESSION['error'] = "Error deleting book: " . $e->getMessage();
}

// 10. Redirect back to books page
header("Location: books.php");
exit();
?>