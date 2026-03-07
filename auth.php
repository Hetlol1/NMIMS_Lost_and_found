<?php
include 'config.php';
header('Content-Type: text/plain');

$action = $_POST['action'] ?? '';

function send_response($message) {
    echo $message;
    exit;
}

// 1. REGISTRATION — always registers as 'user', never admin
if ($action === 'register') {
    $name  = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $pass  = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $role  = 'user'; // hardcoded — admins cannot self-register

    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        send_response("Email already exists");
    }
    $check_stmt->close();

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $pass, $role);

    if ($stmt->execute()) {
        send_response("Registration Successful");
    } else {
        send_response("Database Error: " . $stmt->error);
    }
    $stmt->close();
}

// 2. LOGIN — checks role and returns where to redirect
if ($action === 'login') {
    $email      = $_POST['email'] ?? '';
    $pass       = $_POST['password'] ?? '';
    $login_as   = $_POST['login_as'] ?? 'user'; // 'user' or 'admin' sent from frontend

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($pass, $row['password'])) {

            // Block if trying to login as admin but isn't one
            if ($login_as === 'admin' && $row['role'] !== 'admin') {
                send_response("Access denied. You are not an admin.");
            }

            // Block if trying to login as user but is an admin
            if ($login_as === 'user' && $row['role'] === 'admin') {
                send_response("Please use the Admin login.");
            }

            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name']    = $row['name'];
            $_SESSION['role']    = $row['role'];

            // Tell frontend where to go
            if ($row['role'] === 'admin') {
                send_response("redirect:admin_dashboard.php");
            } else {
                send_response("redirect:dashboard.php");
            }

        } else {
            send_response("Invalid Password");
        }
    } else {
        send_response("User not found");
    }
    $stmt->close();
}
?>