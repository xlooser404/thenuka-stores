<?php
session_start();
error_log("trucks.php - Session ID: " . session_id());
error_log("trucks.php - Session data: " . print_r($_SESSION, true));
error_log("trucks.php - CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

// Restrict access to admin role
if (!isset($_SESSION['user'])) {
    error_log("trucks.php - No user session.");
    $_SESSION['error'] = 'Unauthorized access: No user session.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/trucks.php'));
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    error_log("trucks.php - User role: " . $_SESSION['user']['role']);
    $_SESSION['error'] = 'Unauthorized access: Not an admin.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/trucks.php'));
    exit;
}

// Generate CSRF token once per session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("trucks.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

// Log form hidden fields
error_log("trucks.php - Form hidden fields: " . print_r(['csrf_token' => $_SESSION['csrf_token']], true));

// Include TruckController
require_once __DIR__ . '/../backend/controllers/TruckController.php';
$controller = new TruckController();
$trucks = $controller->getAllTrucks();

// Prepare data for table.php
$title = "Trucks Table";
$headers = [
    ['label' => 'ID', 'class' => ''],
    ['label' => 'Truck Number', 'class' => ''],
    ['label' => 'Driver Name', 'class' => ''],
    ['label' => 'Capacity (kg)', 'class' => ''],
    ['label' => 'Status', 'class' => ''],
    ['label' => 'Actions', 'class' => 'text-center']
];
$rows = [];
foreach ($trucks as $truck) {
    $rows[] = [
        'data' => [
            ['value' => htmlspecialchars($truck['id']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($truck['truck_number']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($truck['driver_name']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($truck['capacity']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($truck['status']), 'text_class' => 'text-sm']
        ],
        'actions' => [
            'edit' => "javascript:void(0);",
            'delete' => "/thenuka-stores/backend/controllers/TruckController.php?action=delete&id=" . $truck['id']
        ]
    ];
}
$actions = [
    'edit' => 'Edit',
    'delete' => 'Delete'
];
$add_button_label = "Add Truck";
$form_fields = [
    'truck_number' => ['label' => 'Truck Number', 'type' => 'text', 'required' => true],
    'driver_name' => ['label' => 'Driver Name', 'type' => 'text', 'required' => true],
    'capacity' => ['label' => 'Capacity (kg)', 'type' => 'number', 'required' => true],
    'status' => ['label' => 'Status', 'type' => 'select', 'options' => [
        'Available' => 'Available',
        'In Transit' => 'In Transit',
        'Maintenance' => 'Maintenance'
    ], 'required' => true]
];
$form_action = "/thenuka-stores/backend/controllers/TruckController.php?action=add";
$form_hidden_fields = [
    'csrf_token' => $_SESSION['csrf_token']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <title>Trucks - Delivery & Billing</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link id="pagestyle" href="../assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
</head>
<body class="g-sidenav-show bg-gray-100">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2">
                <?php include 'partials/sidebar.php'; ?>
            </div>
            <div class="col-md-10">
                <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
                    <?php include 'partials/navbar.php'; ?>
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-12">
                                <!-- Success/Error Messages -->
                                <?php if (isset($_SESSION['success'])): ?>
                                    <div class="alert alert-success text-white">
                                        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger text-white">
                                        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                    </div>
                                <?php endif; ?>
                                <!-- Trucks Table -->
                                <?php
                                include 'partials/table.php';
                                renderTable($title, $headers, $rows, $actions, $add_button_label, $form_fields, $form_action, $form_hidden_fields);
                                ?>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Edit Truck Modal -->
    <div class="modal fade" id="editTruckModal" tabindex="-1" aria-labelledby="editTruckModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTruckModalLabel">Edit Truck</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/thenuka-stores/backend/controllers/TruckController.php?action=update" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_truck_number" class="form-label">Truck Number</label>
                            <input type="text" class="form-control" id="edit_truck_number" name="truck_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_driver_name" class="form-label">Driver Name</label>
                            <input type="text" class="form-control" id="edit_driver_name" name="driver_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_capacity" class="form-label">Capacity (kg)</label>
                            <input type="number" class="form-control" id="edit_capacity" name="capacity" required min="0">
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Available">Available</option>
                                <option value="In Transit">In Transit</option>
                                <option value="Maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Truck</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>
    <script>
        // Populate edit modal with truck data
        function populateEditModal(truck) {
            document.getElementById('edit_id').value = truck.id;
            document.getElementById('edit_truck_number').value = truck.truck_number;
            document.getElementById('edit_driver_name').value = truck.driver_name;
            document.getElementById('edit_capacity').value = truck.capacity;
            document.getElementById('edit_status').value = truck.status;
        }

        // Initialize edit modal on click
        document.querySelectorAll('a[data-action="edit"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const truckId = parseInt(this.getAttribute('data-id'));
                const trucks = <?php echo json_encode($trucks); ?>;
                const truck = trucks.find(t => t.id === truckId);
                if (truck) {
                    populateEditModal(truck);
                    const modal = new bootstrap.Modal(document.getElementById('editTruckModal'));
                    modal.show();
                } else {
                    console.error('Truck not found for ID:', truckId);
                }
            });
        });

        // Confirm delete action
        document.querySelectorAll('a[data-action="delete"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this truck?')) {
                    e.preventDefault();
                }
            });
        });

        // Initialize tooltips for action buttons
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
            new bootstrap.Tooltip(element);
        });

        // Initialize scrollbar for Windows
        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = { damping: '0.5' };
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }
    </script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>
</body>
</html>