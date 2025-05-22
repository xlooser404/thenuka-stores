<?php include 'partials/header.html'; ?>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/navbar.php'; ?>


<?php
require_once 'config/database.php'; // Database configuration

// Initialize variables
$stores = [];
$error = null;

try {
    // Database connection
    $db = new mysqli('localhost', 'username', 'password', 'delivery_billing');
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    // Fetch stores with prepared statement
    $stmt = $db->prepare("SELECT 
                            id, 
                            store_name, 
                            location, 
                            manager_name,
                            contact_email, 
                            status, 
                            last_audit_date 
                          FROM stores");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $stores[] = [
            'data' => [
                [
                    'type' => 'image',
                    'value' => '../assets/img/small-logos/logo-store.svg',
                    'label' => htmlspecialchars($row['store_name']),
                    'subtext' => htmlspecialchars($row['location'])
                ],
                [
                    'value' => htmlspecialchars($row['manager_name']),
                    'text_class' => 'text-sm'
                ],
                [
                    'value' => htmlspecialchars($row['contact_email']),
                    'text_class' => 'text-xs text-secondary'
                ],
                [
                    'type' => 'badge',
                    'value' => htmlspecialchars($row['status']),
                    'badge_class' => $row['status'] === 'Active' ? 'bg-gradient-success' : 'bg-gradient-secondary'
                ],
                [
                    'value' => $row['last_audit_date'] ? date('M d, Y', strtotime($row['last_audit_date'])) : 'N/A',
                    'text_class' => 'text-xs'
                ]
            ],
            'actions' => [
                'edit' => 'edit_store.php?id=' . urlencode($row['id']),
                'delete' => 'delete_store.php?id=' . urlencode($row['id']),
                'view' => 'view_store.php?id=' . urlencode($row['id'])
            ]
        ];
    }
    $stmt->close();
    $db->close();
} catch (Exception $e) {
    $error = "Error loading stores: " . $e->getMessage();
}

// Table configuration
$store_headers = [
    ['label' => 'Store'],
    ['label' => 'Manager'],
    ['label' => 'Contact'],
    ['label' => 'Status', 'class' => 'text-center'],
    ['label' => 'Last Audit', 'class' => 'text-center']
];

$store_actions = [
    'view' => '<i class="fas fa-eye"></i>',
    'edit' => '<i class="fas fa-edit"></i>',
    'delete' => '<i class="fas fa-trash"></i>'
];
$add_button_label = 'Add New Store';
$form_fields = [
    'store_name' => [
        'label' => 'Store Name',
        'type' => 'text',
        'placeholder' => 'Enter store name'
    ],
    'location' => [
        'label' => 'Location',
        'type' => 'text',
        'placeholder' => 'City, State'
    ],
    'manager_name' => [
        'label' => 'Manager Name',
        'type' => 'text'
    ],
    'contact_email' => [
        'label' => 'Contact Email',
        'type' => 'email'
    ],
    'status' => [
        'label' => 'Status',
        'type' => 'select',
        'options' => [
            'Active' => 'Active',
            'Inactive' => 'Inactive',
            'Under Renovation' => 'Under Renovation'
        ]
    ],
    'last_audit_date' => [
        'label' => 'Last Audit Date',
        'type' => 'date'
    ]
];
$form_action = 'add_store.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>Stores Management - Delivery & Billing</title>
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
                    'Stores Management', 
                    $store_headers, 
                    $stores, 
                    $store_actions, 
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
    // Additional store-specific JavaScript can go here
    $(document).ready(function() {
      // Enable tooltips for action icons
      $('[data-toggle="tooltip"]').tooltip();
    });
  </script>
</body>
</html>