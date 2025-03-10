<?php
include "../db/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["title"]);
    $author = trim($_POST["author"]);
    $isbn = trim($_POST["ISBN"]); // Added ISBN field

    if (!empty($title) && !empty($author) && !empty($ISBN)) {
        $sql = "INSERT INTO books (title, author, ISBN) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $title, $author, $ISBN);

        if ($stmt->execute()) {
            header("Location: books.php");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "All fields are required!";
    }
}

$conn->close();
?>

<form method="post">
    <input type="text" name="title" placeholder="Book Title" required>
    <input type="text" name="author" placeholder="Author" required>
    <input type="text" name="isbn" placeholder="ISBN" required> <!-- Added ISBN field -->
    <button type="submit">Add Book</button>
</form>