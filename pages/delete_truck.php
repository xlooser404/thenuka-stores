<?php
require_once __DIR__ . '/../backend/config/database.php';

$database = new Database();
$db = $database->connect();

if ($db === false) {
    die("Database connection failed");
}

$id = isset($_GET['id']) ? $_GET['id'] : null;
$truck = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM trucks WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $truck = $stmt->fetch();
    if (!$truck) {
        die("Truck not found");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("DELETE FROM trucks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        header("Location: trucks.php");
        exit;
    } catch (Exception $e) {
        $error = "Error deleting truck: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Delete Truck - Delivery & Billing</title>
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link id="pagestyle" href="../assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
</head>
<body class="g-sidenav-show bg-gray-100">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-1">
                <?php include_once 'partials/sidebar.php'; ?>
            </div>
            <div class="col-md-11">
                <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
                    <?php include_once 'partials/navbar.php'; ?>
                    <div class="container-fluid py-4">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Delete Truck</h5>
                                        <p>Are you sure you want to delete the truck with license plate <strong><?php echo htmlspecialchars($truck['license_plate']); ?></strong>?</p>
                                        <form method="POST" action="">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                            <a href="trucks.php" class="btn btn-secondary">Cancel</a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>
</body>
</html>