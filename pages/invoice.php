<?php include 'partials/header.html'; ?>
<?php include 'partials/sidebar.php'; ?>
<?php include 'partials/navbar.php'; ?>

<?php
require_once 'config/database.php'; // Database configuration

// Initialize variables
$invoices = [];
$error = null;
$overdueCount = 0;
$totalOutstanding = 0;

try {
    // Database connection
    $db = new mysqli('localhost', 'username', 'password', 'delivery_billing');
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    // Fetch invoice data with prepared statement
    $stmt = $db->prepare("SELECT 
                            i.invoice_id,
                            i.invoice_number,
                            c.customer_name,
                            i.invoice_date,
                            i.due_date,
                            i.total_amount,
                            i.amount_paid,
                            i.payment_status,
                            i.notes
                          FROM invoices i
                          JOIN customers c ON i.customer_id = c.customer_id
                          ORDER BY i.due_date DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $outstanding = $row['total_amount'] - $row['amount_paid'];
        $isOverdue = strtotime($row['due_date']) < time() && $row['payment_status'] !== 'Paid';
        if ($isOverdue) $overdueCount++;
        if ($row['payment_status'] !== 'Paid') $totalOutstanding += $outstanding;
        
        $invoices[] = [
            'data' => [
                [
                    'value' => htmlspecialchars($row['invoice_number']),
                    'text_class' => 'font-weight-bold'
                ],
                [
                    'value' => htmlspecialchars($row['customer_name']),
                    'text_class' => 'text-sm'
                ],
                [
                    'value' => date('M d, Y', strtotime($row['invoice_date'])),
                    'text_class' => 'text-xs'
                ],
                [
                    'value' => date('M d, Y', strtotime($row['due_date'])),
                    'text_class' => $isOverdue ? 'font-weight-bold text-danger' : 'text-xs'
                ],
                [
                    'value' => '$' . number_format($row['total_amount'], 2),
                    'text_class' => 'text-sm font-weight-bold'
                ],
                [
                    'value' => '$' . number_format($outstanding, 2),
                    'text_class' => $outstanding > 0 ? 'font-weight-bold text-warning' : 'text-sm'
                ],
                [
                    'type' => 'badge',
                    'value' => htmlspecialchars($row['payment_status']),
                    'badge_class' => match($row['payment_status']) {
                        'Paid' => 'bg-gradient-success',
                        'Overdue' => 'bg-gradient-danger',
                        'Partial' => 'bg-gradient-warning',
                        default => 'bg-gradient-secondary'
                    }
                ]
            ],
            'row_class' => $isOverdue ? 'table-danger' : '',
            'actions' => [
                'view' => 'view_invoice.php?id=' . urlencode($row['invoice_id']),
                'print' => 'print_invoice.php?id=' . urlencode($row['invoice_id']),
                'payment' => 'record_payment.php?id=' . urlencode($row['invoice_id'])
            ]
        ];
    }
    $stmt->close();
    $db->close();
} catch (Exception $e) {
    $error = "Error loading invoices: " . $e->getMessage();
}

// Table configuration
$invoice_headers = [
    ['label' => 'Invoice #'],
    ['label' => 'Customer'],
    ['label' => 'Invoice Date', 'class' => 'text-center'],
    ['label' => 'Due Date', 'class' => 'text-center'],
    ['label' => 'Total Amount', 'class' => 'text-center'],
    ['label' => 'Outstanding', 'class' => 'text-center'],
    ['label' => 'Status', 'class' => 'text-center']
];

$invoice_actions = [
    'view' => '<i class="fas fa-file-invoice" data-toggle="tooltip" title="View Invoice"></i>',
    'print' => '<i class="fas fa-print" data-toggle="tooltip" title="Print Invoice"></i>',
    'payment' => '<i class="fas fa-money-bill-wave" data-toggle="tooltip" title="Record Payment"></i>'
];

$add_button_label = 'Create New Invoice';
$form_fields = [
    'customer_id' => [
        'label' => 'Customer',
        'type' => 'select',
        'options_query' => 'SELECT customer_id, CONCAT(customer_name, " (", email, ")") as name FROM customers WHERE status = "Active"'
    ],
    'invoice_date' => [
        'label' => 'Invoice Date',
        'type' => 'date',
        'value' => date('Y-m-d')
    ],
    'due_date' => [
        'label' => 'Due Date',
        'type' => 'date',
        'value' => date('Y-m-d', strtotime('+30 days'))
    ],
    'terms' => [
        'label' => 'Payment Terms',
        'type' => 'select',
        'options' => [
            'NET 15' => 'NET 15',
            'NET 30' => 'NET 30',
            'Due on Receipt' => 'Due on Receipt'
        ]
    ],
    'notes' => [
        'label' => 'Notes',
        'type' => 'textarea',
        'rows' => 3
    ]
];
$form_action = 'create_invoice.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>Invoice Management - Delivery & Billing</title>
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
    .invoice-alert {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
      display: <?php echo $overdueCount > 0 ? 'block' : 'none'; ?>;
    }
    .overdue-row {
      animation: pulseDanger 2s infinite;
    }
    @keyframes pulseDanger {
      0% { background-color: rgba(220,53,69,0.1); }
      50% { background-color: rgba(220,53,69,0.3); }
      100% { background-color: rgba(220,53,69,0.1); }
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
            
            <!-- Invoice Summary Cards -->
            <div class="row mb-4">
              <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                  <div class="card-body p-3">
                    <div class="row">
                      <div class="col-8">
                        <div class="numbers">
                          <p class="text-sm mb-0 text-capitalize font-weight-bold">Total Invoices</p>
                          <h5 class="font-weight-bolder mb-0">
                            <?php echo count($invoices); ?>
                          </h5>
                        </div>
                      </div>
                      <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-md">
                          <i class="fas fa-file-invoice text-lg opacity-10" aria-hidden="true"></i>
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
                          <p class="text-sm mb-0 text-capitalize font-weight-bold">Overdue Invoices</p>
                          <h5 class="font-weight-bolder mb-0">
                            <?php echo $overdueCount; ?>
                            <?php if ($overdueCount > 0): ?>
                              <span class="text-danger text-sm font-weight-bolder">(Urgent)</span>
                            <?php endif; ?>
                          </h5>
                        </div>
                      </div>
                      <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-danger shadow text-center border-radius-md">
                          <i class="fas fa-exclamation-circle text-lg opacity-10" aria-hidden="true"></i>
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
                          <p class="text-sm mb-0 text-capitalize font-weight-bold">Outstanding Balance</p>
                          <h5 class="font-weight-bolder mb-0">
                            $<?php echo number_format($totalOutstanding, 2); ?>
                          </h5>
                        </div>
                      </div>
                      <div class="col-4 text-end">
                        <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                          <i class="fas fa-money-bill-wave text-lg opacity-10" aria-hidden="true"></i>
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
                    'Invoice Management', 
                    $invoice_headers, 
                    $invoices, 
                    $invoice_actions, 
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

  <!-- Overdue Invoice Alert Notification -->
  <?php if ($overdueCount > 0): ?>
    <div class="invoice-alert">
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <span class="alert-icon"><i class="fas fa-exclamation-circle"></i></span>
        <span class="alert-text"><strong><?php echo $overdueCount; ?> overdue invoices</strong> totaling $<?php echo number_format($totalOutstanding, 2); ?>!</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    </div>
  <?php endif; ?>

  <!-- Core JS Files -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/soft-ui-dashboard.min.js?v=1.1.0"></script>
  
  <script>
    // Invoice-specific JavaScript
    $(document).ready(function() {
      // Enable tooltips for action icons
      $('[data-toggle="tooltip"]').tooltip();
      
      // Dynamic customer loading for form
      $.get('api/get_customers.php', function(customers) {
        var select = $('#customer_id');
        select.empty();
        $.each(customers, function(key, value) {
          select.append($('<option>', {
            value: key,
            text: value
          }));
        });
      }, 'json');
      
      // Flash overdue rows
      $('.table-danger').hover(
        function() {
          $(this).addClass('overdue-row');
        }, 
        function() {
          $(this).removeClass('overdue-row');
        }
      );
      
      // Set due date based on terms selection
      $('#terms').change(function() {
        const terms = $(this).val();
        const invoiceDate = new Date($('#invoice_date').val());
        let dueDate = new Date(invoiceDate);
        
        if (terms === 'NET 15') {
          dueDate.setDate(dueDate.getDate() + 15);
        } else if (terms === 'NET 30') {
          dueDate.setDate(dueDate.getDate() + 30);
        } // "Due on Receipt" keeps the same date
        
        $('#due_date').val(dueDate.toISOString().split('T')[0]);
      });
    });
  </script>
</body>
</html>