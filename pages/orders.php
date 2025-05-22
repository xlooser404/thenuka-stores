
<?php
// Sample data for Orders (replace with database query)
$orders = [
    [
        'data' => [
            ['value' => 'ORD001', 'text_class' => 'text-sm'],
            ['type' => 'image', 'value' => '../assets/img/team-2.jpg', 'label' => 'John Doe', 'subtext' => 'john@example.com'],
            ['type' => 'badge', 'value' => 'Pending', 'badge_class' => 'bg-gradient-warning'],
            ['value' => '2025-05-20', 'text_class' => 'text-xs text-secondary']
        ],
        'actions' => ['edit' => 'edit_order.php?id=1', 'delete' => 'delete_order.php?id=1']
    ],
    [
        'data' => [
            ['value' => 'ORD002', 'text_class' => 'text-sm'],
            ['type' => 'image', 'value' => '../assets/img/team-3.jpg', 'label' => 'Jane Smith', 'subtext' => 'jane@example.com'],
            ['type' => 'badge', 'value' => 'Delivered', 'badge_class' => 'bg-gradient-success'],
            ['value' => '2025-05-21', 'text_class' => 'text-xs text-secondary']
        ],
        'actions' => ['edit' => 'edit_order.php?id=2', 'delete' => 'delete_order.php?id=2']
    ]
];

// Table configuration
$order_headers = [
    ['label' => 'Order ID'],
    ['label' => 'Customer'],
    ['label' => 'Status', 'class' => 'text-center'],
    ['label' => 'Date', 'class' => 'text-center']
];
$order_actions = ['edit' => 'Edit', 'delete' => 'Delete'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>Orders - Delivery & Billing</title>
  <!-- Fonts and icons -->
  <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
  <!-- Nucleo Icons -->
  <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- CSS Files -->
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
          <!-- Navbar -->
          <?php include 'partials/navbar.php'; ?>
          <!-- End Navbar -->
          <div class="container-fluid py-4">
            <div class="row">
              <div class="col-12">
                <?php
                include 'partials/table.php';
                renderTable('Orders Table', $order_headers, $orders, $order_actions);
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
</body>
</html>
