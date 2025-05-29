<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(false);
}

error_log("SalesController.php - Session started, ID: " . session_id());
error_log("SalesController.php - Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("SalesController.php - Generated fallback CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin' || !isset($_SESSION['user']['id']) || !is_numeric($_SESSION['user']['id'])) {
    error_log("SalesController.php - Unauthorized access or missing user ID: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access or invalid session.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/sales.php'));
    exit;
}

class SalesController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("SalesController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/sales.php'));
            exit;
        }
        $attrs = [
            PDO::ATTR_ERRMODE => $this->db->getAttribute(PDO::ATTR_ERRMODE),
            PDO::ATTR_EMULATE_PREPARES => $this->db->getAttribute(PDO::ATTR_EMULATE_PREPARES),
            PDO::ATTR_DEFAULT_FETCH_MODE => $this->db->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE)
        ];
        error_log("SalesController.php - PDO attributes: " . json_encode($attrs));
    }

    public function getAllCustomers() {
        try {
            $stmt = $this->db->prepare("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM customers ORDER BY first_name, last_name ASC");
            $stmt->execute();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("SalesController.php - Fetched " . count($customers) . " customers.");
            return $customers;
        } catch (PDOException $e) {
            error_log("SalesController.php - Error fetching customers: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch customers.";
            return [];
        }
    }

    public function getAllProducts() {
        try {
            $stmt = $this->db->prepare("SELECT id, product_name, quantity, price_per_kg FROM inventory WHERE quantity > 0 ORDER BY product_name ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SalesController.php - Error fetching products: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch products.";
            return [];
        }
    }

    public function getCustomerDue($customer_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(subtotal) - SUM(CASE WHEN payment_method = 'cash' THEN subtotal ELSE 0 END) as due
                FROM sales
                WHERE customer_id = :customer_id
            ");
            $stmt->execute([':customer_id' => $customer_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['due'] ? floatval($result['due']) : 0.00;
        } catch (PDOException $e) {
            error_log("SalesController.php - Error fetching customer due: " . $e->getMessage());
            return 0.00;
        }
    }

    public function getSaleDetails($sale_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.id, s.sale_date, s.subtotal, s.payment_method, 
                       CONCAT(c.first_name, ' ', c.last_name) as customer_name
                FROM sales s
                JOIN customers c ON s.customer_id = c.id
                WHERE s.id = :sale_id
            ");
            $stmt->execute([':sale_id' => $sale_id]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->db->prepare("
                SELECT si.quantity_kg, si.price, i.product_name, i.price_per_kg
                FROM sale_items si
                JOIN inventory i ON si.product_id = i.id
                WHERE si.sale_id = :sale_id
            ");
            $stmt->execute([':sale_id' => $sale_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'sale' => $sale,
                'items' => $items
            ];
        } catch (PDOException $e) {
            error_log("SalesController.php - Error fetching sale details: " . $e->getMessage());
            return null;
        }
    }

    public function createSale($data) {
        error_log("SalesController.php - POST data: " . json_encode($data, JSON_PRETTY_PRINT));

        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("SalesController.php - CSRF validation failed.");
            return ['success' => false, 'error' => 'CSRF token mismatch.'];
        }

        if (!isset($data['products']) || !is_array($data['products']) || empty($data['products'])) {
            $_SESSION['error'] = 'No products selected for the sale.';
            error_log("SalesController.php - Invalid or empty products array.");
            return ['success' => false, 'error' => 'No products selected for the sale.'];
        }

        foreach ($data['products'] as $index => $product) {
            if (!isset($product['product_id'], $product['unit_price'], $product['quantity_kg'], $product['price']) ||
                !is_numeric($product['product_id']) ||
                !is_numeric($product['unit_price']) ||
                !is_numeric($product['quantity_kg']) ||
                !is_numeric($product['price'])) {
                $_SESSION['error'] = "Invalid product data at row " . ($index + 1);
                error_log("SalesController.php - Invalid product at row " . ($index + 1) . ": " . json_encode($product));
                return ['success' => false, 'error' => "Invalid product data at row " . ($index + 1)];
            }
        }

        $customer_id = filter_var($data['customer_id'], FILTER_VALIDATE_INT);
        $subtotal = filter_var($data['subtotal'], FILTER_VALIDATE_FLOAT);
        $payment_method = filter_var($data['payment_method'], FILTER_SANITIZE_STRING);

        if (!$customer_id || $subtotal <= 0 || !in_array($payment_method, ['cash', 'credit', 'online'])) {
            $_SESSION['error'] = 'Invalid sale data.';
            error_log("SalesController.php - Invalid sale data: customer_id=$customer_id, subtotal=$subtotal, payment_method=$payment_method");
            return ['success' => false, 'error' => 'Invalid sale data.'];
        }

        try {
            $this->db->beginTransaction();

            // Verify transaction_type ENUM
            $stmt = $this->db->query("SHOW COLUMNS FROM inventory_transactions LIKE 'transaction_type'");
            $column = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("SalesController.php - transaction_type ENUM: " . $column['Type']);

            // Insert sale
            error_log("SalesController.php - Inserting sale");
            $stmt = $this->db->prepare("
                INSERT INTO sales (customer_id, subtotal, previous_due, payment_method, created_by)
                VALUES (:customer_id, :subtotal, :previous_due, :payment_method, :created_by)
            ");
            $params = [
                ':customer_id' => $customer_id,
                ':subtotal' => $subtotal,
                ':previous_due' => $this->getCustomerDue($customer_id),
                ':payment_method' => $payment_method,
                ':created_by' => $_SESSION['user']['id']
            ];
            error_log("SalesController.php - Sales insert params: " . json_encode($params));
            $stmt->execute($params);
            $sale_id = $this->db->lastInsertId();
            error_log("SalesController.php - Sale inserted, sale_id=$sale_id");

            foreach ($data['products'] as $index => $product) {
                error_log("SalesController.php - Processing product row " . ($index + 1) . ": " . json_encode($product));
                $product_id = filter_var($product['product_id'], FILTER_VALIDATE_INT);
                $unit_price = filter_var($product['unit_price'], FILTER_VALIDATE_FLOAT);
                $quantity_kg = filter_var($product['quantity_kg'], FILTER_VALIDATE_FLOAT);
                $price = filter_var($product['price'], FILTER_VALIDATE_FLOAT);

                if (!$product_id || $quantity_kg <= 0 || $price <= 0 || $unit_price <= 0) {
                    throw new Exception("Invalid product data at row " . ($index + 1));
                }

                // Verify unit price
                error_log("SalesController.php - Verifying unit price for product_id=$product_id");
                $stmt = $this->db->prepare("SELECT price_per_kg FROM inventory WHERE id = :product_id");
                $stmt->execute([':product_id' => $product_id]);
                $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$inventory || abs($unit_price - $inventory['price_per_kg']) > 0.01) {
                    throw new Exception("Invalid unit price for product ID $product_id at row " . ($index + 1));
                }

                if (abs($price - ($quantity_kg * $unit_price)) > 0.01) {
                    throw new Exception("Invalid total price for product ID $product_id at row " . ($index + 1));
                }

                // Insert sale item
                error_log("SalesController.php - Inserting sale item");
                $stmt = $this->db->prepare("
                    INSERT INTO sale_items (sale_id, product_id, quantity_kg, price)
                    VALUES (:sale_id, :product_id, :quantity_kg, :price)
                ");
                $stmt->execute([
                    ':sale_id' => $sale_id,
                    ':product_id' => $product_id,
                    ':quantity_kg' => $quantity_kg,
                    ':price' => $price
                ]);

                // Update inventory
                error_log("SalesController.php - Updating inventory");
                $stmt = $this->db->prepare("
                    UPDATE inventory
                    SET quantity = quantity - :quantity_kg
                    WHERE id = :product_id AND quantity >= :quantity_kg_check
                ");
                $params = [
                    ':quantity_kg' => $quantity_kg,
                    ':product_id' => $product_id,
                    ':quantity_kg_check' => $quantity_kg
                ];
                error_log("SalesController.php - Inventory update params: " . json_encode($params));
                $stmt->execute($params);
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Insufficient stock for product ID $product_id at row " . ($index + 1));
                }

                // Log transaction
                error_log("SalesController.php - Logging transaction");
                $stmt = $this->db->prepare("
                    INSERT INTO inventory_transactions (inventory_id, transaction_type, quantity, reason, created_by)
                    VALUES (:inventory_id, 'SALE', :quantity, :reason, :created_by)
                ");
                $params = [
                    ':inventory_id' => $product_id,
                    ':quantity' => $quantity_kg,
                    ':reason' => "Sale #$sale_id",
                    ':created_by' => $_SESSION['user']['id']
                ];
                error_log("SalesController.php - Transaction insert params: " . json_encode($params));
                $stmt->execute($params);
            }

            $this->db->commit();
            $sale_details = $this->getSaleDetails($sale_id);
            $_SESSION['success'] = 'Sale created successfully!';
            error_log("SalesController.php - Sale created, sale_id=$sale_id");
            return [
                'success' => true,
                'message' => 'Sale created successfully!',
                'sale_id' => $sale_id,
                'sale_details' => $sale_details
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("SalesController.php - Failed to create sale: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to create sale: ' . $e->getMessage();
            return ['success' => false, 'error' => 'Failed to create sale: ' . $e->getMessage()];
        }
    }
}

if (isset($_GET['action'])) {
    error_log("SalesController.php - Handling action: " . htmlspecialchars($_GET['action']));
    $controller = new SalesController();

    if ($_GET['action'] === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = $controller->createSale($_POST);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            header('Location: /thenuka-stores/pages/sales.php');
        }
        exit;
    }

    if ($_GET['action'] === 'getDue' && isset($_GET['customer_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['due' => $controller->getCustomerDue(filter_var($_GET['customer_id'], FILTER_VALIDATE_INT))]);
        exit;
    }
}

ob_end_flush();
?>