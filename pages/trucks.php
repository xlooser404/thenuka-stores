<?php
include_once 'partials/header.html';
include_once 'partials/sidebar.php';
include_once 'partials/navbar.php';

require_once __DIR__ . '/../backend/config/database.php';

$trucks = [];
$error = null;

try {
    $database = new Database();
    $db = $database->connect();

    if ($db === false) {
        throw new Exception("Database connection failed");
    }

    $stmt = $db->prepare("SELECT id, license_plate, model, capacity, status, last_maintenance FROM trucks");
    $stmt->execute();
    $result = $stmt->fetchAll();

    foreach ($result as $row) {
        $trucks[] = [
            'data' => [
                ['type' => 'badge', 'value' => htmlspecialchars($row['license_plate']), 'badge_class' => 'bg-gradient-info', 'text_class' => 'text-uppercase font-weight-bold'],
                ['value' => htmlspecialchars($row['model']), 'text_class' => 'text-sm'],
                ['value' => number_format($row['capacity']) . ' kg', 'text_class' => 'text-sm'],
                ['type' => 'badge', 'value' => htmlspecialchars($row['status']), 'badge_class' => $row['status'] === 'Available' ? 'bg-gradient-success' : 'bg-gradient-warning'],
                ['value' => date('M d, Y', strtotime($row['last_maintenance'])), 'text_class' => 'text-xs text-secondary']
            ],
            'actions' => [
                'edit' => 'edit_truck.php?id=' . urlencode($row['id']),
                'delete' => 'delete_truck.php?id=' . urlencode($row['id'])
            ],
            'id' => $row['id'],
            'license_plate' => htmlspecialchars($row['license_plate']),
            'model' => htmlspecialchars($row['model']),
            'capacity' => $row['capacity'],
            'status' => htmlspecialchars($row['status']),
            'last_maintenance' => $row['last_maintenance']
        ];
    }
} catch (Exception $e) {
    $error = "Error loading trucks: " . $e->getMessage();
}

$truck_headers = [
    ['label' => 'License Plate'],
    ['label' => 'Model'],
    ['label' => 'Capacity'],
    ['label' => 'Status', 'class' => 'text-center'],
    ['label' => 'Last Maintenance', 'class' => 'text-center']
];

$truck_actions = ['edit' => 'Edit', 'delete' => 'Delete'];
$add_button_label = 'Add Truck';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Trucks - Delivery & Billing</title>
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
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
            max-height: 500px; /* Increased height for the modal */
            overflow-y: auto;
        }
    </style>
</head>
<body class="g-sidenav-show bg-gray-100">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-1">
                <?php include_once 'partials/sidebar.php'; ?>
            </div>
            <div class="col-md-11">
                <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
                    <?php include_once 'partials/navbar.php'; ?>
                    <div class="container-fluid py-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-12">
                                <?php include_once 'partials/table.php'; ?>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0">Trucks Management</h5>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTruckModal">
                                                <?php echo $add_button_label; ?>
                                            </button>
                                        </div>
                                        <div class="table-responsive mt-3">
                                            <?php
                                            renderTable(
                                                'Trucks Management', 
                                                $truck_headers, 
                                                $trucks, 
                                                $truck_actions
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

    <!-- Add Truck Modal -->
    <div class="modal fade" id="addTruckModal" tabindex="-1" aria-labelledby="addTruckModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTruckModalLabel">Add New Truck</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-custom">
                    <form id="addTruckForm">
                        <div class="mb-3">
                            <label for="add_license_plate" class="form-label">License Plate</label>
                            <input type="text" class="form-control" id="add_license_plate" name="license_plate" placeholder="ABC-1234" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="add_model" name="model" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_capacity" class="form-label">Capacity (kg)</label>
                            <input type="number" class="form-control" id="add_capacity" name="capacity" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_status" class="form-label">Status</label>
                            <select class="form-select" id="add_status" name="status" required>
                                <option value="Available">Available</option>
                                <option value="In Maintenance">In Maintenance</option>
                                <option value="On Route">On Route</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="add_last_maintenance" class="form-label">Last Maintenance Date</label>
                            <input type="date" class="form-control" id="add_last_maintenance" name="last_maintenance" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Truck</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Truck Modal -->
    <div class="modal fade" id="editTruckModal" tabindex="-1" aria-labelledby="editTruckModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg-custom">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTruckModalLabel">Edit Truck</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body modal-body-custom">
                    <form id="editTruckForm">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_license_plate" class="form-label">License Plate</label>
                            <input type="text" class="form-control" id="edit_license_plate" name="license_plate" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="edit_model" name="model" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_capacity" class="form-label">Capacity (kg)</label>
                            <input type="number" class="form-control" id="edit_capacity" name="capacity" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="Available">Available</option>
                                <option value="In Maintenance">In Maintenance</option>
                                <option value="On Route">On Route</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_last_maintenance" class="form-label">Last Maintenance Date</label>
                            <input type="date" class="form-control" id="edit_last_maintenance" name="last_maintenance" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Truck Modal -->
    <div class="modal fade" id="deleteTruckModal" tabindex="-1" aria-labelledby="deleteTruckModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTruckModalLabel">Delete Truck</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the truck with license plate <strong id="delete_license_plate"></strong>?</p>
                    <form id="deleteTruckForm">
                        <input type="hidden" id="delete_id" name="id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </form>
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
        // Populate Edit Modal
        function openEditModal(id, license_plate, model, capacity, status, last_maintenance) {
            $('#edit_id').val(id);
            $('#edit_license_plate').val(license_plate);
            $('#edit_model').val(model);
            $('#edit_capacity').val(capacity);
            $('#edit_status').val(status);
            $('#edit_last_maintenance').val(last_maintenance);
            $('#editTruckModal').modal('show');
        }

        // Populate Delete Modal
        function openDeleteModal(id, license_plate) {
            $('#delete_id').val(id);
            $('#delete_license_plate').text(license_plate);
            $('#deleteTruckModal').modal('show');
        }

        // Handle Add Form Submission
        $('#addTruckForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'add_truck.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error adding truck: ' + response.error);
                    }
                },
                error: function() {
                    alert('Error adding truck');
                }
            });
        });

        // Handle Edit Form Submission
        $('#editTruckForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'edit_truck.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error updating truck: ' + response.error);
                    }
                },
                error: function() {
                    alert('Error updating truck');
                }
            });
        });

        // Handle Delete Form Submission
        $('#deleteTruckForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'delete_truck.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error deleting truck: ' + response.error);
                    }
                },
                error: function() {
                    alert('Error deleting truck');
                }
            });
        });
    </script>
</body>
</html>