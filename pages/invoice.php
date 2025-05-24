<?php
session_start();
error_log("invoice.php - Session ID: " . session_id());
error_log("invoice.php - Session data: " . print_r($_SESSION, true));
error_log("invoice.php - CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

// Restrict access to admin role
if (!isset($_SESSION['user'])) {
    error_log("invoice.php - No user session.");
    $_SESSION['error'] = 'Unauthorized access: No user session.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/invoice.php'));
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    error_log("invoice.php - User role: " . $_SESSION['user']['role']);
    $_SESSION['error'] = 'Unauthorized access: Not an admin.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/invoice.php'));
    exit;
}

// Generate CSRF token once per session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("invoice.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

// Log form hidden fields
$csrfToken = $_SESSION['csrf_token'];
error_log("invoice.php - Form hidden fields: " . print_r(['csrf_token' => $csrfToken], true));

// Include InvoiceController
require_once __DIR__ . '/../backend/controllers/InvoiceController.php';
$controller = new InvoiceController();
$invoices = $controller->getAllInvoices();

// Prepare data for table.php
$title = "Invoices Table";
$headers = [
    ['label' => 'ID', 'class' => ''],
    ['label' => 'Invoice Number', 'class' => ''],
    ['label' => 'Customer Name', 'class' => ''],
    ['label' => 'Total Amount', 'class' => ''],
    ['label' => 'Issue Date', 'class' => ''],
    ['label' => 'Status', 'class' => ''],
    ['label' => 'Actions', 'class' => 'text-center']
];
$rows = [];
foreach ($invoices as $invoice) {
    $rows[] = [
        'data' => [
            ['value' => htmlspecialchars($invoice['id']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($invoice['invoice_number']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($invoice['customer_name']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars(number_format($invoice['total_amount'], 2)), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($invoice['issue_date']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($invoice['status']), 'text_class' => 'text-sm']
        ],
        'actions' => [
            'edit' => "javascript:void(0);",
            'delete' => "/thenuka-stores/backend/controllers/InvoiceController.php?action=delete&id=" . $invoice['id']
        ]
    ];
}
$actions = [
    'edit' => 'Edit',
    'delete' => 'Delete'
];
$add_button_label = "Add Invoice";
$form_fields = [
    'invoice_number' => ['label' => 'Invoice Number', 'type' => 'text', 'required' => true],
    'customer_name' => ['label' => 'Customer Name', 'type' => 'text', 'required' => true],
    'total_amount' => ['label' => 'Total Amount', 'type' => 'number', 'required' => true, 'step' => '0.01'],
    'issue_date' => ['label' => 'Issue Date', 'type' => 'date', 'required' => true],
    'status' => ['label' => 'Status', 'type' => 'select', 'options' => [
        'Pending' => 'Pending',
        'Paid' => 'Paid',
        'Cancelled' => 'Cancelled'
    ], 'required' => true]
];
$form_action = "/thenuka-stores/backend/controllers/InvoiceController.php?action=add";
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
    <title>Invoices - Delivery & Billing</title>
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
                                <!-- Invoices Table -->
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

    <!-- Edit Invoice Modal -->
    <div class="modal fade" id="editInvoiceModal" tabindex="-1" aria-labelledby="editInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editInvoiceModalLabel">Edit Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/thenuka-stores/backend/controllers/InvoiceController.php?action=update" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_invoice_number" class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" id="edit_invoice_number" name="invoice_number" required>
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
                            <label for="edit_issue_date" class="form-label">Issue Date</label>
                            <input type="date" class="form-control" id="edit_issue_date" name="issue_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Pending">Pending</option>
                                <option value="Paid">Paid</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Invoice</button>
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
        // Populate edit modal with invoice data
        function populateEditModal(invoice) {
            console.log('Populating modal with invoice:', invoice);
            document.getElementById('edit_id').value = invoice.id;
            document.getElementById('edit_invoice_number').value = invoice.invoice_number;
            document.getElementById('edit_customer_name').value = invoice.customer_name;
            document.getElementById('edit_total_amount').value = invoice.total_amount;
            document.getElementById('edit_issue_date').value = invoice.issue_date;
            document.getElementById('edit_status').value = invoice.status;
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
                    const invoiceId = parseInt(this.getAttribute('data-id'));
                    const invoices = <?php echo json_encode($invoices); ?>;
                    const invoice = invoices.find(i => i.id === invoiceId);
                    if (invoice) {
                        populateEditModal(invoice);
                        const modal = new bootstrap.Modal(document.getElementById('editInvoiceModal'));
                        console.log('Showing modal');
                        modal.show();
                    } else {
                        console.error('Invoice not found for ID:', invoiceId);
                    }
                });
            });
        });

        // Confirm delete action
        document.querySelectorAll('a[data-action="delete"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this invoice?')) {
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