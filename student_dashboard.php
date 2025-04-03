<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

    <!-- Navbar -->
    <nav class="bg-blue-600 p-4 text-white flex justify-between">
        <h1 class="text-xl font-bold">Library System</h1>
        <ul class="flex space-x-4">
            <li><a href="dashboard.php" class="hover:underline">Home</a></li>
            <li><a href="?books" class="hover:underline">Books</a></li>
            <li><a href="?issued_books" class="hover:underline">My Issued Books</a></li>
            <li><a href="?reservations" class="hover:underline">Reservations</a></li>
            <li><a href="logout.php" class="hover:underline">Logout</a></li>
        </ul>
    </nav>

    <div class="container mx-auto p-6">
        
        <!-- Books Section -->
        <?php if(isset($_GET['books'])): ?>
        <h2 class="text-2xl font-semibold mb-4">Available Books</h2>
        <table class="w-full bg-white shadow-md rounded-lg overflow-hidden">
            <thead>
                <tr class="bg-blue-500 text-white">
                    <th class="p-2">Title</th>
                    <th class="p-2">Author</th>
                    <th class="p-2">Available Copies</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include "../db/config.php";
                $result = $conn->query("SELECT * FROM books WHERE status = 'available'");
                while ($row = $result->fetch_assoc()):
                ?>
                <tr class="border-b">
                    <td class="p-2"><?= $row['title'] ?></td>
                    <td class="p-2"><?= $row['author'] ?></td>
                    <td class="p-2"><?= $row['copies'] ?></td>
                    <td class="p-2">
                        <form action="actions.php" method="post" class="inline">
                            <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="issue_book" class="bg-green-500 text-white px-3 py-1 rounded">Issue</button>
                        </form>
                        <form action="actions.php" method="post" class="inline">
                            <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="reserve_book" class="bg-yellow-500 text-white px-3 py-1 rounded">Reserve</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <!-- Issued Books Section -->
        <?php if(isset($_GET['issued_books'])): ?>
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
                $user_id = $_SESSION['user_id'];
                $result = $conn->query("SELECT books.title, issued_books.issued_date, books.id 
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
        <?php endif; ?>

        <!-- Reservations Section -->
        <?php if(isset($_GET['reservations'])): ?>
        <h2 class="text-2xl font-semibold mb-4">My Reservations</h2>
        <table class="w-full bg-white shadow-md rounded-lg overflow-hidden">
            <thead>
                <tr class="bg-blue-500 text-white">
                    <th class="p-2">Title</th>
                    <th class="p-2">Reservation Date</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT books.title, reservations.reserved_date, books.id 
                                        FROM reservations 
                                        JOIN books ON reservations.book_id = books.id 
                                        WHERE reservations.user_id = $user_id");
                while ($row = $result->fetch_assoc()):
                ?>
                <tr class="border-b">
                    <td class="p-2"><?= $row['title'] ?></td>
                    <td class="p-2"><?= $row['reserved_date'] ?></td>
                    <td class="p-2">
                        <form action="actions.php" method="post">
                            <input type="hidden" name="book_id" value="<?= $row['id'] ?>">
                            <button type="submit" name="cancel_reservation" class="bg-gray-500 text-white px-3 py-1 rounded">Cancel</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </div>

</body>
</html>
