<?php
session_start();
error_log("agents.php - Session ID: " . session_id());
error_log("agents.php - Session data: " . print_r($_SESSION, true));
error_log("agents.php - CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

// Restrict access to admin role
if (!isset($_SESSION['user'])) {
    error_log("agents.php - No user session.");
    $_SESSION['error'] = 'Unauthorized access: No user session.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/agents.php'));
    exit;
}
if ($_SESSION['user']['role'] !== 'admin') {
    error_log("agents.php - User role: " . $_SESSION['user']['role']);
    $_SESSION['error'] = 'Unauthorized access: Not an admin.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/agents.php'));
    exit;
}

// Generate CSRF token once per session
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("agents.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

// Log form hidden fields
error_log("agents.php - Form hidden fields: " . print_r(['csrf_token' => $_SESSION['csrf_token']], true));

// Include AgentController
require_once __DIR__ . '/../backend/controllers/AgentController.php';
$controller = new AgentController();
$agents = $controller->getAllAgents();

// Prepare data for table.php
$title = "Agents Table";
$headers = [
    ['label' => 'ID', 'class' => ''],
    ['label' => 'First Name', 'class' => ''],
    ['label' => 'Last Name', 'class' => ''],
    ['label' => 'Email', 'class' => ''],
    ['label' => 'Phone', 'class' => ''],
    ['label' => 'Region', 'class' => ''],
    ['label' => 'Actions', 'class' => 'text-center']
];
$rows = [];
foreach ($agents as $agent) {
    $rows[] = [
        'data' => [
            ['value' => htmlspecialchars($agent['id']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($agent['first_name']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($agent['last_name']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($agent['email']), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($agent['phone'] ?? 'N/A'), 'text_class' => 'text-sm'],
            ['value' => htmlspecialchars($agent['region'] ?? 'N/A'), 'text_class' => 'text-sm']
        ],
        'actions' => [
            'edit' => "javascript:void(0);",
            'delete' => "/thenuka-stores/backend/controllers/AgentController.php?action=delete&id=" . $agent['id']
        ]
    ];
}
$actions = [
    'edit' => 'Edit',
    'delete' => 'Delete'
];
$add_button_label = "Add Agent";
$form_fields = [
    'first_name' => ['label' => 'First Name', 'type' => 'text', 'required' => true],
    'last_name' => ['label' => 'Last Name', 'type' => 'text', 'required' => true],
    'email' => ['label' => 'Email', 'type' => 'email', 'required' => true],
    'phone' => ['label' => 'Phone', 'type' => 'text'],
    'region' => ['label' => 'Region', 'type' => 'text']
];
$form_action = "/thenuka-stores/backend/controllers/AgentController.php?action=add";
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
    <title>Agents - Delivery & Billing</title>
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
                                <!-- Agents Table -->
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

    <!-- Edit Agent Modal -->
    <div class="modal fade" id="editAgentModal" tabindex="-1" aria-labelledby="editAgentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAgentModalLabel">Edit Agent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/thenuka-stores/backend/controllers/AgentController.php?action=update" method="POST">
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
                            <label for="edit_region" class="form-label">Region</label>
                            <input type="text" class="form-control" id="edit_region" name="region">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Agent</button>
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
        // Populate edit modal with agent data
        function populateEditModal(agent) {
            document.getElementById('edit_id').value = agent.id;
            document.getElementById('edit_first_name').value = agent.first_name;
            document.getElementById('edit_last_name').value = agent.last_name;
            document.getElementById('edit_email').value = agent.email;
            document.getElementById('edit_phone').value = agent.phone || '';
            document.getElementById('edit_region').value = agent.region || '';
        }

        // Initialize edit modal on click
        document.querySelectorAll('a[data-action="edit"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const agentId = parseInt(this.getAttribute('data-id'));
                const agents = <?php echo json_encode($agents); ?>;
                const agent = agents.find(a => a.id === agentId);
                if (agent) {
                    populateEditModal(agent);
                    const modal = new bootstrap.Modal(document.getElementById('editAgentModal'));
                    modal.show();
                } else {
                    console.error('Agent not found for ID:', agentId);
                }
            });
        });

        // Confirm delete action
        document.querySelectorAll('a[data-action="delete"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this agent?')) {
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