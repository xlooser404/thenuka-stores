<?php
session_start();

// Check for remember me token if not logged in
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    require_once '../config/database.php';
    $database = new Database();
    $pdo = $database->connect();
    
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        
        // Refresh the token for security
        $newToken = bin2hex(random_bytes(32));
        $newExpiry = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
        
        $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
        $stmt->execute([$newToken, $newExpiry, $user['id']]);
        
        setcookie('remember_token', $newToken, time() + 30 * 24 * 60 * 60, '/', '', true, true);
    }
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
    <a href="../backend/auth/logout.php">Logout</a>
</body>
</html>