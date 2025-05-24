<?php
ob_start();

session_start();
error_log("OrderController.php - Session started, ID: " . session_id());
error_log("OrderController.php - Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("OrderController.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    error_log("OrderController.php - Unauthorized access: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/orders.php'));
    exit;
}

class OrderController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("OrderController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/orders.php'));
            exit;
        }
    }

    public function getAllOrders() {
        try {
            $stmt = $this->db->prepare("SELECT id, order_number, customer_name, total_amount, order_date, status FROM orders ORDER BY order_date DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderController.php - Error fetching orders: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch orders.";
            return [];
        }
    }

    public function addOrder($data) {
        error_log("OrderController.php - Received POST data: " . print_r($data, true));
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch. Session token: ' . $_SESSION['csrf_token'] . ', Received token: ' . ($data['csrf_token'] ?? 'Not set');
            error_log("OrderController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $order_number = filter_var($data['order_number'], FILTER_SANITIZE_STRING);
        $customer_name = filter_var($data['customer_name'], FILTER_SANITIZE_STRING);
        $total_amount = filter_var($data['total_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $order_date = filter_var($data['order_date'], FILTER_SANITIZE_STRING);
        $status = filter_var($data['status'], FILTER_SANITIZE_STRING);

        if ($total_amount < 0 || !is_numeric($total_amount)) {
            $_SESSION['error'] = 'Invalid total amount value.';
            return false;
        }

        if (!in_array($status, ['Pending', 'Shipped', 'Delivered', 'Cancelled'])) {
            $_SESSION['error'] = 'Invalid status value.';
            return false;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $order_date) || !strtotime($order_date)) {
            $_SESSION['error'] = 'Invalid order date format.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO orders (order_number, customer_name, total_amount, order_date, status)
                VALUES (:order_number, :customer_name, :total_amount, :order_date, :status)
            ");
            $stmt->execute([
                ':order_number' => $order_number,
                ':customer_name' => $customer_name,
                ':total_amount' => $total_amount,
                ':order_date' => $order_date,
                ':status' => $status
            ]);
            $_SESSION['success'] = 'Order added successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("OrderController.php - Error adding order: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to add order: ' . $e->getMessage();
            return false;
        }
    }

    public function getOrderById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, order_number, customer_name, total_amount, order_date, status FROM orders WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("OrderController.php - Error fetching order: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to fetch order.';
            return false;
        }
    }

    public function updateOrder($data) {
        error_log("OrderController.php - Received POST data: " . print_r($data, true));
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch. Session token: ' . $_SESSION['csrf_token'] . ', Received token: ' . ($data['csrf_token'] ?? 'Not set');
            error_log("OrderController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
        $order_number = filter_var($data['order_number'], FILTER_SANITIZE_STRING);
        $customer_name = filter_var($data['customer_name'], FILTER_SANITIZE_STRING);
        $total_amount = filter_var($data['total_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $order_date = filter_var($data['order_date'], FILTER_SANITIZE_STRING);
        $status = filter_var($data['status'], FILTER_SANITIZE_STRING);

        if ($total_amount < 0 || !is_numeric($total_amount)) {
            $_SESSION['error'] = 'Invalid total amount value.';
            return false;
        }

        if (!in_array($status, ['Pending', 'Shipped', 'Delivered', 'Cancelled'])) {
            $_SESSION['error'] = 'Invalid status value.';
            return false;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $order_date) || !strtotime($order_date)) {
            $_SESSION['error'] = 'Invalid order date format.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE orders
                SET order_number = :order_number, customer_name = :customer_name, total_amount = :total_amount, 
                    order_date = :order_date, status = :status
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':order_number' => $order_number,
                ':customer_name' => $customer_name,
                ':total_amount' => $total_amount,
                ':order_date' => $order_date,
                ':status' => $status
            ]);
            $_SESSION['success'] = 'Order updated successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("OrderController.php - Error updating order: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update order: ' . $e->getMessage();
            return false;
        }
    }

    public function deleteOrder($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM orders WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = 'Order deleted successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("OrderController.php - Error deleting order: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete order: ' . $e->getMessage();
            return false;
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    error_log("OrderController.php - Handling action: " . $_GET['action']);
    $controller = new OrderController();

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->addOrder($_POST)) {
            header('Location: /thenuka-stores/pages/orders.php');
        } else {
            header('Location: /thenuka-stores/pages/orders.php');
        }
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->updateOrder($_POST)) {
            header('Location: /thenuka-stores/pages/orders.php');
        } else {
            header('Location: /thenuka-stores/pages/orders.php');
        }
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        if ($controller->deleteOrder($id)) {
            header('Location: /thenuka-stores/pages/orders.php');
        } else {
            header('Location: /thenuka-stores/pages/orders.php');
        }
        exit;
    }
}

ob_end_clean();
?>