<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/Database.php';

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid CSRF token';
    header('Location: ../../pages/login.php');
    exit;
}

// Sanitize input
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email and password are required';
    header('Location: ../../pages/login.php');
    exit;
}

// Connect to DB
$db = new Database();
$conn = $db->connect();
if (!$conn) {
    error_log("AuthController.php - Database connection failed.");
    $_SESSION['error'] = 'Database connection failed';
    header('Location: ../../pages/login.php');
    exit;
}

// Prepare and fetch user
try {
    $query = "SELECT id, username, email, password, role FROM users WHERE email = :email LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify hashed password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            error_log("AuthController.php - Login successful: " . print_r($_SESSION['user'], true));

            if ($user['role'] === 'admin') {
                header("Location: ../../pages/admin_dashboard.php?success=login");
            } elseif ($user['role'] === 'agent') {
                header("Location: ../../pages/agent_dashboard.php?success=login");
            } else {
                $_SESSION['error'] = 'Unknown role';
                header("Location: ../../pages/login.php?error=unknown_role");
            }
            exit;
        } else {
            error_log("AuthController.php - Invalid password for email: $email");
            $_SESSION['error'] = 'Invalid password';
            header("Location: ../../pages/login.php");
            exit;
        }
    } else {
        error_log("AuthController.php - User not found for email: $email");
        $_SESSION['error'] = 'User not found';
        header("Location: ../../pages/login.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("AuthController.php - Database error: " . $e->getMessage());
    $_SESSION['error'] = 'Login failed due to a database error';
    header('Location: ../../pages/login.php');
    exit;
}
?>