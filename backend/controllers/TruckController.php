<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(false);
}

error_log("TruckController.php - Session started, ID: " . session_id());
error_log("TruckController.php - Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("TruckController.php - Generated fallback CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    error_log("TruckController.php - Unauthorized access: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/trucks.php'));
    exit;
}

class TruckController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("TruckController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/trucks.php'));
            exit;
        }
    }

    public function getAllTrucks() {
        try {
            $stmt = $this->db->prepare("SELECT id, truck_number, driver_name, capacity, status FROM trucks ORDER BY id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TruckController.php - Error fetching trucks: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch trucks.";
            return [];
        }
    }

    public function addTruck($data) {
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("TruckController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $truck_number = filter_var($data['truck_number'], FILTER_SANITIZE_STRING);
        $driver_name = filter_var($data['driver_name'], FILTER_SANITIZE_STRING);
        $capacity = filter_var($data['capacity'], FILTER_SANITIZE_NUMBER_INT);
        $status = filter_var($data['status'], FILTER_SANITIZE_STRING);

        if ($capacity < 0 || !is_numeric($capacity)) {
            $_SESSION['error'] = 'Invalid capacity value.';
            return false;
        }

        if (!in_array($status, ['Available', 'In Transit', 'Maintenance'])) {
            $_SESSION['error'] = 'Invalid status value.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO trucks (truck_number, driver_name, capacity, status)
                VALUES (:truck_number, :driver_name, :capacity, :status)
            ");
            $stmt->execute([
                ':truck_number' => $truck_number,
                ':driver_name' => $driver_name,
                ':capacity' => $capacity,
                ':status' => $status
            ]);
            $_SESSION['success'] = 'Truck added successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("TruckController.php - Error adding truck: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to add truck: ' . $e->getMessage();
            return false;
        }
    }

    public function getTruckById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, truck_number, driver_name, capacity, status FROM trucks WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TruckController.php - Error fetching truck: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to fetch truck.';
            return false;
        }
    }

    public function updateTruck($data) {
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("TruckController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
        $truck_number = filter_var($data['truck_number'], FILTER_SANITIZE_STRING);
        $driver_name = filter_var($data['driver_name'], FILTER_SANITIZE_STRING);
        $capacity = filter_var($data['capacity'], FILTER_SANITIZE_NUMBER_INT);
        $status = filter_var($data['status'], FILTER_SANITIZE_STRING);

        if ($capacity < 0 || !is_numeric($capacity)) {
            $_SESSION['error'] = 'Invalid capacity value.';
            return false;
        }

        if (!in_array($status, ['Available', 'In Transit', 'Maintenance'])) {
            $_SESSION['error'] = 'Invalid status value.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE trucks
                SET truck_number = :truck_number, driver_name = :driver_name, capacity = :capacity, status = :status
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':truck_number' => $truck_number,
                ':driver_name' => $driver_name,
                ':capacity' => $capacity,
                ':status' => $status
            ]);
            $_SESSION['success'] = 'Truck updated successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("TruckController.php - Error updating truck: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update truck: ' . $e->getMessage();
            return false;
        }
    }

    public function deleteTruck($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM trucks WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = 'Truck deleted successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("TruckController.php - Error deleting truck: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete truck: ' . $e->getMessage();
            return false;
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    error_log("TruckController.php - Handling action: " . $_GET['action']);
    $controller = new TruckController();

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->addTruck($_POST)) {
            header('Location: /thenuka-stores/pages/trucks.php');
        } else {
            header('Location: /thenuka-stores/pages/trucks.php');
        }
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->updateTruck($_POST)) {
            header('Location: /thenuka-stores/pages/trucks.php');
        } else {
            header('Location: /thenuka-stores/pages/trucks.php');
        }
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        if ($controller->deleteTruck($id)) {
            header('Location: /thenuka-stores/pages/trucks.php');
        } else {
            header('Location: /thenuka-stores/pages/trucks.php');
        }
        exit;
    }
}

ob_end_clean();
?>