<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$report_id = intval($_GET['report_id'] ?? 0);
if (!$report_id) {
    echo json_encode(['error' => 'Invalid report ID']);
    exit;
}

// Get the found report
$stmt = mysqli_prepare($conn, "SELECT * FROM found_reports WHERE id = ? AND finder_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $report_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$report = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$report) {
    echo json_encode(['error' => 'Report not found']);
    exit;
}

// Get all LOST items that have images
$lostItems = [];
$res = mysqli_query($conn,
    "SELECT id, title, description, image_path FROM items WHERE status = 'lost' AND image_path IS NOT NULL AND image_path != ''");
while ($row = mysqli_fetch_assoc($res)) {
    $lostItems[] = $row;
}

if (empty($lostItems)) {
    echo json_encode(['matches' => [], 'message' => 'No lost items to compare against']);
    exit;
}

// ── Build Gemini prompt ──────────────────────────────────────────────────────
$foundImagePath = $report['image_path'];

// Try both with and without leading slash
$pathsToTry = [
    $foundImagePath,
    ltrim($foundImagePath, '/'),
    __DIR__ . '/' . ltrim($foundImagePath, '/'),
];

$resolvedPath = null;
foreach ($pathsToTry as $p) {
    if (file_exists($p)) {
        $resolvedPath = $p;
        break;
    }
}

if (!$resolvedPath) {
    echo json_encode([
        'error' => 'Found image file not found',
        'tried' => $pathsToTry
    ]);
    exit;
}

$foundImageData   = base64_encode(file_get_contents($resolvedPath));
$foundImageMime   = mime_content_type($resolvedPath);
$foundDescription = $report['description'];

// Build list of lost items for the prompt
$itemListText = "";
foreach ($lostItems as $idx => $item) {
    $itemListText .= ($idx + 1) . ". Item ID {$item['id']}: \"{$item['title']}\" — {$item['description']}\n";
}

$promptText = "You are an AI assistant helping match a found item to lost item reports.

FOUND ITEM:
- Description: \"{$foundDescription}\"
- Image: (provided below)

LOST ITEMS LIST:
{$itemListText}

Based on the found item's image and description, rank how likely each lost item matches.

IMPORTANT: Respond ONLY with a raw JSON array. No markdown. No code blocks. No explanation. Just the JSON array starting with [ and ending with ].

Example format:
[{\"item_id\": 5, \"confidence\": \"high\", \"reason\": \"Same brand and color\"},{\"item_id\": 3, \"confidence\": \"low\", \"reason\": \"Different category\"}]

confidence must be exactly one of: high, medium, low
Only include items with at least low confidence.";

// ── Call Gemini API ──────────────────────────────────────────────────────────
$apiKey = GEMINI_API_KEY;
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";
$payload = [
    'contents' => [[
        'parts' => [
            ['text' => $promptText],
            [
                'inline_data' => [
                    'mime_type' => $foundImageMime,
                    'data'      => $foundImageData
                ]
            ]
        ]
    ]],
    'generationConfig' => [
        'temperature'     => 0.1,
        'maxOutputTokens' => 1000
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST,          true);
curl_setopt($ch, CURLOPT_POSTFIELDS,    json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER,    ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT,       30);

$response  = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['error' => 'API request failed: ' . $curlError]);
    exit;
}

$geminiData = json_decode($response, true);

// Check for API-level errors (e.g. invalid key, quota exceeded)
if (isset($geminiData['error'])) {
    echo json_encode([
        'error' => 'Gemini API error: ' . ($geminiData['error']['message'] ?? 'Unknown error'),
        'code'  => $geminiData['error']['code'] ?? ''
    ]);
    exit;
}

// Extract text from Gemini response
$rawText = $geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (empty($rawText)) {
    echo json_encode([
        'error'         => 'Gemini returned empty response',
        'full_response' => $geminiData
    ]);
    exit;
}

// ── Robust JSON extraction ───────────────────────────────────────────────────
// 1. Strip markdown code fences
$cleaned = preg_replace('/```json\s*/i', '', $rawText);
$cleaned = preg_replace('/```\s*/i',     '', $cleaned);
$cleaned = trim($cleaned);

// 2. Extract just the JSON array using regex (handles extra text before/after)
if (preg_match('/(\[.*\])/s', $cleaned, $m)) {
    $cleaned = $m[1];
}

$matches = json_decode($cleaned, true);

if (!is_array($matches)) {
    echo json_encode([
        'error'   => 'Could not parse Gemini response as JSON',
        'raw'     => $rawText,
        'cleaned' => $cleaned
    ]);
    exit;
}

// ── Attach full item details ─────────────────────────────────────────────────
$itemMap  = array_column($lostItems, null, 'id');
$enriched = [];
foreach ($matches as $match) {
    $id   = $match['item_id'] ?? 0;
    $item = $itemMap[$id] ?? null;
    if ($item) {
        $enriched[] = [
            'item_id'     => $id,
            'title'       => $item['title'],
            'description' => $item['description'],
            'image_path'  => $item['image_path'],
            'confidence'  => $match['confidence'],
            'reason'      => $match['reason']
        ];
    }
}

// Sort: high → medium → low
$order = ['high' => 0, 'medium' => 1, 'low' => 2];
usort($enriched, fn($a, $b) => ($order[$a['confidence']] ?? 3) - ($order[$b['confidence']] ?? 3));

echo json_encode(['matches' => $enriched]);
?>