<?php
session_start();
error_log("orders.php - Session ID: " . session_id());
error_log("orders.php - Session data: " . print_r($_SESSION, true));
error_log("orders.php - CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

// Restrict access to admin role
if (!isset($_SESSION['user'])) {
    error_log("orders.php - No user session.");
    $_SESSION['error'] = 'Unauthorized access: No user session.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/orders.php'));
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    error_log("orders.php - User role: " . $_SESSION['user']['role']);
    $_SESSION['error'] = 'Unauthorized access: Not an admin.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/orders.php'));
    exit;
}

// Generate CSRF token once per session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("orders.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

// Log form hidden fields
$csrfToken = $_SESSION['csrf_token'];
error_log("orders.php - Form hidden fields: " . print_r(['csrf_token' => $csrfToken], true));

// Include OrderController
require_once __DIR__ . '/../backend/controllers/OrderController.php';
$controller = new OrderController();
$orders = $controller->getAllOrders();

// Prepare data for table.php
$title = "Orders Table";
$headers = [
    ['label' => 'ID', 'class' => ''],
    ['label' => 'Order Number', 'class' => ''],
    ['label' => 'Customer Name', 'class' => ''],
    ['label' => 'Total Amount', 'class' => ''],
    ['label' => 'Order Date', 'class' => ''],
    ['label' => 'Status', 'class' => ''],
    ['label' => 'Actions', 'class' => 'text-center']
];
$rows = [];
foreach ($orders as $order) {
    $rows[] = [
        'data' => [
            ['value' => htmlspecialchars($order['id']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($order['order_number']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($order['customer_name']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars(number_format($order['total_amount'], 2)), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($order['order_date']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($order['status']), 'text_class' => 'text-sm']
        ],
        'actions' => [
            'edit' => "javascript:void(0);",
            'delete' => "/thenuka-stores/backend/controllers/OrderController.php?action=delete&id=" . $order['id']
        ]
    ];
}
$actions = [
    'edit' => 'Edit',
    'delete' => 'Delete'
];
$add_button_label = "Add Order";
$form_fields = [
    'order_number' => ['label' => 'Order Number', 'type' => 'text', 'required' => true],
    'customer_name' => ['label' => 'Customer Name', 'type' => 'text', 'required' => true],
    'total_amount' => ['label' => 'Total Amount', 'type' => 'number', 'required' => true, 'step' => '0.01'],
    'order_date' => ['label' => 'Order Date', 'type' => 'date', 'required' => true],
    'status' => ['label' => 'Status', 'type' => 'select', 'options' => [
        'Pending' => 'Pending',
        'Shipped' => 'Shipped',
        'Delivered' => 'Delivered',
        'Cancelled' => 'Cancelled'
    ], 'required' => true]
];
$form_action = "/thenuka-stores/backend/controllers/OrderController.php?action=add";
$form_hidden_fields = [
    'csrf_token' => $csrfToken
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <title>Orders - Delivery & Billing</title>
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
                                <!-- Orders Table -->
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

    <!-- Edit Order Modal -->
    <div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOrderModalLabel">Edit Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/thenuka-stores/backend/controllers/OrderController.php?action=update" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_order_number" class="form-label">Order Number</label>
                            <input type="text" class="form-control" id="edit_order_number" name="order_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_customer_name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="edit_customer_name" name="customer_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_total_amount" class="form-label">Total Amount</label>
                            <input type="number" step="0.01" class="form-control" id="edit_total_amount" name="total_amount" required min="0">
                        </div>
                        <div class="mb-3">
                            <label for="edit_order_date" class="form-label">Order Date</label>
                            <input type="date" class="form-control" id="edit_order_date" name="order_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Pending">Pending</option>
                                <option value="Shipped">Shipped</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Order</button>
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
        // Populate edit modal with order data
        function populateEditModal(order) {
            console.log('Populating modal with order:', order);
            document.getElementById('edit_id').value = order.id;
            document.getElementById('edit_order_number').value = order.order_number;
            document.getElementById('edit_customer_name').value = order.customer_name;
            document.getElementById('edit_total_amount').value = order.total_amount;
            document.getElementById('edit_order_date').value = order.order_date;
            document.getElementById('edit_status').value = order.status;
        }

        // Initialize edit modal on click
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM fully loaded, initializing edit modal listeners');
            const editLinks = document.querySelectorAll('a[data-action="edit"]');
            console.log('Found edit links:', editLinks.length);
            editLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Edit link clicked, data-id:', this.getAttribute('data-id'));
                    const orderId = parseInt(this.getAttribute('data-id'));
                    const orders = <?php echo json_encode($orders); ?>;
                    const order = orders.find(o => o.id === orderId);
                    if (order) {
                        populateEditModal(order);
                        const modal = new bootstrap.Modal(document.getElementById('editOrderModal'));
                        console.log('Showing modal');
                        modal.show();
                    } else {
                        console.error('Order not found for ID:', orderId);
                    }
                });
            });
        });

        // Confirm delete action
        document.querySelectorAll('a[data-action="delete"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this order?')) {
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