<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sql = "SELECT items.*, users.name as owner_name 
        FROM items 
        LEFT JOIN users ON items.owner_id = users.id 
        ORDER BY items.created_at DESC";

$result = mysqli_query($conn, $sql);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $row['id'] = (int)$row['id'];
    $row['owner_id'] = (int)$row['owner_id'];
    $items[] = $row;
}

echo json_encode($items);
?>