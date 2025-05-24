<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
    session_regenerate_id(false);
}

error_log("AgentController.php - Session started, ID: " . session_id());
error_log("AgentController.php - Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("AgentController.php - Generated fallback CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user']) || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] !== 'admin')) {
    error_log("AgentController.php - Unauthorized access: Session data: " . print_r($_SESSION ?? [], true));
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/agents.php'));
    exit;
}

class AgentController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        if (!$this->db) {
            error_log("AgentController.php - Database connection failed.");
            $_SESSION['error'] = "Unable to connect to the database.";
            header("Location: /thenuka-stores/pages/login.php?redirect=" . urlencode('/thenuka-stores/pages/agents.php'));
            exit;
        }
    }

    public function getAllAgents() {
        try {
            $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, phone, region FROM agents ORDER BY id DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AgentController.php - Error fetching agents: " . $e->getMessage());
            $_SESSION['error'] = "Failed to fetch agents.";
            return [];
        }
    }

    public function addAgent($data) {
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("AgentController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $first_name = filter_var($data['first_name'], FILTER_SANITIZE_STRING);
        $last_name = filter_var($data['last_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
        $region = filter_var($data['region'], FILTER_SANITIZE_STRING);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO agents (first_name, last_name, email, phone, region)
                VALUES (:first_name, :last_name, :email, :phone, :region)
            ");
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':phone' => $phone,
                ':region' => $region
            ]);
            $_SESSION['success'] = 'Agent added successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("AgentController.php - Error adding agent: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to add agent: ' . $e->getMessage();
            return false;
        }
    }

    public function getAgentById($id) {
        try {
            $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, phone, region FROM agents WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AgentController.php - Error fetching agent: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to fetch agent.';
            return false;
        }
    }

    public function updateAgent($data) {
        if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'CSRF token mismatch.';
            error_log("AgentController.php - CSRF validation failed: Token mismatch.");
            return false;
        }

        $id = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
        $first_name = filter_var($data['first_name'], FILTER_SANITIZE_STRING);
        $last_name = filter_var($data['last_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $phone = filter_var($data['phone'], FILTER_SANITIZE_STRING);
        $region = filter_var($data['region'], FILTER_SANITIZE_STRING);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email format.';
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE agents
                SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, region = :region
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':email' => $email,
                ':phone' => $phone,
                ':region' => $region
            ]);
            $_SESSION['success'] = 'Agent updated successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("AgentController.php - Error updating agent: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to update agent: ' . $e->getMessage();
            return false;
        }
    }

    public function deleteAgent($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM agents WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = 'Agent deleted successfully.';
            return true;
        } catch (PDOException $e) {
            error_log("AgentController.php - Error deleting agent: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete agent: ' . $e->getMessage();
            return false;
        }
    }
}

// Handle requests
if (isset($_GET['action'])) {
    error_log("AgentController.php - Handling action: " . $_GET['action']);
    $controller = new AgentController();

    if ($_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->addAgent($_POST)) {
            header('Location: /thenuka-stores/pages/agents.php');
        } else {
            header('Location: /thenuka-stores/pages/agents.php');
        }
        exit;
    }

    if ($_GET['action'] === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($controller->updateAgent($_POST)) {
            header('Location: /thenuka-stores/pages/agents.php');
        } else {
            header('Location: /thenuka-stores/pages/agents.php');
        }
        exit;
    }

    if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
        if ($controller->deleteAgent($id)) {
            header('Location: /thenuka-stores/pages/agents.php');
        } else {
            header('Location: /thenuka-stores/pages/agents.php');
        }
        exit;
    }
}

ob_end_clean();
?>