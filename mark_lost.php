<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$uid     = (int)$_SESSION['user_id'];
$item_id = (int)($_POST['item_id'] ?? 0);

if (!$item_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid item ID']);
    exit;
}

// Verify ownership and current status
$stmt = mysqli_prepare($conn, "SELECT id, owner_id, status FROM items WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $item_id);
mysqli_stmt_execute($stmt);
$item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$item) {
    echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    exit;
}
if ((int)$item['owner_id'] !== $uid) {
    echo json_encode(['status' => 'error', 'message' => 'You do not own this item']);
    exit;
}
if ($item['status'] !== 'registered') {
    echo json_encode(['status' => 'error', 'message' => 'Item is not in registered status']);
    exit;
}

$upd = mysqli_prepare($conn, "UPDATE items SET status = 'lost', updated_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($upd, 'i', $item_id);

if (mysqli_stmt_execute($upd)) {
    echo json_encode(['status' => 'success', 'message' => 'Item marked as lost. It is now visible to others.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>