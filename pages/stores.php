<?php
// Start session and include controller at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../backend/controllers/StoreController.php';

// Initialize variables
$stores = [];
$error = null;

try {
    $controller = new StoreController();
    $result = $controller->getAllStores();

    foreach ($result as $row) {
        $stores[] = [
            'data' => [
                [
                    'type' => 'image',
                    'value' => '../assets/img/small-logos/logo-atlassian.svg',
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
                'view' => 'view_store.php?id=' . urlencode($row['id']),
                'edit' => '#',
                'delete' => '#'
            ],
            'id' => $row['id'],
            'store_name' => htmlspecialchars($row['store_name']),
            'location' => htmlspecialchars($row['location']),
            'manager_name' => htmlspecialchars($row['manager_name']),
            'contact_email' => htmlspecialchars($row['contact_email']),
            'status' => htmlspecialchars($row['status']),
            'last_audit_date' => $row['last_audit_date'] ? date('Y-m-d', strtotime($row['last_audit_date'])) : ''
        ];
    }
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
    'view' => '<i class="fas fa-eye"></i> View',
    'edit' => '<i class="fas fa-edit"></i> Edit',
    'delete' => '<i class="fas fa-trash"></i> Delete'
];
$add_button_label = 'Add New Store';

// Now include partials that might output content
include_once 'partials/header.html';
include_once 'partials/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <title>Stores Management - Delivery & Billing</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link id="pagestyle" href="../assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
    <style>
        .navbar-custom {
            background-color: #f8f9fa;
            padding: 0.5rem 1rem;
        }
        .navbar-custom .form-control {
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            max-width: 200px;
        }
        .btn-orange {
            background-color: #f5a623;
            border-color: #f5a623;
            color: white;
            padding: 0.375rem 1rem;
        }
        .btn-orange:hover {
            background-color: #e69520;
            border-color: #e69520;
        }
        .modal-lg-custom {
            max-width: 600px;
        }
        .modal-body-custom {
            max-height: 500px;
            overflow-y: auto;
        }

        /* Updated styles for better fit and more left margin */
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }
        .main-content {
            padding: 1rem;
            overflow-x: auto; /* Ensure horizontal scrolling if content overflows */
             margin: -1%;
        }
        .g-sidenav-show .col-md-4 {
            padding-left: 2rem; /* Increase left padding for the sidebar */
            padding-right: 0;
        }
        .g-sidenav-show .col-md-8 {
            padding-left: 1.5rem; /* Add some padding to the left of the main content */
        }
        @media (max-width: 767.98px) {
            .g-sidenav-show .col-md-4,
            .g-sidenav-show .col-md-8 {
                padding-left: 15px;
                padding-right: 15px;
            }
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-100">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar width -->
            <div class="col-md-4">
                <?php include 'partials/sidebar.php'; ?>
            </div>
            <!-- Fix main content width -->
            <div class="col-md-16">
                <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
                    <?php include 'partials/navbar.php'; ?>
                    <div class="container-fluid py-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-12">
                                <?php include 'partials/table.php'; ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Stores Management</h5>
                                    <button type="button" class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#addStoreModal">
                                        <?php echo $add_button_label; ?>
                                    </button>
                                </div>
                                <?php
                                renderTable(
                                    'Stores Management',
                                    $store_headers,
                                    $stores,
                                    $store_actions
                                );
                                ?>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Add Store Modal -->
    <div class="modal fade" id="addStoreModal" tabindex="-1" aria-labelledby="addStoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStoreModalLabel">Add New Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-custom">
                    <form id="addStoreForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="add_store_name" class="form-label">Store Name</label>
                            <input type="text" class="form-control" id="add_store_name" name="store_name" placeholder="Enter store name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="add_location" name="location" placeholder="City, State" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_manager_name" class="form-label">Manager Name</label>
                            <input type="text" class="form-control" id="add_manager_name" name="manager_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_contact_email" class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="add_contact_email" name="contact_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_status" class="form-label">Status</label>
                            <select class="form-select" id="add_status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Under Renovation">Under Renovation</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="add_last_audit_date" class="form-label">Last Audit Date</label>
                            <input type="date" class="form-control" id="add_last_audit_date" name="last_audit_date">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Store</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Store Modal -->
    <div class="modal fade" id="editStoreModal" tabindex="-1" aria-labelledby="editStoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStoreModalLabel">Edit Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-custom">
                    <form id="editStoreForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" id="edit_id" name="id">
                        <input type="hidden" name="action" value="update">
                        <div class="mb-3">
                            <label for="edit_store_name" class="form-label">Store Name</label>
                            <input type="text" class="form-control" id="edit_store_name" name="store_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="edit_location" name="location" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_manager_name" class="form-label">Manager Name</label>
                            <input type="text" class="form-control" id="edit_manager_name" name="manager_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_contact_email" class="form-label">Contact Email</label>
                            <input type="email" class="form-control" id="edit_contact_email" name="contact_email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Under Renovation">Under Renovation</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_last_audit_date" class="form-label">Last Audit Date</label>
                            <input type="date" class="form-control" id="edit_last_audit_date" name="last_audit_date">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteStoreModal" tabindex="-1" aria-labelledby="deleteStoreModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteStoreModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this store?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();

            // Edit action
            $('.action-edit').on('click', function() {
                const store = $(this).data('store');
                $('#edit_id').val(store.id);
                $('#edit_store_name').val(store.store_name);
                $('#edit_location').val(store.location);
                $('#edit_manager_name').val(store.manager_name);
                $('#edit_contact_email').val(store.contact_email);
                $('#edit_status').val(store.status);
                $('#edit_last_audit_date').val(store.last_audit_date);
                $('#editStoreModal').modal('show');
            });

            // Delete action
            let deleteId = null;
            $('.action-delete').on('click', function() {
                deleteId = $(this).data('store').id;
                $('#deleteStoreModal').modal('show');
            });

            $('#confirmDelete').on('click', function() {
                $.ajax({
                    url: '../backend/controllers/StoreController.php?action=delete&id=' + deleteId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('Delete Response:', response);
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting store: ' + (response.error || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Delete Error:', error, xhr.responseText);
                        alert('Error deleting store: ' + (xhr.responseJSON?.error || 'Unknown error'));
                    }
                });
                $('#deleteStoreModal').modal('hide');
            });

            $('#addStoreForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Submitting Add Form:', $(this).serialize());
                $.ajax({
                    url: '../backend/controllers/StoreController.php?action=add',
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {
                        console.log('Add Response:', response);
                        if (response.success) {
                            $('#addStoreModal').modal('hide');
                            location.reload();
                        } else {
                            alert('Error adding store: ' + (response.error || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Add Error:', error, xhr.responseText);
                        alert('Error adding store: ' + (xhr.responseJSON?.error || 'Unknown error'));
                    }
                });
            });

            $('#editStoreForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Submitting Edit Form:', $(this).serialize());
                $.ajax({
                    url: '../backend/controllers/StoreController.php?action=update',
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {
                        console.log('Edit Response:', response);
                        if (response.success) {
                            $('#editStoreModal').modal('hide');
                            location.reload();
                        } else {
                            alert('Error updating store: ' + (response.error || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Edit Error:', error, xhr.responseText);
                        alert('Error updating store: ' + (xhr.responseJSON?.error || 'Unknown error'));
                    }
                });
            });
        });
    </script>
</body>
</html>