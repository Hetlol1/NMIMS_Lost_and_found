<?php
include 'config.php';

header('Content-Type: application/json');

// 1. Authorization Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Unauthorized'
    ]);
    exit;
}

// 2. Database Operation
$sql = "DELETE FROM items";

if (mysqli_query($conn, $sql)) {
    // 3. Success Response
    echo json_encode([
        'status' => 'success', 
        'message' => 'All items deleted successfully'
    ]);
} else {
    // 4. Error Response
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to delete items'
    ]);
}
?>