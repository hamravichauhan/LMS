<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animated Navbar</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <nav class="bg-blue-600 p-4 text-white flex justify-between items-center shadow-lg">
        <h1 class="text-2xl font-extrabold tracking-wide hover:scale-105 transition-transform duration-300">
            Library System
        </h1>
        <ul class="hidden md:flex space-x-6">
            <li>
                <a href="index.php" class="relative after:block after:h-[2px] after:w-full after:bg-white after:scale-x-0 after:transition-transform after:duration-300 after:origin-center hover:after:scale-x-100">
                    Home
                </a>
            </li>
            <li>
                <a href="./books.php" class="relative after:block after:h-[2px] after:w-full after:bg-white after:scale-x-0 after:transition-transform after:duration-300 after:origin-center hover:after:scale-x-100">
                    Books
                </a>
            </li>
            <!-- <li>
                <a href="issued_books.php" class="relative after:block after:h-[2px] after:w-full after:bg-white after:scale-x-0 after:transition-transform after:duration-300 after:origin-center hover:after:scale-x-100">
                    My Issued Books
                </a>
            </li> -->
            <li>
                <a href="reservations.php" class="relative after:block after:h-[2px] after:w-full after:bg-white after:scale-x-0 after:transition-transform after:duration-300 after:origin-center hover:after:scale-x-100">
                    Reservations
                </a>
            </li>
            <li>
                <a href="../auth/logout.php" class="relative after:block after:h-[2px] after:w-full after:bg-red-400 after:scale-x-0 after:transition-transform after:duration-300 after:origin-center hover:after:scale-x-100 hover:text-red-300">
                    Logout
                </a>
            </li>
        </ul>
        
        <!-- Mobile Menu Button -->
        <button id="menu-btn" class="md:hidden text-3xl focus:outline-none">
            ☰
        </button>
    </nav>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-blue-700 text-white p-4 space-y-3 text-center">
        <a href="dashboard.php" class="block hover:bg-blue-500 p-2 rounded transition">Home</a>
        <a href="?books" class="block hover:bg-blue-500 p-2 rounded transition">Books</a>
    
        <a href="?reservations" class="block hover:bg-blue-500 p-2 rounded transition">Reservations</a>
        <a href="profile.php" class="block hover:bg-blue-500 p-2 rounded transition">profile</a>
        <a href="./auth/logout.php" class="block hover:bg-red-500 p-2 rounded transition">Logout</a>
    </div>
 
    <script>
        const menuBtn = document.getElementById('menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('animate-fadeIn');
        });
    </script>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }
    </style>

</body>
</html>
<