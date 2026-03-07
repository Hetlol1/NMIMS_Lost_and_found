<?php
include 'config.php';
header('Content-Type: application/json');

// Only admins can fetch the user list
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]);
    exit;
}

$sql = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

echo json_encode($users);
?>