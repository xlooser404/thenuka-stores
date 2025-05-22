<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Delete the remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    
    // Also clear from database if you want
    require_once '../config/database.php';
    $database = new Database();
    $pdo = $database->connect();
    
    $pdo->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE remember_token = ?")
        ->execute([$_COOKIE['remember_token']]);
}

header("Location: ../../pages/login.php");
exit();
?>