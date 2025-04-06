<?php
session_start();
include '../db/config.php';

// Fetch Latest Books
$latest_books = $conn->query("SELECT id, title, images, author FROM books ORDER BY created_at DESC LIMIT 10");

// Fetch Latest Searched Books
$latest_searched = $conn->query("SELECT DISTINCT book_name FROM search_history ORDER BY searched_at DESC LIMIT 10");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Digital Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#10B981',
                        dark: '#1F2937',
                        light: '#F9FAFB'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans antialiased">
  <?php include './navbar.php'; ?>
    <!-- Hero Section -->
    <div class="relative bg-gradient-to-r from-primary to-purple-600 text-white py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome to Your Digital Library</h1>
            <p class="text-xl md:text-2xl mb-8">Discover, Learn, and Explore Thousands of Books</p>
            
            <!-- Search Bar -->
            <div class="max-w-2xl mx-auto">
                <form action="search.php" method="post" class="relative">
                    <input type="text" name="book_name" placeholder="Search for books, authors, or topics..." 
                        class="w-full p-4 pl-12 pr-6 rounded-full text-dark focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-primary shadow-lg">
                    <button type="submit" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Search Results (if any) -->
        <?php if (!empty($books)): ?>
            <section class="mb-12">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-dark">Search Results</h2>
                    <span class="text-gray-500"><?= count($books) ?> results found</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php while ($row = $books->fetch_assoc()): ?>
                        <a href="book_details.php?id=<?= $row['id'] ?>" class="group block bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                            <div class="relative pb-[150%] overflow-hidden">
                                <img src="<?= htmlspecialchars($row['images']) ?>" 
                                     alt="<?= htmlspecialchars($row['title']) ?>" 
                                     class="absolute h-full w-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-lg mb-1 truncate"><?= htmlspecialchars($row['title']) ?></h3>
                                <p class="text-gray-600"><?= htmlspecialchars($row['author']) ?></p>
                                <div class="mt-3 flex justify-between items-center">
                                    <span class="text-sm text-primary font-medium">Available</span>
                                    <button class="text-secondary hover:text-secondary-dark">
                                        <i class="far fa-bookmark"></i>
                                    </button>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Latest Books Section -->
        <section class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-dark">Recently Added Books</h2>
                <a href="books.php" class="text-primary hover:underline">View All</a>
            </div>
            
            <div class="relative">
                <div class="swiper mySwiperBooks">
                    <div class="swiper-wrapper pb-10">
                        <?php while ($row = $latest_books->fetch_assoc()): ?>
                            <div class="swiper-slide">
                                <a href="book_details.php?id=<?= $row['id'] ?>" class="group block bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 h-full">
                                    <div class="relative pb-[150%] overflow-hidden">
                                    <img src="../images/<?= htmlspecialchars($row['images']) ?>"
                                             alt="<?= htmlspecialchars($row['title']) ?>" 
                                             
                                             class="absolute h-full w-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-bold text-lg mb-1 truncate"><?= htmlspecialchars($row['title']) ?></h3>
                                        <p class="text-gray-600 text-sm"><?= htmlspecialchars($row['author']) ?></p>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="swiper-button-next next-books hidden md:flex items-center justify-center w-12 h-12 rounded-full bg-white shadow-md hover:bg-gray-100 absolute top-1/2 -right-6 transform -translate-y-1/2 z-10 cursor-pointer">
                    <i class="fas fa-chevron-right text-primary"></i>
                </div>
                <div class="swiper-button-prev prev-books hidden md:flex items-center justify-center w-12 h-12 rounded-full bg-white shadow-md hover:bg-gray-100 absolute top-1/2 -left-6 transform -translate-y-1/2 z-10 cursor-pointer">
                    <i class="fas fa-chevron-left text-primary"></i>
                </div>
            </div>
        </section>

        <!-- Popular Searches Section -->
        <section class="mb-12">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-dark">Recent Searches</h2>
        <a href="recent_searches.php" class="text-primary hover:underline">View All</a>
    </div>
    
    <div class="bg-white rounded-xl shadow-md p-6">
        <?php
        // Fetch recent searches for the current user
        $user_id = $_SESSION['user_id'] ?? 0;
        $recent_searches_query = $conn->prepare("
            SELECT book_name, MAX(searched_at) as last_searched 
            FROM search_history 
            WHERE user_id = ?
            GROUP BY book_name 
            ORDER BY last_searched DESC 
            LIMIT 10
        ");
        $recent_searches_query->bind_param("i", $user_id);
        $recent_searches_query->execute();
        $recent_searches = $recent_searches_query->get_result();
        ?>
        
        <?php if ($recent_searches->num_rows > 0): ?>
            <div class="flex flex-wrap gap-3">
                <?php while ($search = $recent_searches->fetch_assoc()): ?>
                    <a href="search.php?q=<?= urlencode($search['book_name']) ?>" 
                       class="inline-block px-4 py-2 bg-gray-100 hover:bg-primary hover:text-white rounded-full text-sm font-medium transition-colors duration-200">
                        <?= htmlspecialchars($search['book_name']) ?>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500">No recent searches found.</p>
        <?php endif; ?>
    </div>
</section>

        <!-- Quick Links Section -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-dark mb-6">Quick Access</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <a href="#" class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow duration-300 group">
                    <div class="w-16 h-16 bg-primary/10 text-primary rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-primary group-hover:text-white transition-colors duration-300">
                        <i class="fas fa-book-open text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-lg">E-Books</h3>
                    <p class="text-gray-600 text-sm mt-1">Browse our collection</p>
                </a>
                <a href="books.php" class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow duration-300 group">
                    <div class="w-16 h-16 bg-secondary/10 text-secondary rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-secondary group-hover:text-white transition-colors duration-300">
                        <i class="fas fa-search text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-lg">Advanced Search</h3>
                    <p class="text-gray-600 text-sm mt-1">Find specific books</p>
                </a>
                <a href="reservations.php" class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow duration-300 group">
                    <div class="w-16 h-16 bg-purple-500/10 text-purple-500 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-500 group-hover:text-white transition-colors duration-300">
                        <i class="fas fa-history text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-lg">Reading History</h3>
                    <p class="text-gray-600 text-sm mt-1">Your past reads</p>
                </a>
                <a href="#" class="bg-white rounded-xl shadow-md p-6 text-center hover:shadow-lg transition-shadow duration-300 group">
                    <div class="w-16 h-16 bg-yellow-500/10 text-yellow-500 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-yellow-500 group-hover:text-white transition-colors duration-300">
                        <i class="fas fa-bookmark text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-lg">Bookmarks</h3>
                    <p class="text-gray-600 text-sm mt-1">Saved for later</p>
                </a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">Digital Library</h3>
                <p class="text-gray-400">Providing access to thousands of books and resources for students worldwide.</p>
            </div>
            <div>
                <h4 class="font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Home</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Browse</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">New Releases</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Popular</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-4">Help</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">FAQ</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Tutorials</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-white transition-colors">Feedback</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-4">Connect With Us</h4>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors text-xl"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors text-xl"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors text-xl"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors text-xl"><i class="fab fa-linkedin"></i></a>
                </div>
                <p class="text-gray-400 mt-4">Subscribe to our newsletter</p>
                <form class="mt-2 flex">
                    <input type="email" placeholder="Your email" class="px-3 py-2 text-dark rounded-l focus:outline-none w-full">
                    <button class="bg-primary px-4 py-2 rounded-r hover:bg-primary-dark transition-colors">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="max-w-7xl mx-auto mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
            <p>&copy; <?= date('Y') ?> Digital Library. All rights reserved.</p>
        </div>
    </footer>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
    <script>
        // Initialize Swiper for Books
        const swiperBooks = new Swiper(".mySwiperBooks", {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            navigation: {
                nextEl: ".next-books",
                prevEl: ".prev-books",
            },
            breakpoints: {
                640: { slidesPerView: 2 },
                768: { slidesPerView: 3 },
                1024: { slidesPerView: 4 },
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>