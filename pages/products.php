<?php include 'partials/header.html'; ?>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/navbar.php'; ?>


<?php
require_once 'config/database.php'; // Database configuration

// Initialize variables
$products = [];
$error = null;

try {
    // Database connection
    $db = new mysqli('localhost', 'username', 'password', 'delivery_billing');
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    // Fetch products with prepared statement
    $stmt = $db->prepare("SELECT 
                            p.id, 
                            p.product_name, 
                            p.sku, 
                            c.category_name,
                            p.price, 
                            p.stock_quantity,
                            p.status,
                            p.last_restocked
                          FROM products p
                          JOIN categories c ON p.category_id = c.id");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'data' => [
                [
                    'type' => 'image',
                    'value' => '../assets/img/products/' . htmlspecialchars($row['sku']) . '.jpg',
                    'label' => htmlspecialchars($row['product_name']),
                    'subtext' => 'SKU: ' . htmlspecialchars($row['sku'])
                ],
                [
                    'value' => htmlspecialchars($row['category_name']),
                    'text_class' => 'text-sm'
                ],
                [
                    'value' => '$' . number_format($row['price'], 2),
                    'text_class' => 'font-weight-bold'
                ],
                [
                    'type' => 'progress',
                    'value' => min(100, ($row['stock_quantity'] / 50) * 100), // Assuming 50 is max stock
                    'progress_class' => $row['stock_quantity'] > 10 ? 'bg-gradient-success' : 'bg-gradient-danger',
                    'text_class' => 'text-center'
                ],
                [
                    'type' => 'badge',
                    'value' => htmlspecialchars($row['status']),
                    'badge_class' => $row['status'] === 'Active' ? 'bg-gradient-success' : 'bg-gradient-secondary'
                ],
                [
                    'value' => $row['last_restocked'] ? date('M d, Y', strtotime($row['last_restocked'])) : 'Never',
                    'text_class' => 'text-xs text-secondary'
                ]
            ],
            'actions' => [
                'view' => 'product_details.php?id=' . urlencode($row['id']),
                'edit' => 'edit_product.php?id=' . urlencode($row['id']),
                'restock' => 'restock_product.php?id=' . urlencode($row['id'])
            ]
        ];
    }
    $stmt->close();
    $db->close();
} catch (Exception $e) {
    $error = "Error loading products: " . $e->getMessage();
}

// Table configuration
$product_headers = [
    ['label' => 'Product'],
    ['label' => 'Category'],
    ['label' => 'Price', 'class' => 'text-center'],
    ['label' => 'Stock Level', 'class' => 'text-center'],
    ['label' => 'Status', 'class' => 'text-center'],
    ['label' => 'Last Restock', 'class' => 'text-center']
];

$product_actions = [
    'view' => '<i class="fas fa-eye" data-toggle="tooltip" title="View"></i>',
    'edit' => '<i class="fas fa-edit" data-toggle="tooltip" title="Edit"></i>',
    'restock' => '<i class="fas fa-boxes" data-toggle="tooltip" title="Restock"></i>'
];

$add_button_label = 'Add Product';
$form_fields = [
    'product_name' => [
        'label' => 'Product Name',
        'type' => 'text',
        'placeholder' => 'Enter product name'
    ],
    'sku' => [
        'label' => 'SKU',
        'type' => 'text',
        'placeholder' => 'PROD-001'
    ],
    'category_id' => [
        'label' => 'Category',
        'type' => 'select',
        'options_query' => 'SELECT id, category_name FROM categories'
    ],
    'price' => [
        'label' => 'Price',
        'type' => 'number',
        'step' => '0.01',
        'min' => '0'
    ],
    'initial_stock' => [
        'label' => 'Initial Stock',
        'type' => 'number',
        'min' => '0'
    ],
    'status' => [
        'label' => 'Status',
        'type' => 'select',
        'options' => [
            'Active' => 'Active',
            'Inactive' => 'Inactive',
            'Discontinued' => 'Discontinued'
        ]
    ]
];
$form_action = 'add_product.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>Products Management - Delivery & Billing</title>
  <!-- Fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- CSS Files -->
  <link id="pagestyle" href="../assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
  <!-- Additional CSS for products -->
  <style>
    .product-image {
        max-width: 50px;
        max-height: 50px;
        object-fit: cover;
    }
  </style>
</head>
<body class="g-sidenav-show bg-gray-100">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-2">
        <?php include 'partials/sidebar.php'; ?>
      </div>
      <div class="col-md-10">
        <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
          <!-- Navbar -->
          <?php include 'partials/navbar.php'; ?>
          <!-- End Navbar -->
          <div class="container-fluid py-4">
            <?php if ($error): ?>
              <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
              </div>
            <?php endif; ?>
            
            <div class="row">
              <div class="col-12">
                <?php
                include 'partials/table.php';
                renderTable(
                    'Products Inventory', 
                    $product_headers, 
                    $products, 
                    $product_actions, 
                    $add_button_label, 
                    $form_fields, 
                    $form_action
                );
                ?>
              </div>
            </div>
          </div>
        </main>
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
    // Product-specific JavaScript
    $(document).ready(function() {
      // Enable tooltips for action icons
      $('[data-toggle="tooltip"]').tooltip();
      
      // Dynamic category loading for form
      <?php if (isset($form_fields['category_id']['options_query'])): ?>
        $.get('api/get_categories.php', function(data) {
          var select = $('#category_id');
          select.empty();
          $.each(data, function(key, value) {
            select.append($('<option>', {
              value: key,
              text: value
            }));
          });
        }, 'json');
      <?php endif; ?>
    });
  </script>
</body>
</html>