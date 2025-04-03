<?php
include "../db/config.php";

// Initialize variables for form persistence
$formData = [
    'title' => '',
    'author' => '',
    'isbn' => '',
    'copies' => 1,
    'year' => '',
    'description' => '',
    'publisher' => '',
    'genre' => ''
];

$errorMessages = [];
$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $formData['title'] = trim($_POST["title"] ?? '');
    $formData['author'] = trim($_POST["author"] ?? '');
    $formData['isbn'] = trim($_POST["isbn"] ?? '');
    $formData['copies'] = intval($_POST["copies"] ?? 1);
    $formData['year'] = trim($_POST["year"] ?? '');
    $formData['description'] = trim($_POST["description"] ?? '');
    $formData['publisher'] = trim($_POST["publisher"] ?? '');
    $formData['genre'] = trim($_POST["genre"] ?? '');
    $imagePath = "";

    // Validate inputs
    if (empty($formData['title'])) {
        $errorMessages[] = "Book title is required";
    }
    if (empty($formData['author'])) {
        $errorMessages[] = "Author name is required";
    }
    if (empty($formData['isbn'])) {
        $errorMessages[] = "ISBN is required";
    }
    if ($formData['copies'] < 1) {
        $errorMessages[] = "Number of copies must be at least 1";
    }
    if (!empty($formData['year']) {
        $currentYear = date('Y');
        if (!is_numeric($formData['year']) || $formData['year'] < 1000 || $formData['year'] > $currentYear) {
            $errorMessages[] = "Publication year must be between 1000 and $currentYear";
        }
    }

    // Handle file upload if present
    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "../images/";
        $fileName = uniqid() . '_' . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = ["jpg", "jpeg", "png", "webp"];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($fileType, $allowedTypes)) {
            $errorMessages[] = "Only JPG, JPEG, PNG, and WEBP files are allowed";
        } elseif ($_FILES["image"]["size"] > $maxFileSize) {
            $errorMessages[] = "Image size must be less than 2MB";
        } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = $targetFilePath;
        } else {
            $errorMessages[] = "Sorry, there was an error uploading your file";
        }
    }

    // Insert book if no errors
    if (empty($errorMessages)) {
        $sql = "INSERT INTO books (title, author, ISBN, images, copies, year_published, description, publisher, genre) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssissss", 
            $formData['title'], 
            $formData['author'], 
            $formData['isbn'], 
            $imagePath, 
            $formData['copies'],
            $formData['year'] ?: NULL,
            $formData['description'],
            $formData['publisher'],
            $formData['genre']
        );

        if ($stmt->execute()) {
            $successMessage = "Book added successfully!";
            // Clear form data after successful submission
            $formData = [
                'title' => '',
                'author' => '',
                'isbn' => '',
                'copies' => 1,
                'year' => '',
                'description' => '',
                'publisher' => '',
                'genre' => ''
            ];
        } else {
            $errorMessages[] = "Database error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add a Book | Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .file-upload {
            position: relative;
            display: inline-block;
        }
        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
            color: #374151;
            transition: all 0.2s;
            cursor: pointer;
        }
        .file-upload-label:hover {
            background-color: #f3f4f6;
        }
        .file-upload-preview {
            max-width: 150px;
            max-height: 200px;
            margin-top: 1rem;
            display: none;
            border-radius: 0.25rem;
            border: 1px solid #e5e7eb;
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .description-counter {
            font-size: 0.75rem;
            color: #6b7280;
            text-align: right;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
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
                    <li><a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a></li>
                    <li><a href="books.php" class="text-gray-600 hover:text-gray-900">Books</a></li>
                    <li><a href="logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <!-- Form Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-book text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Add New Book</h2>
                        <p class="text-gray-600">Fill in all required fields to add a new book to the library</p>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <?php if (!empty($errorMessages)): ?>
                <div class="px-6 pt-4">
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 animate-fade-in">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle h-5 w-5 text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">There were errors with your submission:</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        <?php foreach ($errorMessages as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="px-6 pt-4">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 animate-fade-in">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle h-5 w-5 text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($successMessage) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Book Form -->
            <form method="post" enctype="multipart/form-data" class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Book Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Book Title *</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-heading text-gray-400"></i>
                                </div>
                                <input type="text" name="title" id="title" value="<?= htmlspecialchars($formData['title']) ?>" 
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                                       placeholder="Enter book title" required>
                            </div>
                        </div>

                        <!-- Author -->
                        <div>
                            <label for="author" class="block text-sm font-medium text-gray-700">Author *</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user-edit text-gray-400"></i>
                                </div>
                                <input type="text" name="author" id="author" value="<?= htmlspecialchars($formData['author']) ?>" 
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                                       placeholder="Enter author name" required>
                            </div>
                        </div>

                        <!-- ISBN -->
                        <div>
                            <label for="isbn" class="block text-sm font-medium text-gray-700">ISBN *</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-barcode text-gray-400"></i>
                                </div>
                                <input type="text" name="isbn" id="isbn" value="<?= htmlspecialchars($formData['isbn']) ?>" 
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                                       placeholder="Enter ISBN number" required>
                            </div>
                        </div>

                        <!-- Publisher -->
                        <div>
                            <label for="publisher" class="block text-sm font-medium text-gray-700">Publisher</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-building text-gray-400"></i>
                                </div>
                                <input type="text" name="publisher" id="publisher" value="<?= htmlspecialchars($formData['publisher']) ?>" 
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                                       placeholder="Enter publisher name">
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Genre -->
                        <div>
                            <label for="genre" class="block text-sm font-medium text-gray-700">Genre</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-tags text-gray-400"></i>
                                </div>
                                <input type="text" name="genre" id="genre" value="<?= htmlspecialchars($formData['genre']) ?>" 
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                                       placeholder="Enter book genre (e.g., Fiction, Science)">
                            </div>
                        </div>

                        <!-- Year of Publication -->
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700">Year of Publication</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-calendar-alt text-gray-400"></i>
                                </div>
                                <input type="number" name="year" id="year" value="<?= htmlspecialchars($formData['year']) ?>" 
                                       min="1000" max="<?= date('Y') ?>"
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                                       placeholder="Enter publication year">
                            </div>
                        </div>

                        <!-- Number of Copies -->
                        <div>
                            <label for="copies" class="block text-sm font-medium text-gray-700">Number of Copies *</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-copy text-gray-400"></i>
                                </div>
                                <input type="number" name="copies" id="copies" value="<?= htmlspecialchars($formData['copies']) ?>" 
                                       min="1" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                                       placeholder="Enter number of copies" required>
                            </div>
                        </div>

                        <!-- Book Cover Image -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Book Cover Image</label>
                            <div class="mt-1">
                                <div class="file-upload">
                                    <input type="file" name="image" id="image" class="file-upload-input" accept="image/*">
                                    <label for="image" class="file-upload-label">
                                        <i class="fas fa-cloud-upload-alt mr-2"></i>
                                        <span id="file-name">Choose an image...</span>
                                    </label>
                                </div>
                                <img id="image-preview" class="file-upload-preview" src="#" alt="Preview">
                                <p class="mt-1 text-xs text-gray-500">JPG, PNG, or WEBP (Max 2MB)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description (Full width) -->
                <div class="mt-6">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <div class="mt-1">
                        <textarea name="description" id="description" rows="4" 
                                  class="focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md shadow-sm"
                                  placeholder="Enter a brief description of the book"
                                  oninput="updateCounter(this)"><?= htmlspecialchars($formData['description']) ?></textarea>
                        <div class="description-counter">
                            <span id="char-count">0</span>/500 characters
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="books.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-plus-circle mr-2"></i> Add Book
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('image-preview');
            const fileName = document.getElementById('file-name');
            
            if (file) {
                fileName.textContent = file.name;
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'Choose an image...';
                preview.style.display = 'none';
            }
        });

        // Description character counter
        function updateCounter(textarea) {
            const charCount = textarea.value.length;
            document.getElementById('char-count').textContent = charCount;
            
            // Optionally add warning when approaching limit
            if (charCount > 450) {
                document.querySelector('.description-counter').classList.add('text-yellow-600');
                document.querySelector('.description-counter').classList.remove('text-gray-500');
            } else {
                document.querySelector('.description-counter').classList.remove('text-yellow-600');
                document.querySelector('.description-counter').classList.add('text-gray-500');
            }
        }

        // Initialize counter on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCounter(document.getElementById('description'));
            
            // If there are errors, scroll to the first error
            if (document.querySelector('.bg-red-50')) {
                document.querySelector('.bg-red-50').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        });
    </script>
</body>
</html>