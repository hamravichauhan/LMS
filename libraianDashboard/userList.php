<?php
include "../db/config.php";

// Fetch all students
$student_sql = "SELECT id, name, email FROM users WHERE role='student'";
$student_result = $conn->query($student_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student List</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans">

    <div class="container mx-auto p-6">
        <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-2xl font-semibold text-gray-800 mb-4">📋 List of Students</h3>

            <div class="overflow-x-auto">
                <table class="w-full table-auto border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-blue-500 text-white">
                            <th class="px-4 py-2">Name</th>
                            <th class="px-4 py-2">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $student_result->fetch_assoc()): ?>
                            <tr class="border border-gray-300 hover:bg-gray-100 transition">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row["name"]); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row["email"]); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Back Button -->
            <div class="mt-6">
                <a href="index.php" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition">
                    ⬅ Back to Dashboard
                </a>
            </div>
        </div>
    </div>

</body>

</html>