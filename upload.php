<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $status = ($type == 'found') ? 'found' : 'lost';
    $owner_id = $_SESSION['user_id'];

    // Create uploads folder if it doesn't exist
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Get file info
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Validate file type
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($imageFileType, $allowed)) {
        // Check file size (max 5MB)
        if ($_FILES["fileToUpload"]["size"] <= 5000000) {
            // Move uploaded file
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                // Store path starting with /uploads/
                $image_path = '/uploads/' . basename($_FILES["fileToUpload"]["name"]);
                
                $sql = "INSERT INTO items (owner_id, title, description, image_path, status) 
                        VALUES ('$owner_id', '$title', '$desc', '$image_path', '$status')";
                
                if (mysqli_query($conn, $sql)) {
                    header("Location: dashboard.php");
                    exit();
                } else {
                    echo "Error: " . mysqli_error($conn);
                }
            } else {
                echo "Error uploading file. Please try again.";
            }
        } else {
            echo "Error: File size exceeds 5MB limit.";
        }
    } else {
        echo "Error: Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Item - NMIMS</title>
    <style>
        body { 
            font-family: sans-serif; 
            background: #f0f2f5; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0;
        }
        .form-container { 
            background: white; 
            padding: 30px; 
            border-radius: 10px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
            width: 400px; 
        }
        input, textarea, select { 
            width: 100%; 
            padding: 12px; 
            margin: 10px 0; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            box-sizing: border-box;
        }
        button { 
            width: 100%; 
            padding: 12px; 
            background: #003366; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 1rem; 
        }
        button:hover { 
            background: #004080; 
        }
        a { 
            color: #003366; 
            text-decoration: none; 
            display: block; 
            margin-top: 15px; 
            text-align: center; 
        }
        h2 { 
            text-align: center; 
            color: #003366; 
            margin-bottom: 20px;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            background: #f8d7da;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .success {
            color: #155724;
            padding: 10px;
            background: #d4edda;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Upload Item</h2>
        
        <?php
        // Show error message if exists
        if (isset($_GET['error'])) {
            echo '<div class="error">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Item Title" required>
            <textarea name="description" placeholder="Description" rows="3" required></textarea>
            <select name="type" required>
                <option value="lost">I am an Owner</option>
                <option value="found">I am a Finder</option>
            </select>
            <input type="file" name="fileToUpload" accept="image/*" required>
            <small style="color: #666; display: block; margin: 5px 0;">
                Allowed: JPG, JPEG, PNG, GIF (Max 5MB)
            </small>
            <button type="submit">Upload</button>
        </form>
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>
</body>
</html>