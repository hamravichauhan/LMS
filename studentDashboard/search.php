<?php
session_start();
require_once '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get and sanitize search parameters
$search_type = isset($_GET['type']) ? filter_var($_GET['type'], FILTER_SANITIZE_STRING) : 'title';
$search_query = isset($_GET['query']) ? trim(filter_var($_GET['query'], FILTER_SANITIZE_STRING)) : '';

// Validate search type
$allowed_types = ['title', 'author', 'isbn', 'genre'];
if (!in_array($search_type, $allowed_types)) {
    $search_type = 'title';
}

// Log the search if query is not empty
if (!empty($search_query) && isset($_SESSION['user_id'])) {
    try {
        $log_query = $conn->prepare("INSERT INTO search_history (user_id, book_name, searched_at) VALUES (?, ?, NOW())");
        $log_query->bind_param("is", $_SESSION['user_id'], $search_query);
        $log_query->execute();
    } catch (Exception $e) {
        error_log("Failed to log search: " . $e->getMessage());
    }
}

// Prepare SQL query with parameterized statements
$sql = "SELECT * FROM books WHERE status = 'available' AND ";
$params = [];
$param_types = '';

switch ($search_type) {
    case 'title':
        $sql .= "title LIKE ?";
        $params[] = "%$search_query%";
        $param_types = 's';
        break;
    case 'author':
        $sql .= "author LIKE ?";
        $params[] = "%$search_query%";
        $param_types = 's';
        break;
    case 'isbn':
        $sql .= "ISBN = ?";
        $params[] = $search_query;
        $param_types = 's';
        break;
    case 'genre':
        $sql .= "genre LIKE ?";
        $params[] = "%$search_query%";
        $param_types = 's';
        break;
}

// Execute search query
try {
    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $books = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $books = [];
}

// Handle reservation if form submitted
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['book_id'])) {
    $user_id = $_SESSION['user_id'];
    $book_id = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);

    if (!$book_id) {
        $reservation_message = "Invalid book selection.";
        $reservation_status = "error";
    } else {
        // Check if already reserved
        $check = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND book_id = ?");
        $check->bind_param("ii", $user_id, $book_id);
        $check->execute();
        $check->bind_result($count);
        $check->fetch();
        $check->close();

        if ($count > 0) {
            $reservation_message = "You have already reserved this book.";
            $reservation_status = "error";
        } else {
            // Insert reservation
            $stmt = $conn->prepare("INSERT INTO reservations (user_id, book_id, reservation_date, status) VALUES (?, ?, NOW(), 'pending')");
            $stmt->bind_param("ii", $user_id, $book_id);

            if ($stmt->execute()) {
                $reservation_message = "Book reserved successfully!";
                $reservation_status = "success";
            } else {
                $reservation_message = "Error reserving book. Please try again.";
                $reservation_status = "error";
            }
            $stmt->close();
        }
    }
}

include './navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results | Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .book-card {
            transition: all 0.3s ease;
        }
        .book-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .modal-overlay {
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Search Results Header -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                Search Results for <?= htmlspecialchars($search_type) ?>: 
                <span class="text-primary-600">"<?= htmlspecialchars($search_query) ?>"</span>
            </h1>
            <p class="text-gray-600">
                Found <?= count($books) ?> result(s)
            </p>
            <a href="books.php" class="inline-flex items-center mt-4 text-primary-600 hover:text-primary-800">
                <i class="fas fa-arrow-left mr-2"></i> Back to all books
            </a>
        </div>

        <!-- Books Grid -->
        <?php if (count($books) > 0): ?>
            <div id="booksGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
                <?php foreach ($books as $row): ?>
                <div class="book-card bg-white rounded-lg shadow-md overflow-hidden cursor-pointer"
                    onclick="openModal('<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>')">
                    <div class="w-full h-48 overflow-hidden">
                        <img src="../images/<?= htmlspecialchars($row['images']) ?>" 
                             alt="<?= htmlspecialchars($row['title']) ?>" 
                             class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($row['title']) ?></h3>
                        <p class="text-gray-600 text-sm mt-1"><?= htmlspecialchars($row['author']) ?></p>
                        <div class="flex justify-between items-center mt-3">
                            <span class="text-xs font-medium <?= $row['status'] === 'available' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                            <span class="text-xs text-blue-600 font-semibold">
                                <?= $row['copies'] ?> <?= $row['copies'] === 1 ? 'copy' : 'copies' ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-xl shadow-sm">
                <i class="fas fa-book-open text-gray-300 text-5xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-700">No books found</h3>
                <p class="text-gray-500 mt-2">Try different search terms or browse our collection</p>
                <a href="books.php" class="mt-4 inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                    <i class="fas fa-book mr-2"></i> Browse Books
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Reservation Modal -->
    <div id="bookModal" class="fixed inset-0 bg-black bg-opacity-50 modal-overlay flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-95">
            <div class="sticky top-0 bg-white p-4 border-b flex justify-between items-center z-10">
                <h3 class="text-xl font-bold text-gray-800">Book Details</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-6">
                <!-- Book Image -->
                <div class="w-full h-64 mb-6 overflow-hidden rounded-lg">
                    <img id="modalImage" class="w-full h-full object-contain" alt="Book cover">
                </div>
                
                <!-- Book Details -->
                <div class="space-y-4">
                    <div>
                        <h2 id="modalTitle" class="text-2xl font-bold text-gray-800"></h2>
                        <p id="modalAuthor" class="text-gray-600 mt-1"></p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-500 text-sm">Genre</p>
                            <p id="modalGenre" class="font-medium"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Publisher</p>
                            <p id="modalPublisher" class="font-medium"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Published</p>
                            <p id="modalYear" class="font-medium"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Edition</p>
                            <p id="modalEdition" class="font-medium"></p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-gray-500 text-sm">ISBN</p>
                        <p id="modalISBN" class="font-medium"></p>
                    </div>
                    
                    <div class="flex items-center space-x-4 pt-2">
                        <span id="modalStatus" class="px-3 py-1 rounded-full text-xs font-semibold"></span>
                        <span id="modalCopies" class="text-blue-600 font-medium"></span>
                    </div>
                    
                    <div class="pt-4 border-t">
                        <h4 class="text-gray-700 font-semibold mb-2">Description</h4>
                        <p id="modalDescription" class="text-gray-600 text-sm"></p>
                    </div>
                </div>
                
                <!-- Reservation Form -->
                <div class="mt-8 sticky bottom-0 bg-white pt-4 pb-2">
                    <form id="reservationForm" method="post" class="space-y-4">
                        <input type="hidden" name="book_id" id="modalBookIdReserve">
                        
                        <!-- Status Message -->
                        <div id="reservationStatus" class="<?= isset($reservation_message) ? 'block' : 'hidden' ?> 
                            <?= isset($reservation_status) && $reservation_status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> 
                            p-3 rounded-lg text-center text-sm">
                            <?= isset($reservation_message) ? htmlspecialchars($reservation_message) : '' ?>
                        </div>
                        
                        <button type="submit" name="reserve_book" id="reserveButton"
                            class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition 
                            disabled:opacity-70 disabled:cursor-not-allowed flex items-center justify-center">
                            <i class="fas fa-bookmark mr-2"></i>
                            <span>Reserve Book</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Current book data
        let currentBook = null;
        
        // Modal functions
        function openModal(bookData) {
            currentBook = JSON.parse(bookData);
            
            // Set modal content
            document.getElementById('modalImage').src = `../images/${currentBook.images}`;
            document.getElementById('modalTitle').textContent = currentBook.title;
            document.getElementById('modalAuthor').textContent = `by ${currentBook.author || 'Unknown'}`;
            document.getElementById('modalGenre').textContent = currentBook.genre || 'Not specified';
            document.getElementById('modalPublisher').textContent = currentBook.publisher || 'Unknown';
            document.getElementById('modalYear').textContent = currentBook.publication_year || 'Unknown';
            document.getElementById('modalEdition').textContent = currentBook.edition || 'Unknown';
            document.getElementById('modalISBN').textContent = currentBook.ISBN || 'N/A';
            
            // Set status and copies
            const statusElement = document.getElementById('modalStatus');
            statusElement.textContent = currentBook.status === 'available' ? 'Available' : 'Issued';
            statusElement.className = `px-3 py-1 rounded-full text-xs font-semibold ${
                currentBook.status === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            }`;
            
            document.getElementById('modalCopies').textContent = 
                `${currentBook.copies} ${currentBook.copies === 1 ? 'copy' : 'copies'} available`;
            
            document.getElementById('modalDescription').textContent = 
                currentBook.book_summary || 'No description available.';
            document.getElementById('modalBookIdReserve').value = currentBook.id;
            
            // Enable/disable reserve button
            const reserveBtn = document.getElementById('reserveButton');
            reserveBtn.disabled = currentBook.copies < 1 || currentBook.status !== 'available';
            
            // Show modal
            document.getElementById('bookModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.getElementById('bookModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentBook = null;
            
            // Reload if reservation was made
            if (<?= isset($reservation_message) ? 'true' : 'false' ?>) {
                setTimeout(() => window.location.reload(), 1500);
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('bookModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // Handle form submission with AJAX
        document.getElementById('reservationForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const statusDiv = document.getElementById('reservationStatus');
            const reserveBtn = document.getElementById('reserveButton');
            
            // Disable button and show loading
            reserveBtn.disabled = true;
            reserveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const text = await response.text();
                statusDiv.classList.remove('hidden');
                
                if (text.includes('successfully')) {
                    statusDiv.className = 'bg-green-100 text-green-800 p-3 rounded-lg text-center text-sm block';
                    statusDiv.textContent = 'Book reserved successfully!';
                    
                    // Update UI
                    if (currentBook) {
                        currentBook.copies--;
                        if (currentBook.copies < 1) {
                            currentBook.status = 'issued';
                            document.getElementById('modalStatus').textContent = 'Issued';
                            document.getElementById('modalStatus').className = 
                                'px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800';
                        }
                        document.getElementById('modalCopies').textContent = 
                            `${currentBook.copies} ${currentBook.copies === 1 ? 'copy' : 'copies'} available`;
                    }
                    
                    // Disable reserve button
                    reserveBtn.disabled = true;
                    reserveBtn.className = 'w-full bg-gray-300 text-gray-600 font-medium py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center';
                    reserveBtn.innerHTML = '<i class="fas fa-check mr-2"></i> Reserved';
                    
                    // Close modal after delay
                    setTimeout(closeModal, 2000);
                } else {
                    statusDiv.className = 'bg-red-100 text-red-800 p-3 rounded-lg text-center text-sm block';
                    statusDiv.textContent = text.includes('already reserved') ? 
                        'You have already reserved this book.' : 
                        'Error reserving book. Please try again.';
                    
                    // Reset button
                    reserveBtn.disabled = false;
                    reserveBtn.className = 'w-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition flex items-center justify-center';
                    reserveBtn.innerHTML = '<i class="fas fa-bookmark mr-2"></i> Reserve Book';
                }
            } catch (error) {
                statusDiv.className = 'bg-red-100 text-red-800 p-3 rounded-lg text-center text-sm block';
                statusDiv.textContent = 'Network error. Please try again.';
                
                // Reset button
                reserveBtn.disabled = false;
                reserveBtn.className = 'w-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg transition flex items-center justify-center';
                reserveBtn.innerHTML = '<i class="fas fa-bookmark mr-2"></i> Reserve Book';
            }
        });
    </script>
</body>
</html>