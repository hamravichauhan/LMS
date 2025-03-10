<?php
include "../db/config.php";
$result = $conn->query("SELECT * FROM books");
?>

<h2>Books List</h2>
<a href="add_book.php">Add New Book</a>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Author</th>
        <th>ISBN</th> <!-- Added ISBN column -->
        <th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row["id"] ?></td>
            <td><?= htmlspecialchars($row["title"]) ?></td>
            <td><?= htmlspecialchars($row["author"]) ?></td>
            <td><?= htmlspecialchars($row["ISBN"]) ?></td> <!-- Display ISBN -->
            <td>
                <a href="edit_book.php?id=<?= $row["id"] ?>">Edit</a>
                <a href="delete_book.php?id=<?= $row["id"] ?>" onclick="return confirm('Delete book?');">Delete</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>