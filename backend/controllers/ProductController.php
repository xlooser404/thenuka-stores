<?php
// Start output buffering immediately
ob_start();

// Log headers status and output buffer
error_log("ProductController.php - Headers sent before start: " . (headers_sent() ? 'Yes' : 'No'));
error_log("ProductController.php - Initial output buffer: " . ob_get_contents());

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    // Prevent session ID regeneration
    session_regenerate_id(false);
}

// Log session start
error_log("ProductController.php - Session started, ID: " . session_id());
error_log("ProductController.php - Session data: " . print_r($_SESSION, true));

// Fallback CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("ProductController.php - Generated fallback CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/Database.php';

// Log after include
error_log("ProductController.php - After Database.php include, headers sent: " . (headers_sent() ? 'Yes' : 'No'));

// Redirect to login page if not logged in or not admin
if (!isset($_SESSION['user']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    error_log("ProductController.php - Unauthorized access: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/products.php'));
    exit;
}

class ProductController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("ProductController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/products.php'));
            exit;
        }
    }

    public function getAllProducts() {
        try {
            $stmt = $this->db->prepare("SELECT id, name, description, price, stock, category FROM products ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProductController.php - Error fetching products: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch products.";
            return [];
        }
    }

    public function addProduct($data) {
        // Debug CSRF token
        error_log("ProductController.php - addProduct: POST data: " . print_r($data, true));
        error_log("ProductController.php - addProduct: Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));
        error_log("ProductController.php - addProduct: Submitted CSRF token: " . ($data['csrf_token'] ?? 'Not set'));

        if (!isset($data['csrf_token'])) {
            $_SESSION['error'] = 'CSRF token missing in form submission.';
            error_log("ProductController.php - CSRF validation failed: Token missing.");
            return false;
        }
        if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("ProductController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
        $description = filter_var($data['description'], FILTER_SANITIZE_STRING);
        $price = filter_var($data['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $stock = filter_var($data['stock'], FILTER_SANITIZE_NUMBER_INT);
        $category = filter_var($data['category'], FILTER_SANITIZE_STRING);

        // Validate price and stock
        if ($price < 0 || !is_numeric($price)) {
            $_SESSION['error'] = 'Invalid price format.';
            return false;
        }
        if ($stock < 0 || !is_numeric($stock)) {
            $_SESSION['error'] = 'Invalid stock value.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO products (name, description, price, stock, category)
                VALUES (:name, :description, :price, :stock, :category)
            ");
            $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':stock' => $stock,
                ':category' => $category
            ]);
            $_SESSION['success'] = 'Product added successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("ProductController.php - Error adding product: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to add product: ' . $e->getMessage();
            return false;
        }
    }

    public function getProductById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, name, description, price, stock, category FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProductController.php - Error fetching product: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to fetch product.';
            return false;
        }
    }

    public function updateProduct($data) {
        // Debug CSRF token
        error_log("ProductController.php - updateProduct: POST data: " . print_r($data, true));
        error_log("ProductController.php - updateProduct: Session CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));
        error_log("ProductController.php - updateProduct: Submitted CSRF token: " . ($data['csrf_token'] ?? 'Not set'));

        if (!isset($data['csrf_token'])) {
            $_SESSION['error'] = 'CSRF token missing in form submission.';
            error_log("ProductController.php - CSRF validation failed: Token missing.");
            return false;
        }
        if ($data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("ProductController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
        $name = filter_var($data['name'], FILTER_SANITIZE_STRING);
        $description = filter_var($data['description'], FILTER_SANITIZE_STRING);
        $price = filter_var($data['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $stock = filter_var($data['stock'], FILTER_SANITIZE_NUMBER_INT);
        $category = filter_var($data['category'], FILTER_SANITIZE_STRING);

        // Validate price and stock
        if ($price < 0 || !is_numeric($price)) {
            $_SESSION['error'] = 'Invalid price format.';
            return false;
        }
        if ($stock < 0 || !is_numeric($stock)) {
            $_SESSION['error'] = 'Invalid stock value.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE products
                SET name = :name, description = :description, price = :price, stock = :stock, category = :category
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':stock' => $stock,
                ':category' => $category
            ]);
            $_SESSION['success'] = 'Product updated successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("ProductController.php - Error updating product: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update product: ' . $e->getMessage();
            return false;
        }
    }

    public function deleteProduct($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = 'Product deleted successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("ProductController.php - Error deleting product: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete product: ' . $e->getMessage();
            return false;
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    error_log("ProductController.php - Handling action: " . $_GET['action']);
    $controller = new ProductController();

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->addProduct($_POST)) {
            header('Location: /thenuka-stores/pages/products.php');
        } else {
            header('Location: /thenuka-stores/pages/products.php');
        }
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->updateProduct($_POST)) {
            header('Location: /thenuka-stores/pages/products.php');
        } else {
            header('Location: /thenuka-stores/pages/products.php');
        }
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        if ($controller->deleteProduct($id)) {
            header('Location: /thenuka-stores/pages/products.php');
        } else {
            header('Location: /thenuka-stores/pages/products.php');
        }
        exit;
    }

    if ($_GET['action'] === 'get' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        $product = $controller->getProductById($id);
        if ($product) {
            header('Content-Type: application/json');
            echo json_encode($product);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Product not found']);
        }
        exit;
    }
}

// Clean output buffer
ob_end_clean();
?>