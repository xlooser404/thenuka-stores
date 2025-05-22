<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('../config/Database.php');

// CSRF check (optional for dev)
// if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
//     die('Invalid CSRF token');
// }

// Sanitize input
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Connect to DB
$db = new Database();
$conn = $db->connect();

// Prepare and fetch user
$query = "SELECT * FROM users WHERE email = :email LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // Non-hashed password comparison (dev only)
    if ($user['password'] === $password) {
        $_SESSION['user'] = $user;

        if ($user['role'] === 'admin') {
            header("Location: ../../pages/dashboard.php?success=login");
        } elseif ($user['role'] === 'agent') {
            header("Location: ../../pages/agent_dashboard.php?success=login");
        } else {
            header("Location: ../../pages/login.php?error=unknown_role");
        }
        exit();
    } else {
        $_SESSION['error'] = "Invalid password";
        header("Location: ../pages/login.php");
        exit();
    }
} else {
    $_SESSION['error'] = "User not found";
    header("Location: ../pages/login.php");
    exit();
}
?>
