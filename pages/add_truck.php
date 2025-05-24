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
        $license_plate = $_POST['license_plate'];
        $model = $_POST['model'];
        $capacity = $_POST['capacity'];
        $status = $_POST['status'];
        $last_maintenance = $_POST['last_maintenance'];

        $stmt = $db->prepare("INSERT INTO trucks (license_plate, model, capacity, status, last_maintenance) VALUES (:license_plate, :model, :capacity, :status, :last_maintenance)");
        $stmt->execute([
            'license_plate' => $license_plate,
            'model' => $model,
            'capacity' => $capacity,
            'status' => $status,
            'last_maintenance' => $last_maintenance
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>