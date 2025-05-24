<?php
ob_start();

session_start();
error_log("UserController.php - Session started, ID: " . session_id());
error_log("UserController.php - Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("UserController.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    error_log("UserController.php - Unauthorized access: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/users.php'));
    exit;
}

class UserController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("UserController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/users.php'));
            exit;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("UserController.php - Error fetching users: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch users.";
            return [];
        }
    }

    public function addUser($data) {
        error_log("UserController.php - Received POST data: " . print_r($data, true));
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch. Session token: ' . $_SESSION['csrf_token'] . ', Received token: ' . ($data['csrf_token'] ?? 'Not set');
            error_log("UserController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $username = filter_var($data['username'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $password = $data['password'];
        $role = filter_var($data['role'], FILTER_SANITIZE_STRING);

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format.';
            return false;
        }

        // Validate password length (minimum 8 characters)
        if (strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters long.';
            return false;
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Validate role
        if (!in_array($role, ['admin', 'agent', 'user'])) {
            $_SESSION['error'] = 'Invalid role value.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, role)
                VALUES (:username, :email, :password, :role)
            ");
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $hashedPassword,
                ':role' => $role
            ]);
            $_SESSION['success'] = 'User added successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("UserController.php - Error adding user: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to add user: ' . $e->getMessage();
            return false;
        }
    }

    public function getUserById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("UserController.php - Error fetching user: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to fetch user.';
            return false;
        }
    }

    public function updateUser($data) {
        error_log("UserController.php - Received POST data: " . print_r($data, true));
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch. Session token: ' . $_SESSION['csrf_token'] . ', Received token: ' . ($data['csrf_token'] ?? 'Not set');
            error_log("UserController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
        $username = filter_var($data['username'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $password = $data['password'];
        $role = filter_var($data['role'], FILTER_SANITIZE_STRING);

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format.';
            return false;
        }

        // Validate role
        if (!in_array($role, ['admin', 'agent', 'user'])) {
            $_SESSION['error'] = 'Invalid role value.';
            return false;
        }

        // Prepare the update query
        $query = "
            UPDATE users
            SET username = :username, email = :email, role = :role
            " . (!empty($password) ? ", password = :password" : "") . "
            WHERE id = :id
        ";

        try {
            $stmt = $this->db->prepare($query);
            $params = [
                ':id' => $id,
                ':username' => $username,
                ':email' => $email,
                ':role' => $role
            ];

            // If password is provided, hash and include it in the update
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    $_SESSION['error'] = 'Password must be at least 8 characters long.';
                    return false;
                }
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $params[':password'] = $hashedPassword;
            }

            $stmt->execute($params);
            $_SESSION['success'] = 'User updated successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("UserController.php - Error updating user: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update user: ' . $e->getMessage();
            return false;
        }
    }

    public function deleteUser($id) {
        // Prevent deleting the currently logged-in user
        if ($id == $_SESSION['user']['id']) {
            $_SESSION['error'] = 'You cannot delete your own account.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = 'User deleted successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("UserController.php - Error deleting user: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete user: ' . $e->getMessage();
            return false;
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    error_log("UserController.php - Handling action: " . $_GET['action']);
    $controller = new UserController();

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->addUser($_POST)) {
            header('Location: /thenuka-stores/pages/users.php');
        } else {
            header('Location: /thenuka-stores/pages/users.php');
        }
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->updateUser($_POST)) {
            header('Location: /thenuka-stores/pages/users.php');
        } else {
            header('Location: /thenuka-stores/pages/users.php');
        }
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        if ($controller->deleteUser($id)) {
            header('Location: /thenuka-stores/pages/users.php');
        } else {
            header('Location: /thenuka-stores/pages/users.php');
        }
        exit;
    }
}

ob_end_clean();
?>