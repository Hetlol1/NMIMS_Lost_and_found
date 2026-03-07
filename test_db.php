<?php
include 'config.php';

echo "<h2>Database Connection Test</h2>";

if ($conn) {
    echo "✅ Database Connected Successfully!<br>";
    echo "Host: localhost<br>";
    echo "User: root<br><br>";
    
    // Check if users table exists
    $result = mysqli_query($conn, "SHOW TABLES");
    echo "<h3>Tables in database:</h3>";
    echo "<ul>";
    if ($result) {
        while($row = mysqli_fetch_row($result)) {
            echo "<li>• " . $row[0] . "</li>";
        }
    } else {
        echo "<li>No tables found</li>";
    }
    echo "</ul>";
    
    // Check users table structure
    echo "<h3>Users Table Structure:</h3>";
    $result = mysqli_query($conn, "DESCRIBE users");
    if ($result) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while($row = mysqli_fetch_assoc($result)) {
            echo "<tr><td>" . $row['Field'] . "</td><td>" . $row['Type'] . "</td><td>" . $row['Null'] . "</td><td>" . $row['Key'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "❌ Users table not found!";
    }
} else {
    echo "❌ Database Connection Failed!<br>";
    echo "Error: " . mysqli_connect_error();
}
?>