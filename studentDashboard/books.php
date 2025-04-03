<?php
session_start();
include '../db/config.php';
include './navbar.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to reserve a book.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['book_id'])) {
    $user_id = $_SESSION['user_id'];
    $book_id = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);

    if (!$book_id) {
        die("Invalid book selection.");
    }

    // ✅ Check if reservations table exists using EXISTS() for efficiency
    $check_table = $conn->query("SELECT 1 FROM reservations LIMIT 1");
    if (!$check_table) {
        die("Error: The 'reservations' table does not exist. Please check your database.");
    }

    // ✅ Check if the book is already reserved by the user
    $check = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND book_id = ?");
    $check->bind_param("ii", $user_id, $book_id);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) {
        echo "<script>alert('You have already reserved this book.');</script>";
    } else {
        // ✅ Insert reservation into the correct table
        $stmt = $conn->prepare("INSERT INTO reservations (user_id, book_id, reservation_date, status) VALUES (?, ?, NOW(), 'pending')");
        $stmt->bind_param("ii", $user_id, $book_id);

        if ($stmt->execute()) {
            echo "<script>alert('Book reserved successfully!');</script>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Books</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    
    <div class="container mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Available Books</h2>

        <!-- Search Bar -->
        <!-- Search Bar -->
<div class="flex justify-center mb-6">
    <form action="search.php" method="get" class="relative w-full max-w-xl bg-white p-4 rounded-2xl shadow-lg flex items-center space-x-2">
        <select name="type" class="p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none">
            <option value="title">Book Title</option>
            <option value="author">Author</option>
            <option value="isbn">ISBN</option>
            <option value="genre">Genre</option>
        </select>
        <input type="text" name="query" placeholder="Enter your search..." 
            class="flex-1 p-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
            required>
        <button type="submit" class="p-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>

        <!-- Books Grid -->
        <div id="booksGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php
            $result = $conn->query("SELECT * FROM books WHERE status = 'available'");

            while ($row = $result->fetch_assoc()):
            ?>
            <div class="bg-white rounded-lg shadow-md hover:shadow-xl transition transform hover:scale-105 cursor-pointer p-2"
                onclick="openModal('<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>')">
                
                <div class="w-full h-40 overflow-hidden rounded-md">
                    <img src="../images/<?= htmlspecialchars($row['images']) ?>" alt="Book Image" 
                        class="w-full h-full object-contain">
                </div>

                <div class="mt-2 text-center">
                    <h3 class="text-xs font-semibold text-gray-800"><?= htmlspecialchars($row['title']) ?></h3>
                    <p class="text-gray-600 text-xs"><?= htmlspecialchars($row['author']) ?></p>
                    <p class="text-blue-600 text-xs font-semibold">Copies: <?= htmlspecialchars($row['copies']) ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Modal -->
<!-- Modal -->
<div id="bookModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center hidden z-50">
    <div class="bg-white p-5 rounded-lg shadow-xl w-full max-w-md transform transition-all scale-95 relative mx-4 max-h-[90vh] overflow-y-auto">
        <button onclick="closeModal()" 
            class="absolute top-3 left-3 bg-gray-200 text-gray-700 px-3 py-1 rounded-lg hover:bg-gray-300 transition">
            ← Back
        </button>

        <!-- Book Image -->
        <div class="w-full h-52 flex items-center justify-center overflow-hidden rounded-md mb-4">
            <img id="modalImage" class="w-full h-full object-contain" alt="Book cover">
        </div>

        <!-- Book Details -->
        <div class="space-y-3">
            <h2 id="modalTitle" class="text-xl font-bold text-gray-800"></h2>
            
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <p class="text-gray-600">Author:</p>
                    <p id="modalAuthor" class="text-gray-800 font-medium"></p>
                </div>
                <div>
                    <p class="text-gray-600">Genre:</p>
                    <p id="modalGenre" class="text-gray-800 font-medium"></p>
                </div>
                <div>
                    <p class="text-gray-600">Publisher:</p>
                    <p id="modalPublisher" class="text-gray-800 font-medium"></p>
                </div>
                <div>
                    <p class="text-gray-600">Published:</p>
                    <p id="modalYear" class="text-gray-800 font-medium"></p>
                </div>
                <div>
                    <p class="text-gray-600">Edition:</p>
                    <p id="modalEdition" class="text-gray-800 font-medium"></p>
                </div>
                <div>
                    <p class="text-gray-600">ISBN:</p>
                    <p id="modalISBN" class="text-gray-800 font-medium"></p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 mt-2">
                <span id="modalStatus" class="px-2 py-1 text-xs font-semibold rounded-full"></span>
                <p id="modalCopies" class="text-blue-600 font-medium"></p>
            </div>
            
            <div class="border-t border-gray-200 my-3"></div>
            
            <div>
                <h3 class="font-semibold text-gray-700 mb-1">Description:</h3>
                <p id="modalDescription" class="text-gray-700 text-sm"></p>
            </div>
        </div>

        <!-- Reservation Form -->
        <div class="mt-6 sticky bottom-0 bg-white pt-4 pb-2">
            <form id="reservationForm" action="" method="post">
                <input type="hidden" name="book_id" id="modalBookIdReserve">
                
                <!-- Status Message (hidden by default) -->
                <div id="reservationStatus" class="mb-3 hidden">
                    <p class="p-2 rounded text-center"></p>
                </div>
                
                <button type="submit" name="reserve_book" 
                    class="w-full bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition 
                    disabled:opacity-50 disabled:cursor-not-allowed"
                    id="reserveButton">
                    Reserve Book
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Current book data for reference
    let currentBook = null;

    function openModal(bookData) {
        currentBook = JSON.parse(bookData);
        
        // Set modal content
        document.getElementById('modalImage').src = `../images/${currentBook.images}`;
        document.getElementById('modalTitle').textContent = currentBook.title;
        document.getElementById('modalAuthor').textContent = currentBook.author || 'Unknown';
        document.getElementById('modalGenre').textContent = currentBook.genre || 'Not specified';
        document.getElementById('modalPublisher').textContent = currentBook.publisher || 'Unknown';
        document.getElementById('modalYear').textContent = currentBook.publication_year || 'Unknown';
        document.getElementById('modalEdition').textContent = currentBook.edition || 'Unknown';
        document.getElementById('modalISBN').textContent = currentBook.ISBN || 'N/A';
        
        // Set status and copies
        const statusElement = document.getElementById('modalStatus');
        statusElement.textContent = currentBook.status === 'available' ? 'Available' : 'Issued';
        statusElement.className = `px-2 py-1 text-xs font-semibold rounded-full ${
            currentBook.status === 'available' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`;
        
        document.getElementById('modalCopies').textContent = 
            `${currentBook.copies} ${currentBook.copies === 1 ? 'copy' : 'copies'} available`;
        
        document.getElementById('modalDescription').textContent = 
            currentBook.book_summary || 'No description available.';
        document.getElementById('modalBookIdReserve').value = currentBook.id;
        
        // Enable/disable reserve button based on availability
        const reserveBtn = document.getElementById('reserveButton');
        reserveBtn.disabled = currentBook.copies < 1 || currentBook.status !== 'available';
        
        // Reset status message
        const statusDiv = document.getElementById('reservationStatus');
        statusDiv.classList.add('hidden');
        statusDiv.querySelector('p').textContent = '';
        
        // Show modal
        document.getElementById('bookModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('bookModal').classList.add('hidden');
        currentBook = null;
    }

    // Handle form submission with AJAX
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const statusDiv = document.getElementById('reservationStatus');
        const statusMsg = statusDiv.querySelector('p');
        const reserveBtn = document.getElementById('reserveButton');
        
        // Disable button during submission
        reserveBtn.disabled = true;
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            // Show success/error message
            statusDiv.classList.remove('hidden');
            
            if (text.includes('successfully')) {
                statusDiv.className = statusDiv.className.replace('hidden', 'block');
                statusMsg.className = 'p-2 rounded text-center bg-green-100 text-green-800';
                statusMsg.textContent = 'Book reserved successfully!';
                
                // Update UI to reflect reservation
                if (currentBook) {
                    currentBook.copies--;
                    if (currentBook.copies < 1) {
                        currentBook.status = 'issued';
                        document.getElementById('modalStatus').textContent = 'Issued';
                        document.getElementById('modalStatus').className = 'px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800';
                    }
                    document.getElementById('modalCopies').textContent = 
                        `${currentBook.copies} ${currentBook.copies === 1 ? 'copy' : 'copies'} available`;
                }
            } else {
                statusDiv.className = statusDiv.className.replace('hidden', 'block');
                statusMsg.className = 'p-2 rounded text-center bg-red-100 text-red-800';
                statusMsg.textContent = text.includes('already reserved') ? 
                    'You have already reserved this book.' : 
                    'Error reserving book. Please try again.';
            }
        })
        .catch(error => {
            statusDiv.classList.remove('hidden');
            statusDiv.className = statusDiv.className.replace('hidden', 'block');
            statusMsg.className = 'p-2 rounded text-center bg-red-100 text-red-800';
            statusMsg.textContent = 'Network error. Please try again.';
        })
        .finally(() => {
            // Re-enable button after 2 seconds
            setTimeout(() => {
                reserveBtn.disabled = currentBook.copies < 1 || currentBook.status !== 'available';
            }, 2000);
        });
    });
</script>
</body>
</html>
