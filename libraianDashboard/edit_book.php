<?php
include "../db/config.php";
session_start();

// Initialize variables
$errorMessages = [];
$successMessage = '';
$book = null;

// Validate and get book ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    $_SESSION['error'] = "Invalid book ID";
    header("Location: books.php");
    exit();
}
$book_id = intval($_GET["id"]);

// Fetch book data
$sql = "SELECT * FROM books WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    $_SESSION['error'] = "Book not found";
    header("Location: books.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $title = trim($_POST["title"] ?? '');
    $author = trim($_POST["author"] ?? '');
    $isbn = trim($_POST["isbn"] ?? '');
    $genre = trim($_POST["genre"] ?? '');
    $publisher = trim($_POST["publisher"] ?? '');
    $publication_year = trim($_POST["publication_year"] ?? '');
    $edition = trim($_POST["edition"] ?? '');
    $copies = intval($_POST["copies"] ?? 1);
    $book_summary = trim($_POST["book_summary"] ?? '');
    $imagePath = $book["images"]; // Keep existing image by default

    // Input validation
    if (empty($title)) {
        $errorMessages[] = "Book title is required";
    } elseif (strlen($title) > 255) {
        $errorMessages[] = "Book title must be less than 255 characters";
    }

    if (empty($author)) {
        $errorMessages[] = "Author name is required";
    } elseif (strlen($author) > 100) {
        $errorMessages[] = "Author name must be less than 100 characters";
    }

    if (empty($isbn)) {
        $errorMessages[] = "ISBN is required";
    } elseif (!preg_match('/^[0-9\-]+$/', $isbn)) {
        $errorMessages[] = "ISBN can only contain numbers and hyphens";
    }

    if (empty($genre)) {
        $errorMessages[] = "Genre is required";
    }

    if (empty($publisher)) {
        $errorMessages[] = "Publisher is required";
    }

    if (empty($publication_year) || !is_numeric($publication_year) || 
        $publication_year < 1000 || $publication_year > date('Y')) {
        $errorMessages[] = "Please enter a valid publication year";
    }

    if ($copies < 1) {
        $errorMessages[] = "Number of copies must be at least 1";
    }

    // Handle file upload
    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "../images/";
        $fileName = uniqid() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = ["jpg", "jpeg", "png", "webp"];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($fileType, $allowedTypes)) {
            $errorMessages[] = "Only JPG, JPEG, PNG, and WEBP files are allowed";
        } elseif ($_FILES["image"]["size"] > $maxFileSize) {
            $errorMessages[] = "Image size must be less than 2MB";
        } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            // Delete old image if it exists
            if (!empty($book["images"]) && file_exists($targetDir . $book["images"])) {
                unlink($targetDir . $book["images"]);
            }
            $imagePath = $fileName;
        } else {
            $errorMessages[] = "Sorry, there was an error uploading your file";
        }
    }

    // Update book if no errors
    if (empty($errorMessages)) {
        $sql = "UPDATE books SET 
                title = ?, 
                author = ?, 
                ISBN = ?, 
                images = ?, 
                genre = ?, 
                publisher = ?, 
                publication_year = ?, 
                edition = ?, 
                copies = ?, 
                book_summary = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssisi", 
            $title, 
            $author, 
            $isbn, 
            $imagePath, 
            $genre, 
            $publisher, 
            $publication_year, 
            $edition, 
            $copies, 
            $book_summary, 
            $book_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Book updated successfully!";
            header("Location: books.php");
            exit();
        } else {
            $errorMessages[] = "Database error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book | Library Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
            border-radius: 0.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-group {
            @apply mb-6;
        }
        .form-label {
            @apply block text-sm font-medium text-gray-700 mb-1;
        }
        .input-group {
            @apply relative rounded-md shadow-sm;
        }
        .input-icon {
            @apply absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400;
        }
        .form-input {
            @apply block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm;
        }
        .form-textarea {
            @apply block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm;
        }
        .input-hint {
            @apply text-xs text-gray-500 mt-1;
        }
        .file-upload-btn {
            @apply inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer;
        }
        .primary-btn {
            @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
        }
        .secondary-btn {
            @apply inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
        }
        .description-counter {
            @apply text-xs text-gray-500 mt-1 text-right;
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
                        <i class="fas fa-edit text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Edit Book</h2>
                        <p class="text-gray-600">Update the details of this book</p>
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

            <!-- Book Form -->
            <form method="post" enctype="multipart/form-data" class="px-6 py-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Book Title -->
                        <div class="form-group">
                            <label for="title" class="form-label">Book Title <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-heading"></i>
                                </span>
                                <input type="text" name="title" id="title" 
                                       value="<?= htmlspecialchars($book['title']) ?>" 
                                       class="form-input" 
                                       placeholder="Enter book title" required
                                       maxlength="255">
                            </div>
                        </div>

                        <!-- Author -->
                        <div class="form-group">
                            <label for="author" class="form-label">Author <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-user-edit"></i>
                                </span>
                                <input type="text" name="author" id="author" 
                                       value="<?= htmlspecialchars($book['author']) ?>" 
                                       class="form-input" 
                                       placeholder="Enter author name" required
                                       maxlength="100">
                            </div>
                        </div>

                        <!-- ISBN -->
                        <div class="form-group">
                            <label for="isbn" class="form-label">ISBN <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-barcode"></i>
                                </span>
                                <input type="text" name="isbn" id="isbn" 
                                       value="<?= htmlspecialchars($book['ISBN']) ?>" 
                                       class="form-input" 
                                       placeholder="Enter ISBN number" required
                                       pattern="[0-9\-]+"
                                       title="ISBN can only contain numbers and hyphens">
                            </div>
                        </div>

                        <!-- Publisher -->
                        <div class="form-group">
                            <label for="publisher" class="form-label">Publisher <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-building"></i>
                                </span>
                                <input type="text" name="publisher" id="publisher" 
                                       value="<?= htmlspecialchars($book['publisher']) ?>" 
                                       class="form-input" 
                                       placeholder="Enter publisher name" required>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Genre -->
                        <div class="form-group">
                            <label for="genre" class="form-label">Genre <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-tags"></i>
                                </span>
                                <select name="genre" id="genre" class="form-input" required>
                                    <option value="">Select a genre</option>
                                    <option value="Fiction" <?= ($book['genre'] == 'Fiction') ? 'selected' : '' ?>>Fiction</option>
                                    <option value="Non-Fiction" <?= ($book['genre'] == 'Non-Fiction') ? 'selected' : '' ?>>Non-Fiction</option>
                                    <option value="Science Fiction" <?= ($book['genre'] == 'Science Fiction') ? 'selected' : '' ?>>Science Fiction</option>
                                    <option value="Biography" <?= ($book['genre'] == 'Biography') ? 'selected' : '' ?>>Biography</option>
                                    <option value="History" <?= ($book['genre'] == 'History') ? 'selected' : '' ?>>History</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Year of Publication -->
                        <div class="form-group">
                            <label for="publication_year" class="form-label">Publication Year <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </span>
                                <input type="text" 
                                       name="publication_year" 
                                       id="publication_year" 
                                       value="<?= htmlspecialchars($book['publication_year']) ?>" 
                                       class="form-input year-picker" 
                                       placeholder="Select year"
                                       required
                                       readonly>
                            </div>
                        </div>

                        <!-- Number of Copies -->
                        <div class="form-group">
                            <label for="copies" class="form-label">Copies Available <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-copy"></i>
                                </span>
                                <input type="number" name="copies" id="copies" 
                                       value="<?= htmlspecialchars($book['copies']) ?>" 
                                       min="1" 
                                       class="form-input" 
                                       placeholder="Enter number of copies" required>
                            </div>
                        </div>

                        <!-- Edition -->
                        <div class="form-group">
                            <label for="edition" class="form-label">Edition</label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-bookmark"></i>
                                </span>
                                <input type="text" name="edition" id="edition" 
                                       value="<?= htmlspecialchars($book['edition']) ?>" 
                                       class="form-input" 
                                       placeholder="Enter edition (e.g., First Edition)">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Book Cover Image -->
                <div class="form-group mt-6">
                    <label class="form-label">Book Cover Image</label>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="relative">
                            <label class="file-upload-btn">
                                <i class="fas fa-cloud-upload-alt mr-2"></i>
                                <span id="file-name">Choose new image...</span>
                                <input type="file" name="image" id="image" class="file-upload-input" accept="image/*">
                            </label>
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, or WEBP (Max 2MB)</p>
                        </div>
                        <?php if (!empty($book['images'])): ?>
                            <div id="current-image-container" class="flex items-center gap-2">
                                <img src="../images/<?= htmlspecialchars($book['images']) ?>" 
                                     alt="Current book cover" 
                                     class="file-upload-preview">
                                <button type="button" id="remove-image-btn" class="text-red-500 text-xs">
                                    <i class="fas fa-times mr-1"></i> Remove
                                </button>
                                <input type="hidden" id="current-image" name="current_image" value="<?= htmlspecialchars($book['images']) ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Book Summary -->
                <div class="form-group mt-6">
                    <label for="book_summary" class="form-label">Book Summary</label>
                    <div class="relative">
                        <textarea name="book_summary" id="book_summary" rows="5" 
                                  class="form-textarea"
                                  placeholder="Enter a brief description of the book's content and themes"
                                  oninput="updateCounter(this)"><?= htmlspecialchars($book['book_summary']) ?></textarea>
                        <div class="description-counter">
                            <span id="char-count"><?= strlen($book['book_summary']) ?></span>/500 characters
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="mt-8 flex justify-end space-x-3">
                    <a href="books.php" class="secondary-btn">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit" class="primary-btn">
                        <i class="fas fa-save mr-2"></i> Update Book
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Include Flatpickr for year picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        // Initialize year picker
        flatpickr("#publication_year", {
            dateFormat: "Y",
            minDate: "1000",
            maxDate: new Date().getFullYear().toString(),
            defaultDate: "<?= htmlspecialchars($book['publication_year']) ?>",
            static: true
        });

        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileName = document.getElementById('file-name');
            
            if (file) {
                fileName.textContent = file.name;
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    // Create or update preview image
                    let preview = document.getElementById('image-preview');
                    if (!preview) {
                        preview = document.createElement('img');
                        preview.id = 'image-preview';
                        preview.className = 'file-upload-preview';
                        document.querySelector('#current-image-container').prepend(preview);
                    }
                    preview.src = event.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'Choose new image...';
            }
        });

        // Remove image functionality
        document.getElementById('remove-image-btn')?.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this image?')) {
                document.getElementById('image').value = '';
                document.getElementById('current-image').value = '';
                document.getElementById('image-preview')?.remove();
                this.remove();
            }
        });

        // Character counter for book summary
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
            updateCounter(document.getElementById('book_summary'));
            
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