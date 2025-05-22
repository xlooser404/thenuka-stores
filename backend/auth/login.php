<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $pdo = $database->connect();

    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate inputs
    $errors = [];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    }

    // Check user credentials
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Start session and redirect
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            
            // Check if "Remember me" was checked
            if (isset($_POST['rememberMe']) && $_POST['rememberMe'] == 'on') {
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
                $stmt->execute([$token, $expiry, $user['id']]);
                
                // Set cookie
                setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/');
            }
            
            header("Location: ../../pages/home.php");
            exit();
        } else {
            $errors[] = "Invalid email or password";
        }
    }

    // Return to login page with errors
    session_start();
    $_SESSION['errors'] = $errors;
    header("Location: ../../pages/login.php");
    exit();
}
?>