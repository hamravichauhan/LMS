<?php
include "../db/config.php";

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["book_id"], $_POST["status"])) {
    $book_id = $_POST["book_id"];
    $status = $_POST["status"];

    $stmt = $conn->prepare("UPDATE books SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $book_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch books from the database
$result = $conn->query("SELECT * FROM books");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books List</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h2 class="text-2xl font-bold mb-4">Books List</h2>

        <div class="mb-4">
            <a href="add_book.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add New Book
            </a>
            <a href="index.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded ml-2">
                Home
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border-b">ID</th>
                        <th class="py-2 px-4 border-b">Image</th>
                        <th class="py-2 px-4 border-b">Title</th>
                        <th class="py-2 px-4 border-b">Author</th>
                        <th class="py-2 px-4 border-b">ISBN</th>
                        <th class="py-2 px-4 border-b">Status</th>
                        <th class="py-2 px-4 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b text-center"><?= $row["id"] ?></td>
                            <td class="py-2 px-4 border-b text-center">
                                <img src="../images/<?= htmlspecialchars($row["images"]) ?>" alt="Book Image" class="w-16 h-20 object-cover border rounded">
                            </td>
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($row["title"]) ?></td>
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($row["author"]) ?></td>
                            <td class="py-2 px-4 border-b"><?= htmlspecialchars($row["ISBN"]) ?></td>

                            <!-- Book Status Dropdown -->
                            <td class="py-2 px-4 border-b text-center">
                                <form method="post">
                                    <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                                    <select name="status" class="border rounded px-2 py-1" onchange="this.form.submit()">
                                        <option value="Available" <?= ($row["status"] == "Available") ? "selected" : "" ?>>Available</option>
                                        <option value="Not Available" <?= ($row["status"] == "Not Available") ? "selected" : "" ?>>Not Available</option>
                                    </select>
                                </form>
                            </td>

                            <td class="py-2 px-4 border-b text-center">
                                <a href="edit_book.php?id=<?= $row["id"] ?>" class="text-blue-500 hover:underline mr-2">Edit</a>
                                <a href="delete_book.php?id=<?= $row["id"] ?>" onclick="return confirm('Delete book?');" class="text-red-500 hover:underline">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>