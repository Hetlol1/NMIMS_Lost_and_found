<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$uid = (int)$_SESSION['user_id'];

// The 'view' parameter controls what is returned:
//   'all'   → only LOST, PENDING, CLAIMED items (public feed) — default
//   'mine'  → all items owned by the current user (any status)
$view = $_GET['view'] ?? 'all';

if ($view === 'mine') {
    // My Items: all statuses belonging to this user
    $sql = "SELECT i.*, u.name AS owner_name
            FROM items i
            LEFT JOIN users u ON i.owner_id = u.id
            WHERE i.owner_id = ?
            ORDER BY i.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $uid);
} else {
    // All Items public feed: only lost/pending/claimed (NOT registered)
    $sql = "SELECT i.*, u.name AS owner_name
            FROM items i
            LEFT JOIN users u ON i.owner_id = u.id
            WHERE i.status IN ('lost','pending','claimed')
            ORDER BY i.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = $row;
}

echo json_encode($items);
?>