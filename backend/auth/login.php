<?php
session_start();
require_once '../../backend/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $pdo = $database->connect();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['rememberMe']);

    // Validate inputs
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Email and password are required";
        header("Location: ../../frontend/pages/login.php");
        exit();
    }

    // Check user credentials
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        
        // Handle "Remember Me" functionality
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30 days
            
            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);
            
            // Set cookie (valid for 30 days)
            setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', true, true);
        }
        
        header("Location: ../../dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid email or password";
        header("Location: ../../frontend/pages/login.php");
        exit();
    }
}
?>