<?php
session_start();
include '../db/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

// Fetch Latest Books with fallback image
$latest_books = $conn->query("SELECT id, title, author, images FROM books ORDER BY created_at DESC LIMIT 10");

// Fetch Latest Searched Books with prepared statement
$search_stmt = $conn->prepare("SELECT DISTINCT b.id, b.title, b.author, b.images 
                             FROM search_history sh
                             JOIN books b ON sh.book_name = b.title
                             WHERE sh.user_id = ?
                             ORDER BY sh.searched_at DESC LIMIT 10");
$search_stmt->bind_param("i", $_SESSION['user_id']);
$search_stmt->execute();
$latest_searched = $search_stmt->get_result();

// Handle search if form submitted
$search_results = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_name'])) {
    $search_term = '%' . $conn->real_escape_string(trim($_POST['book_name'])) . '%';
    $search_query = $conn->prepare("SELECT id, title, author, images FROM books 
                                   WHERE title LIKE ? OR author LIKE ? LIMIT 12");
    $search_query->bind_param("ss", $search_term, $search_term);
    $search_query->execute();
    $search_results = $search_query->get_result();
    
    // Log search in history
    if ($search_results->num_rows > 0) {
        $log_stmt = $conn->prepare("INSERT INTO search_history (user_id, book_name) VALUES (?, ?)");
        $book_name = trim($_POST['book_name']);
        $log_stmt->bind_param("is", $_SESSION['user_id'], $book_name);
        $log_stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .swiper {
            width: 100%;
            height: 100%;
            padding: 20px 0;
        }
        .swiper-slide {
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: transform 0.3s ease;
        }
        .swiper-slide:hover {
            transform: translateY(-5px);
        }
        .book-card {
            height: 380px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .book-cover {
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .search-result-card {
            transition: all 0.3s ease;
        }
        .search-result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .empty-state {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include './navbar.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-3xl font-extrabold text-white sm:text-4xl">
                Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?>!
            </h1>
            <p class="mt-3 text-xl text-blue-100">
                Discover your next great read in our library collection
            </p>
        </div>
    </div>

    <!-- Search Section -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form action="" method="post" class="relative">
            <div class="flex shadow-lg rounded-lg overflow-hidden">
                <input type="text" name="book_name" placeholder="Search by title or author..." 
                    class="flex-grow p-4 pl-12 focus:outline-none text-gray-700"
                    required minlength="2" maxlength="100">
                <button type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 flex items-center justify-center transition duration-150">
                    <i class="fas fa-search mr-2"></i> Search
                </button>
            </div>
            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">
                <i class="fas fa-search text-xl"></i>
            </div>
        </form>
    </div>

    <!-- Search Results -->
    <?php if (isset($search_results)): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">
            Search Results for "<?= htmlspecialchars(trim($_POST['book_name'])) ?>"
            <span class="text-gray-500 text-lg">(<?= $search_results->num_rows ?> results)</span>
        </h2>
        
        <?php if ($search_results->num_rows > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php while ($book = $search_results->fetch_assoc()): ?>
            <a href="book_details.php?id=<?= $book['id'] ?>" 
               class="search-result-card bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg">
                <div class="h-48 overflow-hidden">
                    <img src="<?= !empty($book['images']) ? htmlspecialchars($book['images']) : '../images/default-book.jpg' ?>" 
                         alt="<?= htmlspecialchars($book['title']) ?>" 
                         class="w-full h-full object-cover">
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-lg text-gray-800 truncate"><?= htmlspecialchars($book['title']) ?></h3>
                    <p class="text-gray-600"><?= htmlspecialchars($book['author']) ?></p>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state p-8 text-center rounded-lg">
            <i class="fas fa-book-open text-5xl text-gray-400 mb-4"></i>
            <h3 class="text-xl font-medium text-gray-700">No books found</h3>
            <p class="text-gray-500 mt-2">Try a different search term</p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Latest Books Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Recently Added Books</h2>
            <a href="all_books.php" class="text-blue-600 hover:text-blue-800 flex items-center">
                View all <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>

        <div class="swiper latest-books-swiper">
            <div class="swiper-wrapper">
                <?php if ($latest_books->num_rows > 0): ?>
                    <?php while ($book = $latest_books->fetch_assoc()): ?>
                    <div class="swiper-slide">
                        <a href="book_details.php?id=<?= $book['id'] ?>" class="book-card bg-white rounded-lg shadow-md p-4 block">
                            <div class="flex justify-center mb-4">
                                <img src="<?= !empty($book['images']) ? htmlspecialchars($book['images']) : '../images/default-book.jpg' ?>" 
                                     alt="<?= htmlspecialchars($book['title']) ?>" 
                                     class="book-cover">
                            </div>
                            <h3 class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($book['title']) ?></h3>
                            <p class="text-gray-600 text-sm mt-1"><?= htmlspecialchars($book['author']) ?></p>
                            <button class="mt-3 bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-medium">
                                View Details
                            </button>
                        </a>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-full empty-state p-8 text-center rounded-lg">
                        <i class="fas fa-book-open text-5xl text-gray-400 mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-700">No books available</h3>
                        <p class="text-gray-500 mt-2">Check back later for new additions</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="swiper-button-next text-blue-600"></div>
            <div class="swiper-button-prev text-blue-600"></div>
        </div>
    </div>

    <!-- Recently Searched Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Your Recent Searches</h2>
        
        <div class="swiper recent-searches-swiper">
            <div class="swiper-wrapper">
                <?php if ($latest_searched->num_rows > 0): ?>
                    <?php while ($search = $latest_searched->fetch_assoc()): ?>
                    <div class="swiper-slide">
                        <a href="book_details.php?id=<?= $search['id'] ?>" class="book-card bg-white rounded-lg shadow-md p-4 block">
                            <div class="flex justify-center mb-4">
                                <img src="<?= !empty($search['images']) ? htmlspecialchars($search['images']) : '../images/default-book.jpg' ?>" 
                                     alt="<?= htmlspecialchars($search['title']) ?>" 
                                     class="book-cover">
                            </div>
                            <h3 class="font-semibold text-gray-800 truncate"><?= htmlspecialchars($search['title']) ?></h3>
                            <p class="text-gray-600 text-sm mt-1"><?= htmlspecialchars($search['author']) ?></p>
                            <button class="mt-3 bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-medium">
                                View Again
                            </button>
                        </a>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-full empty-state p-8 text-center rounded-lg">
                        <i class="fas fa-search text-5xl text-gray-400 mb-4"></i>
                        <h3 class="text-xl font-medium text-gray-700">No recent searches</h3>
                        <p class="text-gray-500 mt-2">Your search history will appear here</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="swiper-button-next text-blue-600"></div>
            <div class="swiper-button-prev text-blue-600"></div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center">&copy; <?= date('Y') ?> Library Management System. All rights reserved.</p>
        </div>
    </footer>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script>
        // Initialize Swipers
        const bookSwiper = new Swiper('.latest-books-swiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            navigation: {
                nextEl: '.latest-books-swiper .swiper-button-next',
                prevEl: '.latest-books-swiper .swiper-button-prev',
            },
            breakpoints: {
                640: { slidesPerView: 2 },
                768: { slidesPerView: 3 },
                1024: { slidesPerView: 4 },
            }
        });

        const searchSwiper = new Swiper('.recent-searches-swiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            navigation: {
                nextEl: '.recent-searches-swiper .swiper-button-next',
                prevEl: '.recent-searches-swiper .swiper-button-prev',
            },
            breakpoints: {
                640: { slidesPerView: 2 },
                768: { slidesPerView: 3 },
                1024: { slidesPerView: 4 },
            }
        });
    </script>
</body>
</html>