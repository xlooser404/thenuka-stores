<?php include 'partials/header.html'; ?>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/navbar.php'; ?>


<?php
require_once 'config/database.php'; // Database configuration

// Initialize variables
$agents = [];
$error = null;
$activeAgents = 0;

try {
    // Database connection
    $db = new mysqli('localhost', 'username', 'password', 'delivery_billing');
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    // Fetch agent data with prepared statement
    $stmt = $db->prepare("SELECT 
                            a.agent_id,
                            a.agent_name,
                            a.phone,
                            a.email,
                            a.vehicle_type,
                            a.availability_status,
                            a.rating,
                            COUNT(d.delivery_id) AS deliveries_completed,
                            MAX(d.delivery_date) AS last_delivery_date
                          FROM delivery_agents a
                          LEFT JOIN deliveries d ON a.agent_id = d.agent_id
                          GROUP BY a.agent_id
                          ORDER BY a.availability_status, deliveries_completed DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if ($row['availability_status'] === 'Available') $activeAgents++;
        
        $agents[] = [
            'data' => [
                [
                    'type' => 'image',
                    'value' => '../assets/img/team-3.jpg',
                    'label' => htmlspecialchars($row['agent_name']),
                    'subtext' => htmlspecialchars($row['vehicle_type'])
                ],
                [
                    'value' => htmlspecialchars($row['phone']),
                    'text_class' => 'text-sm'
                ],
                [
                    'value' => htmlspecialchars($row['email']),
                    'text_class' => 'text-xs text-secondary'
                ],
                [
                    'type' => 'badge',
                    'value' => htmlspecialchars($row['availability_status']),
                    'badge_class' => match($row['availability_status']) {
                        'Available' => 'bg-gradient-success',
                        'On Delivery' => 'bg-gradient-info',
                        'Off Duty' => 'bg-gradient-secondary',
                        'On Leave' => 'bg-gradient-warning',
                        default => 'bg-gradient-dark'
                    }
                ],
                [
                    'type' => 'stars',
                    'value' => $row['rating'],
                    'max' => 5,
                    'text_class' => 'text-center'
                ],
                [
                    'value' => $row['deliveries_completed'],
                    'text_class' => 'text-center font-weight-bold'
                ],
                [
                    'value' => $row['last_delivery_date'] ? date('M d, Y', strtotime($row['last_delivery_date'])) : 'Never',
                    'text_class' => 'text-xs'
                ]
            ],
            'actions' => [
                'assign' => 'assign_delivery.php?agent_id=' . urlencode($row['agent_id']),
                'edit' => 'edit_agent.php?id=' . urlencode($row['agent_id']),
                'schedule' => 'agent_schedule.php?id=' . urlencode($row['agent_id'])
            ]
        ];
    }
    $stmt->close();
    $db->close();
} catch (Exception $e) {
    $error = "Error loading agents: " . $e->getMessage();
}

// Table configuration
$agent_headers = [
    ['label' => 'Agent'],
    ['label' => 'Phone'],
    ['label' => 'Email'],
    ['label' => 'Status', 'class' => 'text-center'],
    ['label' => 'Rating', 'class' => 'text-center'],
    ['label' => 'Deliveries', 'class' => 'text-center'],
    ['label' => 'Last Delivery', 'class' => 'text-center']
];

$agent_actions = [
    'assign' => '<i class="fas fa-truck-ramp-box" data-toggle="tooltip" title="Assign Delivery"></i>',
    'edit' => '<i class="fas fa-user-edit" data-toggle="tooltip" title="Edit Agent"></i>',
    'schedule' => '<i class="fas fa-calendar-alt" data-toggle="tooltip" title="View Schedule"></i>'
];

$add_button_label = 'Add New Agent';
$form_fields = [
    'agent_name' => [
        'label' => 'Full Name',
        'type' => 'text',
        'placeholder' => 'Enter agent name'
    ],
    'phone' => [
        'label' => 'Phone Number',
        'type' => 'tel',
        'pattern' => '[0-9]{10}'
    ],
    'email' => [
        'label' => 'Email',
        'type' => 'email'
    ],
    'vehicle_type' => [
        'label' => 'Vehicle Type',
        'type' => 'select',
        'options' => [
            'Motorcycle' => 'Motorcycle',
            'Car' => 'Car',
            'Truck' => 'Truck',
            'Bicycle' => 'Bicycle'
        ]
    ],
    'license_number' => [
        'label' => 'License Number',
        'type' => 'text'
    ],
    'availability_status' => [
        'label' => 'Initial Status',
        'type' => 'select',
        'options' => [
            'Available' => 'Available',
            'Off Duty' => 'Off Duty'
        ]
    ]
];
$form_action = 'add_agent.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>Delivery Agents - Delivery & Billing</title>
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
    .stars {
      color: #FFD700;
      font-size: 1.2rem;
    }
    .agent-availability {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
    }
    .available-agent {
      border-left: 4px solid #2dce89;
    }
    .on-delivery-agent {
      border-left: 4px solid #11cdef;
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
            
            <!-- Agent Summary Cards -->
            <div class="row mb-4">
              <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                  <div class="card-body p-3">
                    <div class="row">
                      <div class="col-8">
                        <div class="numbers">
                          <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Agents</p>
                          <h5 class="font-weight-bolder mb-0">
                            <?php echo count($agents); ?>
                          </h5>
                        </div>
                      </div>
                      <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                          <i class="fas fa-users text-lg opacity-10" aria-hidden="true"></i>
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
                          <p class="text-sm mb-0 text-capitalize font-weight-bold">Available Agents</p>
                          <h5 class="font-weight-bolder mb-0">
                            <?php echo $activeAgents; ?>
                            <span class="text-success text-sm font-weight-bolder">(<?php echo round(($activeAgents/max(1, count($agents)))*100); ?>%)</span>
                          </h5>
                        </div>
                      </div>
                      <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                          <i class="fas fa-user-check text-lg opacity-10" aria-hidden="true"></i>
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
                          <p class="text-sm mb-0 text-capitalize font-weight-bold">Avg. Rating</p>
                          <h5 class="font-weight-bolder mb-0">
                            <?php 
                              $avgRating = array_reduce($agents, function($carry, $agent) {
                                return $carry + $agent['data'][4]['value'];
                              }, 0) / max(1, count($agents));
                              echo number_format($avgRating, 1);
                            ?>
                            <span class="stars"><?php echo str_repeat('★', floor($avgRating)) . str_repeat('☆', ceil(5 - $avgRating)); ?></span>
                          </h5>
                        </div>
                      </div>
                      <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                          <i class="fas fa-star text-lg opacity-10" aria-hidden="true"></i>
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
                    'Delivery Agents', 
                    $agent_headers, 
                    $agents, 
                    $agent_actions, 
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

  <!-- Available Agents Notification -->
  <div class="agent-availability">
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
      <span class="alert-text">
        <strong><?php echo $activeAgents; ?> agents</strong> available for deliveries
      </span>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  </div>

  <!-- Core JS Files -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>
  
  <script>
    // Agent-specific JavaScript
    $(document).ready(function() {
      // Enable tooltips for action icons
      $('[data-toggle="tooltip"]').tooltip();
      
      // Highlight available agents
      $('tr').each(function() {
        const statusBadge = $(this).find('.badge');
        if (statusBadge.text() === 'Available') {
          $(this).addClass('available-agent');
        } else if (statusBadge.text() === 'On Delivery') {
          $(this).addClass('on-delivery-agent');
        }
      });
      
      // Vehicle type specific fields
      $('#vehicle_type').change(function() {
        const vehicleType = $(this).val();
        if (vehicleType === 'Motorcycle' || vehicleType === 'Bicycle') {
          $('#license_number').closest('.mb-3').hide();
        } else {
          $('#license_number').closest('.mb-3').show();
        }
      }).trigger('change');
    });
  </script>
</body>
</html>