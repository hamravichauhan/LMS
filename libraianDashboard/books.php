<?php
include "../db/config.php";
session_start();

// Handle book updates (Status & Copies)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["book_id"])) {
    $book_id = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
    $status = isset($_POST["status"]) && in_array($_POST["status"], ['available', 'issued']) ? $_POST["status"] : null;
    $copies = isset($_POST["copies"]) ? filter_input(INPUT_POST, 'copies', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 0]
    ]) : null;

    // Validate book exists
    $check_stmt = $conn->prepare("SELECT id FROM books WHERE id = ?");
    $check_stmt->bind_param("i", $book_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows === 0) {
        $_SESSION['error'] = "Book not found";
        header("Location: books.php");
        exit();
    }
    $check_stmt->close();

    // Ensure at least one field is being updated
    if ($status !== null || $copies !== null) {
        $query = "UPDATE books SET ";
        $params = [];
        $types = "";

        if ($status !== null) {
            $query .= "status = ?, ";
            $params[] = $status;
            $types .= "s";
        }

        if ($copies !== null) {
            $query .= "copies = ?, ";
            $params[] = $copies;
            $types .= "i";
        }

        // Remove trailing comma and add WHERE condition
        $query = rtrim($query, ", ") . " WHERE id = ?";
        $params[] = $book_id;
        $types .= "i";

        // Prepare and execute statement
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Book updated successfully";
            } else {
                $_SESSION['error'] = "Error updating record: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error'] = "Database error";
        }
    } else {
        $_SESSION['error'] = "No changes detected";
    }
    header("Location: books.php");
    exit();
}

// Fetch books from the database with pagination
$limit = 10; // Items per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of books
$total_stmt = $conn->query("SELECT COUNT(*) FROM books");
$total_books = $total_stmt->fetch_row()[0];
$total_pages = ceil($total_books / $limit);

// Get books for current page
$result = $conn->query("SELECT * FROM books ORDER BY title LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books List | Library Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .status-available {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-issued {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .table-container {
            max-height: calc(100vh - 250px);
            overflow-y: auto;
        }
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .book-cover {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #e5e7eb;
        }
        .default-cover {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f3f4f6;
            color: #6b7280;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-900">
                    <a href="dashboard.php" class="flex items-center">
                        <i class="fas fa-book-open text-blue-600 mr-2"></i>
                        Library Management System
                    </a>
                </h1>
                <nav>
                    <ul class="flex space-x-6">
                        <li><a href="index.php" class="text-gray-600 hover:text-gray-900">Dashboard</a></li>
                        <li><a href="books.php" class="text-gray-600 hover:text-gray-900">Books</a></li>
                        <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-4 animate-fade-in">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle h-5 w-5 text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($_SESSION['success']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-4 animate-fade-in">
                    <div class="bg-red-50 border-l-4 border-red-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle h-5 w-5 text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($_SESSION['error']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Books Management</h2>
                    <p class="text-gray-600">View and manage all books in the library</p>
                </div>
                <div class="mt-4 sm:mt-0">
                    <a href="add_book.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Book
                    </a>
                    <a href="index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ml-2">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                </div>
            </div>

            <!-- Books Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="table-container">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cover</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ISBN</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Publisher</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Genre</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Edition</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Copies</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row["id"]) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if (!empty($row["images"])): ?>
                                                <img src="../images/<?= htmlspecialchars($row["images"]) ?>" alt="Book Cover" class="book-cover">
                                            <?php else: ?>
                                                <div class="book-cover default-cover">
                                                    <i class="fas fa-book text-xl"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row["title"]) ?></div>
                                            <div class="text-xs text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars(substr($row["book_summary"] ?? '', 0, 100)) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row["author"]) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row["ISBN"]) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row["publisher"]) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row["genre"]) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row["publication_year"]) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($row["edition"]) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <form method="post" class="flex items-center">
                                                <input type="hidden" name="book_id" value="<?= htmlspecialchars($row['id']) ?>">
                                                <input type="number" name="copies" 
                                                    class="w-16 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500"
                                                    value="<?= htmlspecialchars($row["copies"]) ?>" 
                                                    min="0"
                                                    onchange="this.form.submit()">
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <form method="post">
                                                <input type="hidden" name="book_id" value="<?= htmlspecialchars($row['id']) ?>">
                                                <select name="status" 
                                                    onchange="this.form.submit()"
                                                    class="text-sm border border-gray-300 rounded px-2 py-1 focus:ring-blue-500 focus:border-blue-500 <?= $row['status'] === 'available' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' ?>">
                                                    <option value="available" <?= ($row["status"] === "available") ? "selected" : "" ?>>Available</option>
                                                    <option value="issued" <?= ($row["status"] === "issued") ? "selected" : "" ?>>Issued</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-4">
                                                <a href="edit_book.php?id=<?= htmlspecialchars($row["id"]) ?>" 
                                                   class="text-blue-600 hover:text-blue-900" 
                                                   title="Edit">
                                                   <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_book.php?id=<?= htmlspecialchars($row["id"]) ?>" 
                                                   onclick="return confirm('Are you sure you want to delete this book?');"
                                                   class="text-red-600 hover:text-red-900"
                                                   title="Delete">
                                                   <i class="fas fa-trash-alt"></i>
                                                </a>
                                                <a href="book_details.php?id=<?= htmlspecialchars($row["id"]) ?>" 
                                                   class="text-gray-600 hover:text-gray-900"
                                                   title="View Details">
                                                   <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No books found in the library. <a href="add_book.php" class="text-blue-600 hover:text-blue-800">Add a book</a> to get started.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-3 border-t border-gray-200 flex items-center justify-between bg-white">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <a href="?page=<?= $page > 1 ? $page - 1 : 1 ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                        <a href="?page=<?= $page < $total_pages ? $page + 1 : $total_pages ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium"><?= ($offset + 1) ?></span> to <span class="font-medium"><?= min($offset + $limit, $total_books) ?></span> of <span class="font-medium"><?= $total_books ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <a href="?page=<?= $page > 1 ? $page - 1 : 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php 
                                // Show limited pagination links
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                
                                if ($start > 1) {
                                    echo '<a href="?page=1" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</a>';
                                    if ($start > 2) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                }
                                
                                for ($i = $start; $i <= $end; $i++): ?>
                                    <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; 
                                
                                if ($end < $total_pages) {
                                    if ($end < $total_pages - 1) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                    }
                                    echo '<a href="?page='.$total_pages.'" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">'.$total_pages.'</a>';
                                }
                                ?>
                                <a href="?page=<?= $page < $total_pages ? $page + 1 : $total_pages ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>