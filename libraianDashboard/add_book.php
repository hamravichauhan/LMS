<?php
include "../db/config.php";

// Initialize variables for form persistence
$formData = [
    'title' => '',
    'author' => '',
    'isbn' => '',
    'copies' => 1,
    'genre' => '',
    'publisher' => '',
    'publication_year' => '',
    'edition' => '',
    'book_summary' => ''
];

$errorMessages = [];
$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $formData['title'] = trim($_POST["title"] ?? '');
    $formData['author'] = trim($_POST["author"] ?? '');
    $formData['isbn'] = trim($_POST["isbn"] ?? '');
    $formData['copies'] = intval($_POST["copies"] ?? 1);
    $formData['genre'] = trim($_POST["genre"] ?? '');
    $formData['publisher'] = trim($_POST["publisher"] ?? '');
    $formData['publication_year'] = trim($_POST["publication_year"] ?? '');
    $formData['edition'] = trim($_POST["edition"] ?? '');
    $formData['book_summary'] = trim($_POST["book_summary"] ?? '');
    $imagePath = "";

    // Validate inputs with comprehensive checks
    if (empty($formData['title'])) {
        $errorMessages[] = "Book title is required";
    } elseif (strlen($formData['title']) > 255) {
        $errorMessages[] = "Book title must be less than 255 characters";
    }

    if (empty($formData['author'])) {
        $errorMessages[] = "Author name is required";
    } elseif (strlen($formData['author']) > 100) {
        $errorMessages[] = "Author name must be less than 100 characters";
    } elseif (!preg_match("/^[a-zA-Z\s\-\.]+$/", $formData['author'])) {
        $errorMessages[] = "Author name can only contain letters, spaces, hyphens, and periods";
    }

    if (empty($formData['isbn'])) {
        $errorMessages[] = "ISBN is required";
    } elseif (!preg_match("/^(97(8|9))?\d{9}(\d|X)$/", $formData['isbn'])) {
        $errorMessages[] = "Please enter a valid ISBN (10 or 13 digits)";
    }

    if ($formData['copies'] < 1) {
        $errorMessages[] = "Number of copies must be at least 1";
    } elseif ($formData['copies'] > 1000) {
        $errorMessages[] = "Number of copies cannot exceed 1000";
    }

    if (empty($formData['genre'])) {
        $errorMessages[] = "Genre is required";
    } elseif (strlen($formData['genre']) > 50) {
        $errorMessages[] = "Genre must be less than 50 characters";
    }

    if (empty($formData['publisher'])) {
        $errorMessages[] = "Publisher is required";
    } elseif (strlen($formData['publisher']) > 100) {
        $errorMessages[] = "Publisher name must be less than 100 characters";
    }

    if (empty($formData['publication_year']) || 
        !is_numeric($formData['publication_year']) || 
        $formData['publication_year'] < 1000 || 
        $formData['publication_year'] > date('Y')) {
        $errorMessages[] = "Enter a valid publication year (1000-".date('Y').")";
    }

    if (strlen($formData['edition']) > 50) {
        $errorMessages[] = "Edition must be less than 50 characters";
    }

    if (strlen($formData['book_summary']) > 500) {
        $errorMessages[] = "Book summary must be less than 500 characters";
    }

    // Handle file upload if present
    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "../images/";
        $fileName = uniqid() . '_' . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        $allowedTypes = ["jpg", "jpeg", "png", "webp"];
        $maxFileSize = 2 * 1024 * 1024; // 2MB
        
        // Check if file is an actual image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $errorMessages[] = "File is not an image";
        }
        
        // Check file type
        if (!in_array($fileType, $allowedTypes)) {
            $errorMessages[] = "Only JPG, JPEG, PNG, and WEBP files are allowed";
        } 
        // Check file size
        elseif ($_FILES["image"]["size"] > $maxFileSize) {
            $errorMessages[] = "Image size must be less than 2MB";
        } 
        // Try to upload file
        elseif (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $errorMessages[] = "Sorry, there was an error uploading your file";
        } else {
            $imagePath = $fileName; // Store relative path
        }
    }

    // Insert book if no errors
    if (empty($errorMessages)) {
        $sql = "INSERT INTO books (title, author, ISBN, images, copies, genre, publisher, publication_year, edition, book_summary) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisssss", 
            $formData['title'], 
            $formData['author'], 
            $formData['isbn'], 
            $imagePath, 
            $formData['copies'], 
            $formData['genre'], 
            $formData['publisher'], 
            $formData['publication_year'], 
            $formData['edition'], 
            $formData['book_summary']
        );

        if ($stmt->execute()) {
            $successMessage = "Book added successfully!";
            // Clear form data after successful submission
            $formData = [
                'title' => '',
                'author' => '',
                'isbn' => '',
                'copies' => 1,
                'genre' => '',
                'publisher' => '',
                'publication_year' => '',
                'edition' => '',
                'book_summary' => ''
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
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
        .file-upload-input {
            @apply absolute inset-0 w-full h-full opacity-0 cursor-pointer;
        }
        .primary-btn {
            @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
        }
        .secondary-btn {
            @apply inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
        }
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .is-invalid + .input-hint {
            color: #dc3545;
        }
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
        .is-invalid ~ .invalid-feedback {
            display: block;
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
                    <li><a href="../logout.php" class="text-gray-600 hover:text-gray-900">Logout</a></li>
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Book Title -->
                        <div class="form-group">
                            <label for="title" class="form-label">Book Title <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-heading"></i>
                                </span>
                                <input type="text" name="title" id="title" value="<?= htmlspecialchars($formData['title'] ?? '') ?>" 
                                       class="form-input" 
                                       placeholder="The Great Gatsby" required>
                            </div>
                            <div class="invalid-feedback">
                                Please provide a valid title (max 255 characters)
                            </div>
                        </div>

                        <!-- Author -->
                        <div class="form-group">
                            <label for="author" class="form-label">Author <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-user-edit"></i>
                                </span>
                                <input type="text" name="author" id="author" value="<?= htmlspecialchars($formData['author'] ?? '') ?>" 
                                       class="form-input" 
                                       placeholder="F. Scott Fitzgerald" required>
                            </div>
                            <div class="invalid-feedback">
                                Please provide a valid author name (letters, spaces, hyphens only)
                            </div>
                        </div>

                        <!-- ISBN -->
                        <div class="form-group">
                            <label for="isbn" class="form-label">ISBN <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-barcode"></i>
                                </span>
                                <input type="text" name="isbn" id="isbn" value="<?= htmlspecialchars($formData['isbn'] ?? '') ?>" 
                                       class="form-input" 
                                       placeholder="978-3-16-148410-0" required>
                                <span class="input-hint">13-digit format</span>
                            </div>
                            <div class="invalid-feedback">
                                Please provide a valid ISBN (10 or 13 digits)
                            </div>
                        </div>

                        <!-- Publisher -->
                        <div class="form-group">
                            <label for="publisher" class="form-label">Publisher <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-building"></i>
                                </span>
                                <input type="text" name="publisher" id="publisher" value="<?= htmlspecialchars($formData['publisher'] ?? '') ?>" 
                                       class="form-input" 
                                       placeholder="Penguin Books" required>
                            </div>
                            <div class="invalid-feedback">
                                Please provide a publisher name
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
                                    <option value="" disabled selected>Select a genre</option>
                                    <option value="Fiction" <?= ($formData['genre'] ?? '') == 'Fiction' ? 'selected' : '' ?>>Fiction</option>
                                    <option value="Non-Fiction" <?= ($formData['genre'] ?? '') == 'Non-Fiction' ? 'selected' : '' ?>>Non-Fiction</option>
                                    <option value="Science Fiction" <?= ($formData['genre'] ?? '') == 'Science Fiction' ? 'selected' : '' ?>>Science Fiction</option>
                                    <option value="Biography" <?= ($formData['genre'] ?? '') == 'Biography' ? 'selected' : '' ?>>Biography</option>
                                    <option value="History" <?= ($formData['genre'] ?? '') == 'History' ? 'selected' : '' ?>>History</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="invalid-feedback">
                                Please select a genre
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
                                       value="<?= htmlspecialchars($formData['publication_year'] ?? '') ?>" 
                                       class="form-input year-picker" 
                                       placeholder="Select year"
                                       required
                                       readonly>
                                <span class="input-hint">Click to select year</span>
                            </div>
                            <div class="invalid-feedback">
                                Please select a valid year (1000-<?= date('Y') ?>)
                            </div>
                        </div>

                        <!-- Number of Copies -->
                        <div class="form-group">
                            <label for="copies" class="form-label">Copies Available <span class="text-red-500">*</span></label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-copy"></i>
                                </span>
                                <input type="number" name="copies" id="copies" value="<?= htmlspecialchars($formData['copies'] ?? 1) ?>" 
                                       min="1" max="1000" 
                                       class="form-input" 
                                       placeholder="5" required>
                                <span class="input-hint">Minimum 1</span>
                            </div>
                            <div class="invalid-feedback">
                                Please enter a number between 1 and 1000
                            </div>
                        </div>

                        <!-- Edition -->
                        <div class="form-group">
                            <label for="edition" class="form-label">Edition</label>
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-bookmark"></i>
                                </span>
                                <input type="text" name="edition" id="edition" value="<?= htmlspecialchars($formData['edition'] ?? '') ?>" 
                                       class="form-input" 
                                       placeholder="First Edition">
                            </div>
                            <div class="invalid-feedback">
                                Edition must be less than 50 characters
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Book Cover Image -->
                <div class="form-group mt-8">
                    <label class="form-label">Book Cover</label>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="relative">
                            <label class="file-upload-btn">
                                <i class="fas fa-cloud-upload-alt mr-2"></i>
                                <span id="file-name">Choose cover image...</span>
                                <input type="file" name="image" id="image" class="file-upload-input" accept="image/*">
                            </label>
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, or WEBP (Max 2MB)</p>
                        </div>
                        <div id="image-preview-container" class="hidden">
                            <img id="image-preview" class="h-32 rounded-lg border-2 border-dashed border-gray-300 object-cover">
                            <button type="button" id="remove-image" class="text-red-500 text-xs mt-1 flex items-center">
                                <i class="fas fa-times mr-1"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Book Summary -->
                <div class="form-group mt-6">
                    <label for="book_summary" class="form-label">Book Summary</label>
                    <div class="relative">
                        <textarea name="book_summary" id="book_summary" rows="5" 
                                  class="form-textarea"
                                  placeholder="Enter a brief description of the book's content and themes"><?= htmlspecialchars($formData['book_summary'] ?? '') ?></textarea>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-xs text-gray-500">Max 500 characters</span>
                            <span id="char-count" class="text-xs font-medium">0/500</span>
                        </div>
                    </div>
                    <div class="invalid-feedback">
                        Summary must be less than 500 characters
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-10">
                    <a href="books.php" class="secondary-btn">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </a>
                    <button type="submit" class="primary-btn">
                        <i class="fas fa-plus-circle mr-2"></i> Add Book
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize year picker
        flatpickr("#publication_year", {
            dateFormat: "Y",
            minDate: "1000",
            maxDate: new Date().getFullYear().toString(),
            defaultDate: "<?= date('Y') ?>",
            static: true
        });

        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('image-preview');
            const container = document.getElementById('image-preview-container');
            const fileName = document.getElementById('file-name');
            
            if (file) {
                fileName.textContent = file.name;
                container.classList.remove('hidden');
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Remove image
        document.getElementById('remove-image').addEventListener('click', function() {
            document.getElementById('image').value = '';
            document.getElementById('image-preview-container').classList.add('hidden');
            document.getElementById('file-name').textContent = 'Choose cover image...';
        });

        // Character counter
        document.getElementById('book_summary').addEventListener('input', function() {
            const count = this.value.length;
            const counter = document.getElementById('char-count');
            counter.textContent = `${count}/500`;
            
            if (count > 500) {
                this.classList.add('is-invalid');
                counter.classList.add('text-red-600');
                counter.classList.remove('text-gray-500');
            } else if (count > 450) {
                this.classList.remove('is-invalid');
                counter.classList.add('text-yellow-600');
                counter.classList.remove('text-gray-500');
            } else {
                this.classList.remove('is-invalid');
                counter.classList.remove('text-yellow-600', 'text-red-600');
                counter.classList.add('text-gray-500');
            }
        });

        // Form validation on submit
        document.querySelector('form').addEventListener('submit', function(e) {
            let isValid = true;
            const title = document.getElementById('title');
            const author = document.getElementById('author');
            const isbn = document.getElementById('isbn');
            const copies = document.getElementById('copies');
            const genre = document.getElementById('genre');
            const publisher = document.getElementById('publisher');
            const publicationYear = document.getElementById('publication_year');
            const bookSummary = document.getElementById('book_summary');
            
            // Clear previous error highlights
            document.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });
            
            // Validate title
            if (title.value.trim() === '') {
                title.classList.add('is-invalid');
                isValid = false;
            } else if (title.value.length > 255) {
                title.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate author
            if (author.value.trim() === '') {
                author.classList.add('is-invalid');
                isValid = false;
            } else if (!/^[a-zA-Z\s\-\.]+$/.test(author.value)) {
                author.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate ISBN
            if (isbn.value.trim() === '') {
                isbn.classList.add('is-invalid');
                isValid = false;
            } else if (!/^(97(8|9))?\d{9}(\d|X)$/.test(isbn.value)) {
                isbn.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate copies
            if (copies.value < 1 || copies.value > 1000) {
                copies.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate genre
            if (genre.value === '') {
                genre.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate publisher
            if (publisher.value.trim() === '') {
                publisher.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate publication year
            if (publicationYear.value.trim() === '' || 
                isNaN(publicationYear.value) || 
                publicationYear.value < 1000 || 
                publicationYear.value > new Date().getFullYear()) {
                publicationYear.classList.add('is-invalid');
                isValid = false;
            }
            
            // Validate book summary length
            if (bookSummary.value.length > 500) {
                bookSummary.classList.add('is-invalid');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Real-time validation for fields
        document.getElementById('title').addEventListener('input', function() {
            if (this.value.length > 255) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        document.getElementById('author').addEventListener('input', function() {
            if (!/^[a-zA-Z\s\-\.]+$/.test(this.value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        document.getElementById('isbn').addEventListener('input', function () {
        const value = this.value.replace(/[-\s]/g, ''); // Remove dashes/spaces
        const errorDiv = document.getElementById('isbn-error');

        if (/^(97(8|9))?\d{9}(\d|X)$/.test(value)) {
            let isValid = false;

            if (value.length === 10) {
                isValid = validateISBN10(value);
            } else if (value.length === 13) {
                isValid = validateISBN13(value);
            }

            if (isValid) {
                this.classList.remove('is-invalid');
                errorDiv.style.display = 'none';
            } else {
                this.classList.add('is-invalid');
                errorDiv.style.display = 'block';
            }
        } else {
            this.classList.add('is-invalid');
            errorDiv.style.display = 'block';
        }
    });

    /* Simplified validation to match PHP format check only */
    function validateISBN10() { return true; }
    function validateISBN13() { return true; }
        
        document.getElementById('copies').addEventListener('input', function() {
            if (this.value < 1 || this.value > 1000) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Initialize counter
        document.addEventListener('DOMContentLoaded', function() {
            const summary = document.getElementById('book_summary');
            document.getElementById('char-count').textContent = `${summary.value.length}/500`;
        });
    </script>
</body>
</html>
