<?php
session_start();
error_log("products.php - Session ID: " . session_id());
error_log("products.php - Session data: " . print_r($_SESSION, true));
error_log("products.php - CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

// Restrict access to admin role
if (!isset($_SESSION['user'])) {
  error_log("products.php - No user session.");
  $_SESSION['error'] = 'Unauthorized access: No user session.';
  header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/products.php'));
  exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
  error_log("products.php - User role: " . $_SESSION['user']['role']);
  $_SESSION['error'] = 'Unauthorized access: Not an admin.';
  header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/products.php'));
  exit;
}

// Generate CSRF token once per session
if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  error_log("products.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

// Log form hidden fields
error_log("products.php - Form hidden fields: " . print_r(['csrf_token' => $_SESSION['csrf_token']], true));

// Include ProductController
require_once __DIR__ . '/../backend/controllers/ProductController.php';
$controller = new ProductController();
$products = $controller->getAllProducts();

// Prepare data for table.php
$title = "Products Table";
$headers = [
  ['label' => 'ID', 'class' => ''],
  ['label' => 'Name', 'class' => ''],
  ['label' => 'Description', 'class' => ''],
  ['label' => 'Price', 'class' => ''],
  ['label' => 'Stock', 'class' => ''],
  ['label' => 'Category', 'class' => ''],
  ['label' => 'Actions', 'class' => 'text-center']
];
$rows = [];
foreach ($products as $product) {
  $rows[] = [
    'data' => [
      ['value' => htmlspecialchars($product['id']), 'text_class' => 'text-sm'],
      ['value' => htmlspecialchars($product['name']), 'text_class' => 'text-sm'],
      ['value' => htmlspecialchars($product['description'] ?? 'N/A'), 'text_class' => 'text-sm'],
      ['value' => htmlspecialchars(number_format($product['price'], 2)), 'text_class' => 'text-sm'],
      ['value' => htmlspecialchars($product['stock'] ?? 0), 'text_class' => 'text-sm'],
      ['value' => htmlspecialchars($product['category'] ?? 'N/A'), 'text_class' => 'text-sm']
    ],
    'actions' => [
      'edit' => "javascript:void(0);",
      'delete' => "/thenuka-stores/backend/controllers/ProductController.php?action=delete&id=" . $product['id']
    ]
  ];
}
$actions = [
  'edit' => 'Edit',
  'delete' => 'Delete'
];
$add_button_label = "Add Product";
$form_fields = [
  'name' => ['label' => 'Name', 'type' => 'text'],
  'description' => ['label' => 'Description', 'type' => 'text'],
  'price' => ['label' => 'Price', 'type' => 'number', 'step' => '0.01'],
  'stock' => ['label' => 'Stock', 'type' => 'number'],
  'category' => ['label' => 'Category', 'type' => 'text']
];
$form_action = "/thenuka-stores/backend/controllers/ProductController.php?action=add";
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
  <title>Products - Delivery & Billing</title>
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
                    <?php echo htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']); ?>
                  </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                  <div class="alert alert-danger text-white">
                    <?php echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']); ?>
                  </div>
                <?php endif; ?>
                <!-- Product Table -->
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

  <!-- Edit Product Modal -->
  <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="/thenuka-stores/backend/controllers/ProductController.php?action=update" method="POST">
          <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="id" id="edit_id">
            <div class="mb-3">
              <label for="edit_name" class="form-label">Name</label>
              <input type="text" class="form-control" id="edit_name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="edit_description" class="form-label">Description</label>
              <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
            </div>
            <div class="mb-3">
              <label for="edit_price" class="form-label">Price</label>
              <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
            </div>
            <div class="mb-3">
              <label for="edit_stock" class="form-label">Stock</label>
              <input type="number" class="form-control" id="edit_stock" name="stock" required>
            </div>
            <div class="mb-3">
              <label for="edit_category" class="form-label">Category</label>
              <input type="text" class="form-control" id="edit_category" name="category">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Update Product</button>
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
    // Populate edit modal with product data
    function populateEditModal(product) {
      document.getElementById('edit_id').value = product.id;
      document.getElementById('edit_name').value = product.name;
      document.getElementById('edit_description').value = product.description || '';
      document.getElementById('edit_price').value = product.price;
      document.getElementById('edit_stock').value = product.stock;
      document.getElementById('edit_category').value = product.category || '';
    }

    // Initialize edit modal on click
    document.querySelectorAll('a[data-action="edit"]').forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const productId = parseInt(this.getAttribute('data-id'));
        const products = <?php echo json_encode($products); ?>;
        const product = products.find(p => p.id === productId);
        if (product) {
          populateEditModal(product);
          const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
          modal.show();
        } else {
          console.error('Product not found for ID:', productId);
        }
      });
    });

    // Confirm delete action
    document.querySelectorAll('a[data-action="delete"]').forEach(link => {
      link.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this product?')) {
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
      var options = {
        damping: '0.5'
      };
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
</body>

</html>