<?php
session_start();
error_log("customers.php - Session ID: " . session_id());
error_log("customers.php - Session data: " . print_r($_SESSION, true));
error_log("customers.php - CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

// Restrict access to admin role
if (!isset($_SESSION['user'])) {
    error_log("customers.php - No user session.");
    $_SESSION['error'] = 'Unauthorized access: No user session.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/customers.php'));
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    error_log("customers.php - User role: " . $_SESSION['user']['role']);
    $_SESSION['error'] = 'Unauthorized access: Not an admin.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/customers.php'));
    exit;
}

// Generate CSRF token once per session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("customers.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

// Log form hidden fields
error_log("customers.php - Form hidden fields: " . print_r(['csrf_token' => $_SESSION['csrf_token']], true));

// Include CustomerController
require_once __DIR__ . '/../backend/controllers/CustomerController.php';
$controller = new CustomerController();
$customers = $controller->getAllCustomers();

// Prepare data for table.php
$title = "Customers Table";
$headers = [
    ['label' => 'ID', 'class' => ''],
    ['label' => 'First Name', 'class' => ''],
    ['label' => 'Last Name', 'class' => ''],
    ['label' => 'Email', 'class' => ''],
    ['label' => 'Phone', 'class' => ''],
    ['label' => 'Address', 'class' => ''],
    ['label' => 'Actions', 'class' => 'text-center']
];
$rows = [];
foreach ($customers as $customer) {
    $rows[] = [
        'data' => [
            ['value' => htmlspecialchars($customer['id']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($customer['first_name']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($customer['last_name']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($customer['email']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($customer['phone'] ?? 'N/A'), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($customer['address'] ?? 'N/A'), 'text_class' => 'text-sm']
        ],
        'actions' => [
            'edit' => "javascript:void(0);",
            'delete' => "/thenuka-stores/backend/controllers/CustomerController.php?action=delete&id=" . $customer['id']
        ]
    ];
}
$actions = [
    'edit' => 'Edit',
    'delete' => 'Delete'
];
$add_button_label = "Add Customer";
$form_fields = [
    'first_name' => ['label' => 'First Name', 'type' => 'text'],
    'last_name' => ['label' => 'Last Name', 'type' => 'text'],
    'email' => ['label' => 'Email', 'type' => 'email'],
    'phone' => ['label' => 'Phone', 'type' => 'text'],
    'address' => ['label' => 'Address', 'type' => 'text']
];
$form_action = "/thenuka-stores/backend/controllers/CustomerController.php?action=add";
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
  <title>Customers - Delivery & Billing</title>
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
                <!-- Fallback Add Customer Form (Temporary for Testing) -->
                <div class="card mb-4">
                  <div class="card-header">
                    <h5>Test Add Customer</h5>
                  </div>
                  <div class="card-body">
                    <form action="/thenuka-stores/backend/controllers/CustomerController.php?action=add" method="POST">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                      <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                      </div>
                      <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                      </div>
                      <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                      </div>
                      <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone">
                      </div>
                      <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                      </div>
                      <button type="submit" class="btn btn-primary">Add Customer</button>
                    </form>
                  </div>
                </div>
                <!-- Customer Table -->
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

  <!-- Edit Customer Modal -->
  <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="/thenuka-stores/backend/controllers/CustomerController.php?action=update" method="POST">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-3">
              <label for="edit_first_name" class="form-label">First Name</label>
              <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
            </div>
            <div class="mb-3">
              <label for="edit_last_name" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
            </div>
            <div class="mb-3">
              <label for="edit_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="edit_email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="edit_phone" class="form-label">Phone</label>
              <input type="text" class="form-control" id="edit_phone" name="phone">
            </div>
            <div class="mb-3">
              <label for="edit_address" class="form-label">Address</label>
              <textarea class="form-control" id="edit_address" name="address" rows="3"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Update Customer</button>
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
    // Populate edit modal with customer data
    function populateEditModal(customer) {
      document.getElementById('edit_id').value = customer.id;
      document.getElementById('edit_first_name').value = customer.first_name;
      document.getElementById('edit_last_name').value = customer.last_name;
      document.getElementById('edit_email').value = customer.email;
      document.getElementById('edit_phone').value = customer.phone || '';
      document.getElementById('edit_address').value = customer.address || '';
    }

    // Initialize edit modal on click
    document.querySelectorAll('a[data-original-title="Edit"]').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const customer = <?php echo json_encode($customers); ?>.find(c => c.id === parseInt(this.getAttribute('data-id')));
        if (customer) {
          populateEditModal(customer);
          const modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
          modal.show();
        }
      });
    });

    // Add data-id to edit links
    document.querySelectorAll('a[data-original-title="Edit"]').forEach(link => {
      const row = link.closest('tr');
      const idCell = row.querySelector('td:first-child span').textContent;
      link.setAttribute('data-id', idCell);
    });

    // Confirm delete action
    document.querySelectorAll('a[data-original-title="Delete"]').forEach(link => {
      link.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this customer?')) {
          e.preventDefault();
        }
      });
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