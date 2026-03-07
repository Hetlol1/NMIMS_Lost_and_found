<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['message' => 'Unauthorized']); exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
if ($user_id <= 0) { echo json_encode(['message' => 'Invalid user']); exit; }

mysqli_query($conn, "DELETE FROM users WHERE id='$user_id'");
echo json_encode(['message' => 'User deleted successfully']);
?>