<?php
// Sample data for Customers (replace with database query)
$db = new mysqli('localhost', 'username', 'password', 'delivery_billing');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$result = $db->query("SELECT name, email, type, status, joined FROM customers");
$customers = [];
while ($row = $result->fetch_assoc()) {
    $customers[] = [
        'data' => [
            ['type' => 'image', 'value' => '../assets/img/team-2.jpg', 'label' => $row['name'], 'subtext' => $row['email']],
            ['value' => $row['type'], 'text_class' => 'text-sm'],
            ['type' => 'badge', 'value' => $row['status'], 'badge_class' => $row['status'] === 'Active' ? 'bg-gradient-success' : 'bg-gradient-secondary'],
            ['value' => $row['joined'], 'text_class' => 'text-xs text-secondary']
        ],
        'actions' => ['edit' => 'edit_customer.php?id=' . $row['name'], 'delete' => 'delete_customer.php?id=' . $row['name']]
    ];
}
$db->close();

// Table configuration
$customer_headers = [
    ['label' => 'Customer'],
    ['label' => 'Type'],
    ['label' => 'Status', 'class' => 'text-center'],
    ['label' => 'Joined', 'class' => 'text-center']
];
$customer_actions = ['edit' => 'Edit', 'delete' => 'Delete'];
$add_button_label = 'Add Customer';
$form_fields = [
    'name' => ['label' => 'Name', 'type' => 'text'],
    'email' => ['label' => 'Email', 'type' => 'email'],
    'type' => [
        'label' => 'Type',
        'type' => 'select',
        'options' => [
            'Customer' => 'Customer',
            'VIP Customer' => 'VIP Customer'
        ]
    ]
];
$form_action = 'add_customer.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>Customers - Delivery & Billing</title>
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
                renderTable('Customers Table', $customer_headers, $customers, $customer_actions, $add_button_label, $form_fields, $form_action);
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