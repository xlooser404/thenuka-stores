<?php
session_start();
require_once __DIR__ . '/../config/Database.php';

class CustomerController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("Database connection failed in CustomerController.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header('Location: ../../pages/login.php');
            exit;
        }
    }

    public function getAllCustomers() {
        try {
            $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, phone, address FROM customers ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching customers: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch customers.";
            return [];
        }
    }

    public function addCustomer($data) {
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token validation failed.';
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
            error_log("Error adding customer: " . $e->getMessage());
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
            error_log("Error fetching customer: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to fetch customer.';
            return false;
        }
    }

    public function updateCustomer($data) {
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token validation failed.';
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
            error_log("Error updating customer: " . $e->getMessage());
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
            error_log("Error deleting customer: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete customer: ' . $e->getMessage();
            return false;
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    $controller = new CustomerController();

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->addCustomer($_POST)) {
            header('Location: ../../pages/customers.php');
        } else {
            header('Location: ../../pages/customers.php');
        }
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->updateCustomer($_POST)) {
            header('Location: ../../pages/customers.php');
        } else {
            header('Location: ../../pages/customers.php');
        }
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        if ($controller->deleteCustomer($id)) {
            header('Location: /delivery-billing/pages/customers.php');
        } else {
            header('Location: /delivery-billing/pages/customers.php');
        }
        exit;
    }
}
?>