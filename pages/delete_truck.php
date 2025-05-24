<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../backend/config/database.php';

$database = new Database();
$db = $database->connect();

if ($db === false) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM trucks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}


?>