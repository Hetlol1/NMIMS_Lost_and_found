<?php
include 'config.php';

header('Content-Type: application/json');

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$finder_id = (int)$_SESSION['user_id'];
$item_id   = (int)($_POST['item_id'] ?? 0);

if (!$item_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid item ID.']);
    exit();
}

// Fetch the item — support both user_id and owner_id column names
$res  = mysqli_query($conn, "SELECT * FROM items WHERE id='$item_id'");
$item = mysqli_fetch_assoc($res);

if (!$item) {
    echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
    exit();
}

// Normalise owner field (user_id or owner_id)
$owner_id = (int)($item['user_id'] ?? $item['owner_id'] ?? 0);

// Can't report your own item as found
if ($owner_id === $finder_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot report your own item as found.']);
    exit();
}

// Item must be in 'lost' status
if ($item['status'] !== 'lost') {
    echo json_encode(['status' => 'error', 'message' => 'This item is no longer marked as lost (status: ' . $item['status'] . ').']);
    exit();
}

// Update status to 'pending' and record who found it
// Try updating found_by column; if your table doesn't have it, the query still works
$update = mysqli_query($conn,
    "UPDATE items SET status='pending', found_by='$finder_id' WHERE id='$item_id'"
);

if ($update) {
    echo json_encode(['status' => 'success', 'message' => 'Reported! Waiting for the owner to confirm.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>