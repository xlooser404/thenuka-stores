<?php include 'partials/header.html'; ?>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/navbar.php'; ?>


<?php
require_once 'config/database.php'; // Database configuration

// Initialize variables
$inventory = [];
$error = null;
$lowStockCount = 0;

try {
    // Database connection
    $db = new mysqli('localhost', 'username', 'password', 'delivery_billing');
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    // Fetch inventory data with prepared statement
    $stmt = $db->prepare("SELECT 
                            i.id,
                            p.product_name,
                            p.sku,
                            l.location_name,
                            i.current_quantity,
                            i.reorder_level,
                            i.last_updated,
                            p.status
                          FROM inventory i
                          JOIN products p ON i.product_id = p.id
                          JOIN locations l ON i.location_id = l.id
                          ORDER BY l.location_name, p.product_name");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $isLowStock = $row['current_quantity'] <= $row['reorder_level'];
        if ($isLowStock) $lowStockCount++;
        
        $inventory[] = [
            'data' => [
                [
                    'type' => 'image',
                    'value' => '../assets/img/products/' . htmlspecialchars($row['sku']) . '.jpg',
                    'label' => htmlspecialchars($row['product_name']),
                    'subtext' => 'SKU: ' . htmlspecialchars($row['sku'])
                ],
                [
                    'value' => htmlspecialchars($row['location_name']),
                    'text_class' => 'text-sm'
                ],
                [
                    'value' => $row['current_quantity'],
                    'text_class' => $isLowStock ? 'font-weight-bold text-danger' : 'font-weight-bold'
                ],
                [
                    'value' => $row['reorder_level'],
                    'text_class' => 'text-sm'
                ],
                [
                    'type' => 'badge',
                    'value' => $isLowStock ? 'Reorder' : 'OK',
                    'badge_class' => $isLowStock ? 'bg-gradient-danger' : 'bg-gradient-success'
                ],
                [
                    'value' => date('M d, Y H:i', strtotime($row['last_updated'])),
                    'text_class' => 'text-xs text-secondary'
                ],
                [
                    'type' => 'badge',
                    'value' => htmlspecialchars($row['status']),
                    'badge_class' => $row['status'] === 'Active' ? 'bg-gradient-info' : 'bg-gradient-secondary'
                ]
            ],
            'row_class' => $isLowStock ? 'table-warning' : '',
            'actions' => [
                'adjust' => 'adjust_inventory.php?id=' . urlencode($row['id']),
                'transfer' => 'transfer_inventory.php?id=' . urlencode($row['id']),
                'history' => 'inventory_history.php?id=' . urlencode($row['id'])
            ]
        ];
    }
    $stmt->close();
    $db->close();
} catch (Exception $e) {
    $error = "Error loading inventory: " . $e->getMessage();
}

// Table configuration
$inventory_headers = [
    ['label' => 'Product'],
    ['label' => 'Location'],
    ['label' => 'Current Qty', 'class' => 'text-center'],
    ['label' => 'Reorder Level', 'class' => 'text-center'],
    ['label' => 'Status', 'class' => 'text-center'],
    ['label' => 'Last Updated', 'class' => 'text-center'],
    ['label' => 'Product Status', 'class' => 'text-center']
];

$inventory_actions = [
    'adjust' => '<i class="fas fa-plus-minus" data-toggle="tooltip" title="Adjust Stock"></i>',
    'transfer' => '<i class="fas fa-truck-fast" data-toggle="tooltip" title="Transfer"></i>',
    'history' => '<i class="fas fa-history" data-toggle="tooltip" title="View History"></i>'
];

$add_button_label = 'Add Inventory Item';
$form_fields = [
    'product_id' => [
        'label' => 'Product',
        'type' => 'select',
        'options_query' => 'SELECT id, CONCAT(product_name, " (", sku, ")") as name FROM products WHERE status = "Active"'
    ],
    'location_id' => [
        'label' => 'Location',
        'type' => 'select',
        'options_query' => 'SELECT id, location_name FROM locations WHERE status = "Active"'
    ],
    'initial_quantity' => [
        'label' => 'Initial Quantity',
        'type' => 'number',
        'min' => '0'
    ],
    'reorder_level' => [
        'label' => 'Reorder Level',
        'type' => 'number',
        'min' => '0',
        'placeholder' => 'Set alert threshold'
    ]
];
$form_action = 'add_inventory.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>Inventory Management - Delivery & Billing</title>
  <!-- Fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- CSS Files -->
  <link id="pagestyle" href="../assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
  <style>
    .inventory-alert {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
      display: <?php echo $lowStockCount > 0 ? 'block' : 'none'; ?>;
    }
    .low-stock-row {
      animation: pulseWarning 2s infinite;
    }
    @keyframes pulseWarning {
      0% { background-color: rgba(255,193,7,0.1); }
      50% { background-color: rgba(255,193,7,0.3); }
      100% { background-color: rgba(255,193,7,0.1); }
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
            
            <!-- Inventory Summary Cards -->
            <div class="row mb-4">
              <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                  <div class="card-body p-3">
                    <div class="row">
                      <div class="col-8">
                        <div class="numbers">
                          <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Items</p>
                          <h5 class="font-weight-bolder mb-0">
                            <?php echo count($inventory); ?>
                          </h5>
                        </div>
                      </div>
                      <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                          <i class="fas fa-boxes text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                  <div class="card-body p-3">
                    <div class="row">
                      <div class="col-8">
                        <div class="numbers">
                          <p class="text-sm mb-0 text-capitalize font-weight-bold">Low Stock</p>
                          <h5 class="font-weight-bolder mb-0">
                            <?php echo $lowStockCount; ?>
                            <?php if ($lowStockCount > 0): ?>
                              <span class="text-danger text-sm font-weight-bolder">(Needs Attention)</span>
                            <?php endif; ?>
                          </h5>
                        </div>
                      </div>
                      <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-danger shadow text-center border-radius-md">
                          <i class="fas fa-exclamation-triangle text-lg opacity-10" aria-hidden="true"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-12">
                <?php
                include 'partials/table.php';
                renderTable(
                    'Inventory Overview', 
                    $inventory_headers, 
                    $inventory, 
                    $inventory_actions, 
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

  <!-- Low Stock Alert Notification -->
  <?php if ($lowStockCount > 0): ?>
    <div class="inventory-alert">
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <span class="alert-icon"><i class="fas fa-exclamation-triangle"></i></span>
        <span class="alert-text"><strong><?php echo $lowStockCount; ?> items</strong> need reordering!</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    </div>
  <?php endif; ?>

  <!-- Core JS Files -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>
  
  <script>
    // Inventory-specific JavaScript
    $(document).ready(function() {
      // Enable tooltips for action icons
      $('[data-toggle="tooltip"]').tooltip();
      
      // Dynamic product and location loading for form
      $.get('api/get_products.php', function(products) {
        var select = $('#product_id');
        select.empty();
        $.each(products, function(key, value) {
          select.append($('<option>', {
            value: key,
            text: value
          }));
        });
      }, 'json');
      
      $.get('api/get_locations.php', function(locations) {
        var select = $('#location_id');
        select.empty();
        $.each(locations, function(key, value) {
          select.append($('<option>', {
            value: key,
            text: value
          }));
        });
      }, 'json');
      
      // Flash low stock rows
      $('.table-warning').hover(
        function() {
          $(this).addClass('low-stock-row');
        }, 
        function() {
          $(this).removeClass('low-stock-row');
        }
      );
    });
  </script>
</body>
</html>