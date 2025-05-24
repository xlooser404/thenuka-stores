<?php
session_start();
error_log("inventory.php - Session ID: " . session_id());
error_log("inventory.php - Session data: " . print_r($_SESSION, true));
error_log("inventory.php - CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

// Restrict access to admin role
if (!isset($_SESSION['user'])) {
    error_log("inventory.php - No user session.");
    $_SESSION['error'] = 'Unauthorized access: No user session.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/inventory.php'));
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    error_log("inventory.php - User role: " . $_SESSION['user']['role']);
    $_SESSION['error'] = 'Unauthorized access: Not an admin.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/inventory.php'));
    exit;
}

// Generate CSRF token once per session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("inventory.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

// Log form hidden fields
error_log("inventory.php - Form hidden fields: " . print_r(['csrf_token' => $_SESSION['csrf_token']], true));

// Include InventoryController
require_once __DIR__ . '/../backend/controllers/InventoryController.php';
$controller = new InventoryController();
$inventory = $controller->getAllInventory();

// Prepare data for table.php
$title = "Inventory Table";
$headers = [
    ['label' => 'ID', 'class' => ''],
    ['label' => 'Product Name', 'class' => ''],
    ['label' => 'Quantity', 'class' => ''],
    ['label' => 'Location', 'class' => ''],
    ['label' => 'Last Updated', 'class' => ''],
    ['label' => 'Actions', 'class' => 'text-center']
];
$rows = [];
foreach ($inventory as $item) {
    $rows[] = [
        'data' => [
            ['value' => htmlspecialchars($item['id']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($item['product_name']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($item['quantity']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($item['location'] ?? 'N/A'), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($item['last_updated'] ?? 'N/A'), 'text_class' => 'text-sm']
        ],
        'actions' => [
            'edit' => "javascript:void(0);",
            'delete' => "/thenuka-stores/backend/controllers/InventoryController.php?action=delete&id=" . $item['id']
        ]
    ];
}
$actions = [
    'edit' => 'Edit',
    'delete' => 'Delete'
];
$add_button_label = "Add Inventory Item";
$form_fields = [
    'product_name' => ['label' => 'Product Name', 'type' => 'text'],
    'quantity' => ['label' => 'Quantity', 'type' => 'number', 'required' => true],
    'location' => ['label' => 'Location', 'type' => 'text']
];
$form_action = "/thenuka-stores/backend/controllers/InventoryController.php?action=add";
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
    <title>Inventory - Delivery & Billing</title>
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
                                <!-- Inventory Table -->
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

    <!-- Edit Inventory Modal -->
    <div class="modal fade" id="editInventoryModal" tabindex="-1" aria-labelledby="editInventoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editInventoryModalLabel">Edit Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/thenuka-stores/backend/controllers/InventoryController.php?action=update" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_product_name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="edit_product_name" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" required min="0">
                        </div>
                        <div class="mb-3">
                            <label for="edit_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="edit_location" name="location">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Inventory</button>
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
        // Populate edit modal with inventory data
        function populateEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_product_name').value = item.product_name;
            document.getElementById('edit_quantity').value = item.quantity;
            document.getElementById('edit_location').value = item.location || '';
        }

        // Initialize edit modal on click
        document.querySelectorAll('a[data-action="edit"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const itemId = parseInt(this.getAttribute('data-id'));
                const inventory = <?php echo json_encode($inventory); ?>;
                const item = inventory.find(i => i.id === itemId);
                if (item) {
                    populateEditModal(item);
                    const modal = new bootstrap.Modal(document.getElementById('editInventoryModal'));
                    modal.show();
                } else {
                    console.error('Inventory item not found for ID:', itemId);
                }
            });
        });

        // Confirm delete action
        document.querySelectorAll('a[data-action="delete"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this inventory item?')) {
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
</body>
</html>