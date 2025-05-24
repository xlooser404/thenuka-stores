<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(false);
}

error_log("InventoryController.php - Session started, ID: " . session_id());
error_log("InventoryController.php - Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("InventoryController.php - Generated fallback CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    error_log("InventoryController.php - Unauthorized access: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/inventory.php'));
    exit;
}

class InventoryController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("InventoryController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/inventory.php'));
            exit;
        }
    }

    public function getAllInventory() {
        try {
            $stmt = $this->db->prepare("SELECT id, product_name, quantity, location, last_updated FROM inventory ORDER BY last_updated DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("InventoryController.php - Error fetching inventory: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch inventory.";
            return [];
        }
    }

    public function addInventory($data) {
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("InventoryController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $product_name = filter_var($data['product_name'], FILTER_SANITIZE_STRING);
        $quantity = filter_var($data['quantity'], FILTER_SANITIZE_NUMBER_INT);
        $location = filter_var($data['location'], FILTER_SANITIZE_STRING);

        if ($quantity < 0 || !is_numeric($quantity)) {
            $_SESSION['error'] = 'Invalid quantity value.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO inventory (product_name, quantity, location, last_updated)
                VALUES (:product_name, :quantity, :location, NOW())
            ");
            $stmt->execute([
                ':product_name' => $product_name,
                ':quantity' => $quantity,
                ':location' => $location
            ]);
            $_SESSION['success'] = 'Inventory item added successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("InventoryController.php - Error adding inventory: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to add inventory item: ' . $e->getMessage();
            return false;
        }
    }

    public function getInventoryById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, product_name, quantity, location, last_updated FROM inventory WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("InventoryController.php - Error fetching inventory item: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to fetch inventory item.';
            return false;
        }
    }

    public function updateInventory($data) {
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("InventoryController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
        $product_name = filter_var($data['product_name'], FILTER_SANITIZE_STRING);
        $quantity = filter_var($data['quantity'], FILTER_SANITIZE_NUMBER_INT);
        $location = filter_var($data['location'], FILTER_SANITIZE_STRING);

        if ($quantity < 0 || !is_numeric($quantity)) {
            $_SESSION['error'] = 'Invalid quantity value.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE inventory
                SET product_name = :product_name, quantity = :quantity, location = :location, last_updated = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':product_name' => $product_name,
                ':quantity' => $quantity,
                ':location' => $location
            ]);
            $_SESSION['success'] = 'Inventory item updated successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("InventoryController.php - Error updating inventory: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update inventory item: ' . $e->getMessage();
            return false;
        }
    }

    public function deleteInventory($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM inventory WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = 'Inventory item deleted successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("InventoryController.php - Error deleting inventory: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete inventory item: ' . $e->getMessage();
            return false;
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    error_log("InventoryController.php - Handling action: " . $_GET['action']);
    $controller = new InventoryController();

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->addInventory($_POST)) {
            header('Location: /thenuka-stores/pages/inventory.php');
        } else {
            header('Location: /thenuka-stores/pages/inventory.php');
        }
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->updateInventory($_POST)) {
            header('Location: /thenuka-stores/pages/inventory.php');
        } else {
            header('Location: /thenuka-stores/pages/inventory.php');
        }
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        if ($controller->deleteInventory($id)) {
            header('Location: /thenuka-stores/pages/inventory.php');
        } else {
            header('Location: /thenuka-stores/pages/inventory.php');
        }
        exit;
    }
}

ob_end_clean();
?>