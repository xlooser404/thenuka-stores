<?php
ob_start();

// Start session at the very beginning
session_start();
error_log("InvoiceController.php - Session started, ID: " . session_id());
error_log("InvoiceController.php - Session data: " . print_r($_SESSION, true));

// Generate CSRF token only if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("InvoiceController.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    error_log("InvoiceController.php - Unauthorized access: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/invoice.php'));
    exit;
}

class InvoiceController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("InvoiceController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/invoice.php'));
            exit;
        }
    }

    public function getAllInvoices() {
        try {
            $stmt = $this->db->prepare("SELECT id, invoice_number, customer_name, total_amount, issue_date, status FROM invoices ORDER BY issue_date DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("InvoiceController.php - Error fetching invoices: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch invoices.";
            return [];
        }
    }

    public function addInvoice($data) {
        error_log("InvoiceController.php - Received CSRF token from POST: " . ($data['csrf_token'] ?? 'Not set'));
        error_log("InvoiceController.php - Session CSRF token: " . $_SESSION['csrf_token']);
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch. Session token: ' . $_SESSION['csrf_token'] . ', Received token: ' . ($data['csrf_token'] ?? 'Not set');
            error_log("InvoiceController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $invoice_number = filter_var($data['invoice_number'], FILTER_SANITIZE_STRING);
        $customer_name = filter_var($data['customer_name'], FILTER_SANITIZE_STRING);
        $total_amount = filter_var($data['total_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $issue_date = filter_var($data['issue_date'], FILTER_SANITIZE_STRING);
        $status = filter_var($data['status'], FILTER_SANITIZE_STRING);

        if ($total_amount < 0 || !is_numeric($total_amount)) {
            $_SESSION['error'] = 'Invalid total amount value.';
            return false;
        }

        if (!in_array($status, ['Pending', 'Paid', 'Cancelled'])) {
            $_SESSION['error'] = 'Invalid status value.';
            return false;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $issue_date) || !strtotime($issue_date)) {
            $_SESSION['error'] = 'Invalid issue date format.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO invoices (invoice_number, customer_name, total_amount, issue_date, status)
                VALUES (:invoice_number, :customer_name, :total_amount, :issue_date, :status)
            ");
            $stmt->execute([
                ':invoice_number' => $invoice_number,
                ':customer_name' => $customer_name,
                ':total_amount' => $total_amount,
                ':issue_date' => $issue_date,
                ':status' => $status
            ]);
            $_SESSION['success'] = 'Invoice added successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("InvoiceController.php - Error adding invoice: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to add invoice: ' . $e->getMessage();
            return false;
        }
    }

    public function getInvoiceById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, invoice_number, customer_name, total_amount, issue_date, status FROM invoices WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("InvoiceController.php - Error fetching invoice: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to fetch invoice.';
            return false;
        }
    }

    public function updateInvoice($data) {
        error_log("InvoiceController.php - Received CSRF token from POST: " . ($data['csrf_token'] ?? 'Not set'));
        error_log("InvoiceController.php - Session CSRF token: " . $_SESSION['csrf_token']);
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch. Session token: ' . $_SESSION['csrf_token'] . ', Received token: ' . ($data['csrf_token'] ?? 'Not set');
            error_log("InvoiceController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
        $invoice_number = filter_var($data['invoice_number'], FILTER_SANITIZE_STRING);
        $customer_name = filter_var($data['customer_name'], FILTER_SANITIZE_STRING);
        $total_amount = filter_var($data['total_amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $issue_date = filter_var($data['issue_date'], FILTER_SANITIZE_STRING);
        $status = filter_var($data['status'], FILTER_SANITIZE_STRING);

        if ($total_amount < 0 || !is_numeric($total_amount)) {
            $_SESSION['error'] = 'Invalid total amount value.';
            return false;
        }

        if (!in_array($status, ['Pending', 'Paid', 'Cancelled'])) {
            $_SESSION['error'] = 'Invalid status value.';
            return false;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $issue_date) || !strtotime($issue_date)) {
            $_SESSION['error'] = 'Invalid issue date format.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE invoices
                SET invoice_number = :invoice_number, customer_name = :customer_name, total_amount = :total_amount, 
                    issue_date = :issue_date, status = :status
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':invoice_number' => $invoice_number,
                ':customer_name' => $customer_name,
                ':total_amount' => $total_amount,
                ':issue_date' => $issue_date,
                ':status' => $status
            ]);
            $_SESSION['success'] = 'Invoice updated successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("InvoiceController.php - Error updating invoice: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update invoice: ' . $e->getMessage();
            return false;
        }
    }

    public function deleteInvoice($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM invoices WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = 'Invoice deleted successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("InvoiceController.php - Error deleting invoice: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete invoice: ' . $e->getMessage();
            return false;
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    error_log("InvoiceController.php - Handling action: " . $_GET['action']);
    $controller = new InvoiceController();

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->addInvoice($_POST)) {
            header('Location: /thenuka-stores/pages/invoice.php');
        } else {
            header('Location: /thenuka-stores/pages/invoice.php');
        }
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->updateInvoice($_POST)) {
            header('Location: /thenuka-stores/pages/invoice.php');
        } else {
            header('Location: /thenuka-stores/pages/invoice.php');
        }
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        if ($controller->deleteInvoice($id)) {
            header('Location: /thenuka-stores/pages/invoice.php');
        } else {
            header('Location: /thenuka-stores/pages/invoice.php');
        }
        exit;
    }
}

ob_end_clean();
?>