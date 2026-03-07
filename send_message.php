<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$item_id = $_POST['item_id'] ?? 0;
$message = $_POST['message'] ?? '';
$sender_id = $_SESSION['user_id'];

if ($item_id > 0 && !empty($message)) {
    $sql = "INSERT INTO chat (item_id, sender_id, message) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iis", $item_id, $sender_id, $message);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
}
?>