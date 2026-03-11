<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "nmims_db";

$conn = mysqli_connect($host, $user, $pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

define('GEMINI_API_KEY', 'AIzaSyDxDyGenDWQ4Pe_ExIOVkP__J9zz3-_hn4');  // ← paste new key here

session_start();
?>