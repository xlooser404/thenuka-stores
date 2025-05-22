<?php include 'partials/header.html'; ?>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/navbar.php'; ?>

<?php
require_once 'config/database.php'; // Assuming you have this configuration file

// Initialize variables
$users = [];
$error = null;

try {
    // Database connection
    $db = new mysqli('localhost', 'username', 'password', 'delivery_billing');
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    // Fetch users with prepared statement
    $stmt = $db->prepare("SELECT id, username, email, role, status, created_at FROM users");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'data' => [
                [
                    'type' => 'image', 
                    'value' => '../assets/img/team-2.jpg', 
                    'label' => htmlspecialchars($row['username']), 
                    'subtext' => htmlspecialchars($row['email'])
                ],
                [
                    'value' => htmlspecialchars($row['role']), 
                    'text_class' => 'text-sm'
                ],
                [
                    'type' => 'badge', 
                    'value' => htmlspecialchars($row['status']), 
                    'badge_class' => $row['status'] === 'Active' ? 'bg-gradient-success' : 'bg-gradient-secondary'
                ],
                [
                    'value' => date('M d, Y', strtotime($row['created_at'])), 
                    'text_class' => 'text-xs text-secondary'
                ]
            ],
            'actions' => [
                'edit' => 'edit_user.php?id=' . urlencode($row['id']),
                'delete' => 'delete_user.php?id=' . urlencode($row['id'])
            ]
        ];
    }
    $stmt->close();
    $db->close();
} catch (Exception $e) {
    $error = "Error loading users: " . $e->getMessage();
}

// Table configuration
$user_headers = [
    ['label' => 'User'],
    ['label' => 'Role'],
    ['label' => 'Status', 'class' => 'text-center'],
    ['label' => 'Created At', 'class' => 'text-center']
];

$user_actions = ['edit' => 'Edit', 'delete' => 'Delete'];
$add_button_label = 'Add User';
$form_fields = [
    'username' => ['label' => 'Username', 'type' => 'text'],
    'email' => ['label' => 'Email', 'type' => 'email'],
    'role' => [
        'label' => 'Role',
        'type' => 'select',
        'options' => [
            'User' => 'User',
            'Admin' => 'Admin',
            'Manager' => 'Manager'
        ]
    ],
    'status' => [
        'label' => 'Status',
        'type' => 'select',
        'options' => [
            'Active' => 'Active',
            'Inactive' => 'Inactive'
        ]
    ]
];
$form_action = 'add_user.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>Users - Delivery & Billing</title>
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
                    'Users Table', 
                    $user_headers, 
                    $users, 
                    $user_actions, 
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
</body>
</html>