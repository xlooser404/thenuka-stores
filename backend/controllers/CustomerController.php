<?php
// Start output buffering immediately
ob_start();

// Log headers status and output buffer
error_log("CustomerController.php - Headers sent before start: " . (headers_sent() ? 'Yes' : 'No'));
error_log("CustomerController.php - Initial output buffer: " . ob_get_contents());

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // Prevent session ID regeneration
    session_regenerate_id(false);
}

// Log session start
error_log("CustomerController.php - Session started, ID: " . session_id());
error_log("CustomerController.php - Session data: " . print_r($_SESSION, true));

// Fallback CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("CustomerController.php - Generated fallback CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/Database.php';

// Log after include
error_log("CustomerController.php - After Database.php include, headers sent: " . (headers_sent() ? 'Yes' : 'No'));

// Redirect to login page if not logged in or not admin
if (!isset($_SESSION['user']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    error_log("CustomerController.php - Unauthorized access: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/customers.php'));
    exit;
}

class CustomerController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("CustomerController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/customers.php'));
            exit;
        }
    }

    public function getAllCustomers() {
        try {
            $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, phone, address FROM customers ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("CustomerController.php - Error fetching customers: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch customers.";
            return [];
        }
    }

    public function addCustomer($data) {
        // Debug CSRF token
        error_log("CustomerController.php - addCustomer: POST data: " . print_r($data, true));
        error_log("CustomerController.php - addCustomer: Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));
        error_log("CustomerController.php - addCustomer: Submitted CSRF token: " . ($data['csrf_token'] ?? 'Not set'));

        if (!isset($data['csrf_token'])) {
            $_SESSION['error'] = 'CSRF token missing in form submission.';
            error_log("CustomerController.php - CSRF validation failed: Token missing.");
            return false;
        }
        if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("CustomerController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $first_name = filter_var($data['first_name'], FILTER_SANITIZE_STRING);
        $last_name = filter_var($data['last_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
        $address = filter_var($data['address'], FILTER_SANITIZE_STRING);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO customers (first_name, last_name, email, phone, address)
                VALUES (:first_name, :last_name, :email, :phone, :address)
            ");
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address
            ]);
            $_SESSION['success'] = 'Customer added successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("CustomerController.php - Error adding customer: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to add customer: ' . $e->getMessage();
            return false;
        }
    }

    public function getCustomerById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, phone, address FROM customers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("CustomerController.php - Error fetching customer: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to fetch customer.';
            return false;
        }
    }

    public function updateCustomer($data) {
        // Debug CSRF token
        error_log("CustomerController.php - updateCustomer: POST data: " . print_r($data, true));
        error_log("CustomerController.php - updateCustomer: Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));
        error_log("CustomerController.php - updateCustomer: Submitted CSRF token: " . ($data['csrf_token'] ?? 'Not set'));

        if (!isset($data['csrf_token'])) {
            $_SESSION['error'] = 'CSRF token missing in form submission.';
            error_log("CustomerController.php - CSRF validation failed: Token missing.");
            return false;
        }
        if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("CustomerController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
        $first_name = filter_var($data['first_name'], FILTER_SANITIZE_STRING);
        $last_name = filter_var($data['last_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
        $address = filter_var($data['address'], FILTER_SANITIZE_STRING);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE customers
                SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, address = :address
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':phone' => $phone,
                ':address' => $address
            ]);
            $_SESSION['success'] = 'Customer updated successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("CustomerController.php - Error updating customer: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update customer: ' . $e->getMessage();
            return false;
        }
    }

    public function deleteCustomer($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM customers WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = 'Customer deleted successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("CustomerController.php - Error deleting customer: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete customer: ' . $e->getMessage();
            return false;
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    error_log("CustomerController.php - Handling action: " . $_GET['action']);
    $controller = new CustomerController();

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->addCustomer($_POST)) {
            header('Location: /thenuka-stores/pages/customers.php');
        } else {
            header('Location: /thenuka-stores/pages/customers.php');
        }
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->updateCustomer($_POST)) {
            header('Location: /thenuka-stores/pages/customers.php');
        } else {
            header('Location: /thenuka-stores/pages/customers.php');
        }
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        if ($controller->deleteCustomer($id)) {
            header('Location: /thenuka-stores/pages/customers.php');
        } else {
            header('Location: /thenuka-stores/pages/customers.php');
        }
        exit;
    }
}

// Clean output buffer
ob_end_clean();
?>