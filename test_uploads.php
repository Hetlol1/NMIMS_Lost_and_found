<?php
include 'config.php';

echo "<h2>Upload Test</h2>";

// Check uploads folder
if (file_exists('uploads/')) {
    echo "✅ uploads/ folder exists<br>";
    echo "Folder path: " . realpath('uploads/') . "<br>";
    
    // List files
    $files = scandir('uploads/');
    echo "<br>Files in uploads/:<br>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "❌ uploads/ folder does not exist!";
}

// Check permissions
if (is_writable('uploads/')) {
    echo "<br>✅ uploads/ folder is writable";
} else {
    echo "<br>❌ uploads/ folder is NOT writable";
}
?>