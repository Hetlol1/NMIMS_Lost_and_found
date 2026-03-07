<?php
include 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$item_id = $_POST['item_id'] ?? 0;

if ($item_id > 0) {
    // Get item details to check ownership
    $check = mysqli_query($conn, "SELECT * FROM items WHERE id='$item_id'");
    
    if (mysqli_num_rows($check) > 0) {
        $item = mysqli_fetch_assoc($check);
        
        // Check if current user is the owner
        if ($item['owner_id'] == $_SESSION['user_id']) {
            // Delete chat messages first (cascade)
            mysqli_query($conn, "DELETE FROM chat WHERE item_id = '$item_id'");
            
            // Delete the item
            if (mysqli_query($conn, "DELETE FROM items WHERE id = '$item_id'")) {
                echo json_encode(['status' => 'success', 'message' => 'Item deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete item']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'You are not the owner of this item']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid item ID']);
}
?>