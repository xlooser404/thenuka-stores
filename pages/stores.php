<?php
include_once 'partials/header.html';
include_once 'partials/sidebar.php';
include_once 'partials/navbar.php';

require_once __DIR__ . '/../backend/config/database.php';

// Handle CRUD requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'error' => ''];
    try {
        $database = new Database();
        $db = $database->connect();

        if ($db === false) {
            throw new Exception("Database connection failed");
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'add_store') {
            $store_name = $_POST['store_name'] ?? '';
            $location = $_POST['location'] ?? '';
            $manager_name = $_POST['manager_name'] ?? '';
            $contact_email = $_POST['contact_email'] ?? '';
            $status = $_POST['status'] ?? '';
            $last_audit_date = $_POST['last_audit_date'] ?: null;

            $stmt = $db->prepare("INSERT INTO stores (store_name, location, manager_name, contact_email, status, last_audit_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$store_name, $location, $manager_name, $contact_email, $status, $last_audit_date]);
            $response['success'] = true;
        } elseif ($action === 'edit_store') {
            $id = $_POST['id'] ?? '';
            $store_name = $_POST['store_name'] ?? '';
            $location = $_POST['location'] ?? '';
            $manager_name = $_POST['manager_name'] ?? '';
            $contact_email = $_POST['contact_email'] ?? '';
            $status = $_POST['status'] ?? '';
            $last_audit_date = $_POST['last_audit_date'] ?: null;

            $stmt = $db->prepare("UPDATE stores SET store_name = ?, location = ?, manager_name = ?, contact_email = ?, status = ?, last_audit_date = ? WHERE id = ?");
            $stmt->execute([$store_name, $location, $manager_name, $contact_email, $status, $last_audit_date, $id]);
            $response['success'] = true;
        } elseif ($action === 'delete_store') {
            $id = $_POST['id'] ?? '';
            $stmt = $db->prepare("DELETE FROM stores WHERE id = ?");
            $stmt->execute([$id]);
            $response['success'] = true;
        } else {
            throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Initialize variables
$stores = [];
$error = null;

try {
    // Database connection using Database class
    $database = new Database();
    $db = $database->connect();

    if ($db === false) {
        throw new Exception("Database connection failed");
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
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as $row) {
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
    'view' => '<i class="fas fa-eye"></i>',
    'edit' => '<i class="fas fa-edit"></i>',
    'delete' => '<i class="fas fa-trash"></i>'
];
$add_button_label = 'Add New Store';
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
        .modal-lg-custom {
            max-width: 600px;
        }
        .modal-body-custom {
            max-height: 500px;
            overflow-y: auto;
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
                    <?php include 'partials/navbar.php'; ?>
                    <div class="container-fluid py-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-12">
                                <?php include 'partials/table.php'; ?>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">Stores Management</h5>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStoreModal">
                                                <?php echo $add_button_label; ?>
                                            </button>
                                        </div>
                                        <div class="table-responsive mt-3">
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
                        <input type="hidden" name="action" value="add_store">
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
                        <input type="hidden" id="edit_id" name="id">
                        <input type="hidden" name="action" value="edit_store">
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
                    url: '<?php echo basename(__FILE__); ?>',
                    type: 'POST',
                    data: { action: 'delete_store', id: deleteId },
                    success: function(response) {
                        console.log('Delete Response:', response);
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error deleting store: ' + response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Delete Error:', error, xhr.responseText);
                        alert('Error deleting store');
                    }
                });
                $('#deleteStoreModal').modal('hide');
            });

            $('#addStoreForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Submitting Add Form:', $(this).serialize());
                $.ajax({
                    url: '<?php echo basename(__FILE__); ?>',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        console.log('Add Response:', response);
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error adding store: ' + response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Add Error:', error, xhr.responseText);
                        alert('Error adding store');
                    }
                });
            });

            $('#editStoreForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Submitting Edit Form:', $(this).serialize());
                $.ajax({
                    url: '<?php echo basename(__FILE__); ?>',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        console.log('Edit Response:', response);
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error updating store: ' + response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Edit Error:', error, xhr.responseText);
                        alert('Error updating store');
                    }
                });
            });
        });
    </script>
</body>
</html>