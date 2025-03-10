<?php
include "../db/config.php";

$sql = "SELECT r.id, u.name AS user_name, b.title AS book_title, r.reservation_date 
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        JOIN books b ON r.book_id = b.id
        WHERE r.status = 'pending'";

$result = $conn->query($sql);
?>

<h2>Book Reservations</h2>
<table border="1">
    <tr>
        <th>User</th>
        <th>Book</th>
        <th>Reservation Date</th>
        <th>Actions</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row["user_name"]) ?></td>
            <td><?= htmlspecialchars($row["book_title"]) ?></td>
            <td><?= $row["reservation_date"] ?></td>
            <td><a href="cancel_reservation.php?id=<?= $row["id"] ?>">Cancel</a></td>
        </tr>
    <?php endwhile; ?>
</table>