<?php
// Start output buffering immediately
ob_start();

// Log headers status and output buffer
error_log("StoreController.php - Headers sent before start: " . (headers_sent() ? 'Yes' : 'No'));
error_log("StoreController.php - Initial output buffer: " . ob_get_contents());

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // Prevent session ID regeneration
    session_regenerate_id(false);
}

// Log session start
error_log("StoreController.php - Session started, ID: " . session_id());
error_log("StoreController.php - Session data: " . print_r($_SESSION, true));

// Fallback CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("StoreController.php - Generated fallback CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/Database.php';

// Log after include
error_log("StoreController.php - After Database.php include, headers sent: " . (headers_sent() ? 'Yes' : 'No'));

// Redirect to login page if not logged in or not admin
if (!isset($_SESSION['user']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    error_log("StoreController.php - Unauthorized access: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/store.php'));
    exit;
}

class StoreController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("StoreController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/store.php'));
            exit;
        }
    }

    public function getAllStores() {
        try {
            $stmt = $this->db->prepare("SELECT id, store_name, location, manager_name, contact_email, status, last_audit_date FROM stores ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("StoreController.php - Error fetching stores: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch stores.";
            return [];
        }
    }

    public function addStore($data) {
        error_log("StoreController.php - addStore: POST data: " . print_r($data, true));
        error_log("StoreController.php - addStore: Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));
        error_log("StoreController.php - addStore: Submitted CSRF token: " . ($data['csrf_token'] ?? 'Not set'));

        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            error_log("StoreController.php - CSRF validation failed.");
            return ['success' => false, 'error' => 'Invalid CSRF token.'];
        }

        $store_name = filter_var($data['store_name'], FILTER_SANITIZE_STRING);
        $location = filter_var($data['location'], FILTER_SANITIZE_STRING);
        $manager_name = filter_var($data['manager_name'], FILTER_SANITIZE_STRING);
        $contact_email = filter_var($data['contact_email'], FILTER_SANITIZE_EMAIL);
        $status = filter_var($data['status'], FILTER_SANITIZE_STRING);
        $last_audit_date = !empty($data['last_audit_date']) ? $data['last_audit_date'] : null;

        if (empty($store_name) || empty($location) || empty($manager_name) || empty($contact_email)) {
            return ['success' => false, 'error' => 'All required fields (store name, location, manager name, contact email) must be filled.'];
        }

        if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format.'];
        }

        if (!in_array($status, ['Active', 'Inactive', 'Under Renovation'])) {
            return ['success' => false, 'error' => 'Invalid status value.'];
        }

        if ($last_audit_date && !DateTime::createFromFormat('Y-m-d', $last_audit_date)) {
            return ['success' => false, 'error' => 'Invalid date format for last audit date.'];
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO stores (store_name, location, manager_name, contact_email, status, last_audit_date)
                VALUES (:store_name, :location, :manager_name, :contact_email, :status, :last_audit_date)
            ");
            $stmt->execute([
                ':store_name' => $store_name,
                ':location' => $location,
                ':manager_name' => $manager_name,
                ':contact_email' => $contact_email,
                ':status' => $status,
                ':last_audit_date' => $last_audit_date
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("StoreController.php - Error adding store: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to add store: ' . $e->getMessage()];
        }
    }

    public function getStoreById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, store_name, location, manager_name, contact_email, status, last_audit_date FROM stores WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("StoreController.php - Error fetching store: " . $e->getMessage());
            return false;
        }
    }

    public function updateStore($data) {
        error_log("StoreController.php - updateStore: POST data: " . print_r($data, true));
        error_log("StoreController.php - updateStore: Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));
        error_log("StoreController.php - updateStore: Submitted CSRF token: " . ($data['csrf_token'] ?? 'Not set'));

        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            error_log("StoreController.php - CSRF validation failed.");
            return ['success' => false, 'error' => 'Invalid CSRF token.'];
        }

        $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
        $store_name = filter_var($data['store_name'], FILTER_SANITIZE_STRING);
        $location = filter_var($data['location'], FILTER_SANITIZE_STRING);
        $manager_name = filter_var($data['manager_name'], FILTER_SANITIZE_STRING);
        $contact_email = filter_var($data['contact_email'], FILTER_SANITIZE_EMAIL);
        $status = filter_var($data['status'], FILTER_SANITIZE_STRING);
        $last_audit_date = !empty($data['last_audit_date']) ? $data['last_audit_date'] : null;

        if (empty($store_name) || empty($location) || empty($manager_name) || empty($contact_email)) {
            return ['success' => false, 'error' => 'All required fields (store name, location, manager name, contact email) must be filled.'];
        }

        if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format.'];
        }

        if (!in_array($status, ['Active', 'Inactive', 'Under Renovation'])) {
            return ['success' => false, 'error' => 'Invalid status value.'];
        }

        if ($last_audit_date && !DateTime::createFromFormat('Y-m-d', $last_audit_date)) {
            return ['success' => false, 'error' => 'Invalid date format for last audit date.'];
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE stores
                SET store_name = :store_name, location = :location, manager_name = :manager_name, 
                    contact_email = :contact_email, status = :status, last_audit_date = :last_audit_date
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':store_name' => $store_name,
                ':location' => $location,
                ':manager_name' => $manager_name,
                ':contact_email' => $contact_email,
                ':status' => $status,
                ':last_audit_date' => $last_audit_date
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("StoreController.php - Error updating store: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to update store: ' . $e->getMessage()];
        }
    }

    public function deleteStore($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM stores WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("StoreController.php - Error deleting store: " . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to delete store: ' . $e->getMessage()];
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    error_log("StoreController.php - Handling action: " . $_GET['action']);
    $controller = new StoreController();

    header('Content-Type: application/json'); // Set JSON content type for all responses

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = $controller->addStore($_POST);
        echo json_encode($response);
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $response = $controller->updateStore($_POST);
        echo json_encode($response);
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $response = $controller->deleteStore($id);
        echo json_encode($response);
        exit;
    }

    if ($_GET['action'] === 'get' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $store = $controller->getStoreById($id);
        if ($store) {
            echo json_encode($store);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Store not found']);
        }
        exit;
    }

    // If action is invalid
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

// Clean output buffer
ob_end_clean();
?>