<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/Database.php';

// CSRF check
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid CSRF token';
    header('Location: ../../pages/register.php');
    exit;
}

// Sanitize and validate input
$name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$terms = isset($_POST['flexCheckDefault']);

// Validation checks
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    $_SESSION['error'] = 'All fields are required';
    header('Location: ../../pages/register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Invalid email format';
    header('Location: ../../pages/register.php');
    exit;
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = 'Passwords do not match';
    header('Location: ../../pages/register.php');
    exit;
}

if (strlen($password) < 8) {
    $_SESSION['error'] = 'Password must be at least 8 characters long';
    header('Location: ../../pages/register.php');
    exit;
}

if (!$terms) {
    $_SESSION['error'] = 'You must agree to the Terms and Conditions';
    header('Location: ../../pages/register.php');
    exit;
}

// Connect to DB
$db = new Database();
$conn = $db->connect();
if (!$conn) {
    error_log("Register.php - Database connection failed.");
    $_SESSION['error'] = 'Database connection failed';
    header('Location: ../../pages/register.php');
    exit;
}

// Check if email already exists
try {
    $query = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Email already registered';
        header('Location: ../../pages/register.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Register.php - Database error during email check: " . $e->getMessage());
    $_SESSION['error'] = 'Registration failed due to a database error';
    header('Location: ../../pages/register.php');
    exit;
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
try {
    $query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'user')";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->execute();

    // Log success and redirect
    error_log("Register.php - User registered successfully: $email");
    $_SESSION['success'] = 'Registration successful! Please log in.';
    header('Location: ../../pages/login.php');
    exit;
} catch (PDOException $e) {
    error_log("Register.php - Database error during user insertion: " . $e->getMessage());
    $_SESSION['error'] = 'Registration failed due to a database error';
    header('Location: ../../pages/register.php');
    exit;
}
?>