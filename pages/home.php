<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Check for remember me cookie if session is expired
    if (isset($_COOKIE['remember_token'])) {
        require_once '../backend/config/database.php';
        $database = new Database();
        $pdo = $database->connect();
        
        $token = $_COOKIE['remember_token'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND token_expiry > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
        } else {
            header("Location: frontend/pages/login.php");
            exit();
        }
    } else {
        header("Location: frontend/pages/login.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>home</title>
    <!-- Include your CSS files here -->
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
    <p>Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
    <a href="backend/auth/logout.php">Logout</a>
</body>
</html>