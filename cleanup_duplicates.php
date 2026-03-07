<?php
include 'config.php';
header('Content-Type: application/json');

// --- 1. SESSION & AUTHORIZATION ---
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status'  => 'error', 
        'message' => 'Unauthorized'
    ]);
    exit;
}

// --- 2. DATA INITIALIZATION ---
$duplicates    = [];
$deleted_count = 0;

// --- 3. IDENTIFY DUPLICATES ---
$sql = "SELECT title, GROUP_CONCAT(id) as ids, COUNT(*) as count 
        FROM items 
        GROUP BY title 
        HAVING COUNT(*) > 1";

$result = mysqli_query($conn, $sql);

// --- 4. PROCESSING LOGIC ---
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ids = explode(',', $row['ids']);
        
        // Keep the first item (lowest ID index 0), delete the rest (index 1+)
        for ($i = 1; $i < count($ids); $i++) {
            $delete_id = $ids[$i];
            
            // Delete dependent records (Chat Messages)
            mysqli_query($conn, "DELETE FROM chat WHERE item_id = '$delete_id'");
            
            // Delete the parent record (Item)
            $delete_query = "DELETE FROM items WHERE id = '$delete_id'";
            if (mysqli_query($conn, $delete_query)) {
                $deleted_count++;
                $duplicates[] = [
                    'title'      => $row['title'],
                    'deleted_id' => $delete_id,
                    'kept_id'    => $ids[0]
                ];
            }
        }
    }
}

// --- 5. FINAL RESPONSE ---
echo json_encode([
    'status'        => 'success',
    'message'       => "Processed " . count($duplicates) . " groups, deleted $deleted_count items total",
    'duplicates'    => $duplicates,
    'deleted_count' => $deleted_count
]);
?>