<?php
include "../db/config.php";

if (isset($_GET["id"])) {
    $sql = "DELETE FROM books WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_GET["id"]);
    $stmt->execute();
}

header("Location: books.php");
