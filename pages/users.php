<?php
session_start();
error_log("users.php - Session ID: " . session_id());
error_log("users.php - Session data: " . print_r($_SESSION, true));
error_log("users.php - CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

// Restrict access to admin role
if (!isset($_SESSION['user'])) {
    error_log("users.php - No user session.");
    $_SESSION['error'] = 'Unauthorized access: No user session.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/users.php'));
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    error_log("users.php - User role: " . $_SESSION['user']['role']);
    $_SESSION['error'] = 'Unauthorized access: Not an admin.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/users.php'));
    exit;
}

// Generate CSRF token once per session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("users.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

// Log form hidden fields
$csrfToken = $_SESSION['csrf_token'];
error_log("users.php - Form hidden fields: " . print_r(['csrf_token' => $csrfToken], true));

// Include UserController
require_once __DIR__ . '/../backend/controllers/UserController.php';
$controller = new UserController();
$users = $controller->getAllUsers();

// Prepare data for table.php
$title = "Users Table";
$headers = [
    ['label' => 'ID', 'class' => ''],
    ['label' => 'Username', 'class' => ''],
    ['label' => 'Email', 'class' => ''],
    ['label' => 'Role', 'class' => ''],
    ['label' => 'Created At', 'class' => ''],
    ['label' => 'Actions', 'class' => 'text-center']
];
$rows = [];
foreach ($users as $user) {
    $rows[] = [
        'data' => [
            ['value' => htmlspecialchars($user['id']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($user['username']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($user['email']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($user['role']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($user['created_at']), 'text_class' => 'text-sm']
        ],
        'actions' => [
            'edit' => "javascript:void(0);",
            'delete' => "/thenuka-stores/backend/controllers/UserController.php?action=delete&id=" . $user['id']
        ]
    ];
}
$actions = [
    'edit' => 'Edit',
    'delete' => 'Delete'
];
$add_button_label = "Add User";
$form_fields = [
    'username' => ['label' => 'Username', 'type' => 'text', 'required' => true],
    'email' => ['label' => 'Email', 'type' => 'email', 'required' => true],
    'password' => ['label' => 'Password', 'type' => 'password', 'required' => true],
    'role' => ['label' => 'Role', 'type' => 'select', 'options' => [
        'admin' => 'Admin',
        'agent' => 'Agent',
        'user' => 'User'
    ], 'required' => true]
];
$form_action = "/thenuka-stores/backend/controllers/UserController.php?action=add";
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
    <title>Users - Delivery & Billing</title>
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
                                <!-- Users Table -->
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/thenuka-stores/backend/controllers/UserController.php?action=update" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Password (leave blank to keep unchanged)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="agent">Agent</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
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
        // Populate edit modal with user data
        function populateEditModal(user) {
            console.log('Populating modal with user:', user);
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_password').value = ''; // Password is not pre-filled for security
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
                    const userId = parseInt(this.getAttribute('data-id'));
                    const users = <?php echo json_encode($users); ?>;
                    const user = users.find(u => u.id === userId);
                    if (user) {
                        populateEditModal(user);
                        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                        console.log('Showing modal');
                        modal.show();
                    } else {
                        console.error('User not found for ID:', userId);
                    }
                });
            });
        });

        // Confirm delete action
        document.querySelectorAll('a[data-action="delete"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this user?')) {
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