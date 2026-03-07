<?php
include 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$item_id = $_GET['item_id'] ?? 0;

if ($item_id > 0) {
    $user_id = $_SESSION['user_id'];
    
    // Get messages for this item
    $sql = "SELECT c.*, u.name as sender_name 
            FROM chat c 
            LEFT JOIN users u ON c.sender_id = u.id 
            WHERE c.item_id = '$item_id' 
            ORDER BY c.created_at ASC";
    
    $result = mysqli_query($conn, $sql);
    
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = [
            'id' => $row['id'],
            'item_id' => $row['item_id'],
            'sender_id' => $row['sender_id'],
            'sender_name' => $row['sender_name'],
            'message' => $row['message'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode($messages);
} else {
    echo json_encode([]);
}
?>