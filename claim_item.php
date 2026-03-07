<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$item_id = (int)($_POST['item_id'] ?? 0);

if (!$item_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid item ID.']);
    exit();
}

// Fetch item — support both user_id and owner_id column names
$res  = mysqli_query($conn, "SELECT * FROM items WHERE id='$item_id'");
$item = mysqli_fetch_assoc($res);

if (!$item) {
    echo json_encode(['status' => 'error', 'message' => 'Item not found.']);
    exit();
}

$owner_id  = (int)($item['user_id'] ?? $item['owner_id'] ?? 0);
$found_by  = (int)($item['found_by'] ?? 0);
$status    = $item['status'];

// ── CASE 1: Item is 'found' (uploaded as found, no owner claimed it yet)
//    Anyone except the uploader can press "Claim Item" to say "this is mine"
// ────────────────────────────────────────────────────────────────────────────
if ($status === 'found') {
    if ($user_id === $owner_id) {
        echo json_encode(['status' => 'error', 'message' => 'You uploaded this item — you cannot claim your own upload.']);
        exit();
    }
    // Mark as pending: the uploader (finder) will confirm
    $ok = mysqli_query($conn,
        "UPDATE items SET status='pending', found_by='$user_id' WHERE id='$item_id'"
    );
    if ($ok) {
        echo json_encode(['status' => 'success', 'message' => 'Claim submitted! Waiting for the finder to confirm.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit();
}

// ── CASE 2: Item is 'pending' (someone reported finding a lost item)
//    Only the OWNER can press "Confirm Claim" to close the loop
// ────────────────────────────────────────────────────────────────────────────
if ($status === 'pending') {
    if ($user_id !== $owner_id) {
        echo json_encode(['status' => 'error', 'message' => 'Only the item owner can confirm the claim.']);
        exit();
    }
    $ok = mysqli_query($conn,
        "UPDATE items SET status='claimed' WHERE id='$item_id'"
    );
    if ($ok) {
        echo json_encode(['status' => 'success', 'message' => 'Item marked as claimed. All done!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit();
}

// ── CASE 3: Already claimed or unexpected status ─────────────────────────────
echo json_encode(['status' => 'error', 'message' => 'This item cannot be claimed (status: ' . htmlspecialchars($status) . ').']);
?>