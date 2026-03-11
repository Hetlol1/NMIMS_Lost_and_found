<?php
include 'config.php';

$apiKey = GEMINI_API_KEY;
$url    = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "<pre>";
foreach ($data['models'] ?? [] as $model) {
    echo $model['name'] . "\n";
}
echo "</pre>";
?>
```

Then open:
```
http://localhost/nmims_lost_found/list_models.php