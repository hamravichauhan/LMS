<h2 class="text-2xl font-semibold mb-4">My Issued Books</h2>
<table class="w-full bg-white shadow-md rounded-lg overflow-hidden">
    <thead>
        <tr class="bg-blue-500 text-white">
            <th class="p-2">Title</th>
            <th class="p-2">Issued Date</th>
            <th class="p-2">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        session_start();
        include "../db/config.php";
        $user_id = $_SESSION['user_id'];
        $result = $conn->query("SELECT books.title, issue_books.issue_date, books.id 
                                FROM issued_books 
                                JOIN books ON issued_books.book_id = books.id 
                                WHERE issued_books.user_id = $user_id");
        while ($row = $result->fetch_assoc()):
        ?>
        <tr class="border-b">
            <td class="p-2"><?= $row['title'] ?></td>
            <td class="p-2"><?= $row['issued_date'] ?></td>
            <td class="p-2">
                <form action="actions.php" method="post">
                    <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                    <button type="submit" name="return_book" class="bg-red-500 text-white px-3 py-1 rounded">Return</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
