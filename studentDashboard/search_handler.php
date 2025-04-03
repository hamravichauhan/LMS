<?php
session_start();
include '../db/config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['book_name'])) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $book_name = trim($_POST['book_name']);

    if (!empty($book_name)) {
        // Insert into search history
        $stmt = $conn->prepare("INSERT INTO search_history (user_id, book_name, searched_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $user_id, $book_name);
        $stmt->execute();

        // Fetch matching books
        $result = $conn->prepare("SELECT id, title, images, author FROM books WHERE title LIKE ?");
        $searchTerm = "%$book_name%";
        $result->bind_param("s", $searchTerm);
        $result->execute();
        $books = $result->get_result();
    }
}
?>
