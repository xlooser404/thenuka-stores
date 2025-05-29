<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


   session_start();
   error_log("buy.php - Session ID: " . session_id());
   error_log("buy.php - Session data: " . print_r($_SESSION, true));

   if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
       error_log("buy.php - Unauthorized access.");
       $_SESSION['error'] = 'Unauthorized access.';
       header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/buy.php'));
       exit;
   }

   if (!isset($_SESSION['csrf_token'])) {
       $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
       error_log("buy.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
   }

   require_once __DIR__ . '/../backend/controllers/PurchasesController.php';
   try {
       $controller = new PurchasesController();
       $suppliers = $controller->getAllSuppliers();
       $products = $controller->getAllProducts();
   } catch (Exception $e) {
       error_log("buy.php - Error initializing controller: " . $e->getMessage());
       $_SESSION['error'] = 'Failed to load purchase form. Please try again.';
       header('Location: /thenuka-stores/pages/dashboard.php');
       exit;
   }
   ?>

   <!DOCTYPE html>
   <html lang="en">
   <head>
       <meta charset="utf-8" />
       <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
       <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
       <link rel="icon" type="image/png" href="../assets/img/favicon.png">
       <title>Buy - Thenuka Stores</title>
       <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
       <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
       <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
       <link id="pagestyle" href="../assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
       <style>
           table#productsTable .remove-btn {
               background: #dc3545 !important;
               color: white !important;
               border: none !important;
               padding: 5px 10px !important;
               cursor: pointer !important;
               font-size: 14px !important;
               margin: 0 auto !important;
           }
           table#productsTable .remove-btn:disabled {
               background: #6c757d !important;
               cursor: not-allowed !important;
           }
           .main-content {
               margin-left: 230px !important;
               width: calc(100% - 230px) !important;
               padding: 15px;
           }
           @media (max-width: 991px) {
               .main-content {
                   margin-left: 0 !important;
                   width: 100% !important;
               }
           }
       </style>
       <script src="../assets/js/jquery.min.js"></script>
   </head>
   <body class="g-sidenav-show bg-gray-100">
       <div class="container-fluid">
           <div class="row">
               <?php include 'partials/sidebar.php'; ?>
               <main class="main-content position-relative border-radius-lg">
                   <?php include 'partials/navbar.php'; ?>
                   <div class="container-fluid py-4">
                       <div class="row">
                           <div class="col-12">
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
                               <div class="card">
                                   <div class="card-header pb-0">
                                       <h6>Create New Purchase</h6>
                                   </div>
                                   <div class="card-body">
                                       <form id="purchaseForm" method="POST">
                                           <div class="row mb-3">
                                               <div class="col-md-6">
                                                   <label for="supplier_id" class="form-label">Select Supplier</label>
                                                   <?php if (empty($suppliers)): ?>
                                                       <p class="text-danger">No suppliers available. <a href="/thenuka-stores/pages/suppliers.php">Add a supplier</a>.</p>
                                                   <?php else: ?>
                                                       <select class="form-control" id="supplier_id" name="supplier_id" required>
                                                           <option value="">-- Select Supplier --</option>
                                                           <?php foreach ($suppliers as $supplier): ?>
                                                               <option value="<?php echo htmlspecialchars($supplier['id']); ?>">
                                                                   <?php echo htmlspecialchars($supplier['name']); ?>
                                                               </option>
                                                           <?php endforeach; ?>
                                                       </select>
                                                   <?php endif; ?>
                                               </div>
                                               <div class="col-md-6">
                                                   <label for="purchase_date" class="form-label">Purchase Date</label>
                                                   <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?php echo date('Y-m-d'); ?>" required>
                                               </div>
                                           </div>
                                           <div class="table-responsive p-0">
                                               <table class="table align-items-center mb-0" id="productsTable">
                                                   <thead>
                                                       <tr>
                                                           <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">#</th>
                                                           <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Product</th>
                                                           <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Current Stock (KG)</th>
                                                           <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Purchase Price (Per KG)</th>
                                                           <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Quantity (KG)</th>
                                                           <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Total Price</th>
                                                           <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">Action</th>
                                                       </tr>
                                                   </thead>
                                                   <tbody id="productsBody">
                                                       <tr class="product-row">
                                                           <td class="text-sm">1</td>
                                                           <td>
                                                               <select class="form-control product-select" name="products[0][product_id]" required>
                                                                   <option value="">-- Select Product --</option>
                                                                   <?php foreach ($products as $item): ?>
                                                                       <option value="<?php echo htmlspecialchars($item['id']); ?>" 
                                                                               data-stock="<?php echo htmlspecialchars($item['quantity'] ?? 0); ?>">
                                                                           <?php echo htmlspecialchars($item['product_name']); ?>
                                                                       </option>
                                                                   <?php endforeach; ?>
                                                               </select>
                                                           </td>
                                                           <td>
                                                               <input type="text" class="form-control current-stock" readonly>
                                                           </td>
                                                           <td>
                                                               <input type="number" class="form-control purchase-price" name="products[0][price_per_kg]" step="0.01" min="0" required>
                                                           </td>
                                                           <td>
                                                               <input type="number" class="form-control quantity" name="products[0][quantity_kg]" step="0.1" min="0.1" required>
                                                           </td>
                                                           <td>
                                                               <input type="text" class="form-control total-price" readonly>
                                                           </td>
                                                           <td class="text-center">
                                                               <button type="button" class="remove-btn" disabled>Remove</button>
                                                           </td>
                                                       </tr>
                                                   </tbody>
                                               </table>
                                               <div class="mt-2">
                                                   <button type="button" class="btn btn-primary" id="addRow">Add Product</button>
                                                   <a href="/thenuka-stores/pages/inventory.php" class="btn btn-secondary">Add New Product</a>
                                               </div>
                                           </div>
                                           <div class="card mt-4">
                                               <div class="card-body">
                                                   <div class="row">
                                                       <div class="col-md-4">
                                                           <label class="form-label">Subtotal</label>
                                                           <input type="number" step="0.01" class="form-control" id="subtotal" readonly>
                                                       </div>
                                                       <div class="col-md-4">
                                                           <label class="form-label">Previous Total Due</label>
                                                           <input type="text" class="form-control" id="previous_due" readonly>
                                                       </div>
                                                       <div class="col-md-4">
                                                           <label class="form-label">Payment Method</label>
                                                           <select class="form-control" name="payment_method" required>
                                                               <option value="cash">Cash</option>
                                                               <option value="credit">Credit</option>
                                                               <option value="online">Online</option>
                                                           </select>
                                                       </div>
                                                   </div>
                                                   <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                   <button type="submit" class="btn btn-success mt-3">Submit Purchase</button>
                                               </div>
                                           </div>
                                       </form>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </main>
           </div>
       </div>

       <script src="../assets/js/core/popper.min.js"></script>
       <script src="../assets/js/core/bootstrap.min.js"></script>
       <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
       <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
       <script src="../assets/js/soft-ui-dashboard.min.js"></script>
       <script>
           var win = navigator.platform.indexOf('Win') > -1;
           if (win && document.querySelector('#sidenav-scrollbar')) {
               var options = { damping: '0.5' };
               Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
           }

           let rowCount = 1;

           function calculateRowTotal(row) {
               const select = row.querySelector('.product-select');
               const stockInput = row.querySelector('.current-stock');
               const purchasePriceInput = row.querySelector('.purchase-price');
               const quantityInput = row.querySelector('.quantity');
               const totalPriceInput = row.querySelector('.total-price');

               const stock = parseFloat(select.options[select.selectedIndex]?.dataset.stock) || 0;
               const purchasePrice = parseFloat(purchasePriceInput.value) || 0;
               const quantity = parseFloat(quantityInput.value) || 0;

               stockInput.value = stock.toFixed(2);
               const total = quantity * purchasePrice;
               totalPriceInput.value = total.toFixed(2);

               updateSubtotal();
           }

           function updateSubtotal() {
               let subtotal = 0;
               document.querySelectorAll('.total-price').forEach(input => {
                   subtotal += parseFloat(input.value) || 0;
               });
               document.getElementById('subtotal').value = subtotal.toFixed(2);
           }

           function updatePreviousDue() {
               const supplierId = document.getElementById('supplier_id').value;
               if (supplierId) {
                   fetch(`/thenuka-stores/backend/controllers/PurchasesController.php?action=getDue&supplier_id=${supplierId}`)
                       .then(response => response.json())
                       .then(data => {
                           document.getElementById('previous_due').value = (data.due || 0).toFixed(2);
                       })
                       .catch(error => console.error('Error fetching due:', error));
               } else {
                   document.getElementById('previous_due').value = '0.00';
               }
           }

           function updateRowNumbers() {
               const rows = document.querySelectorAll('.product-row');
               rows.forEach((row, index) => {
                   row.querySelector('td:first-child').textContent = index + 1;
                   row.querySelector('.product-select').name = `products[${index}][product_id]`;
                   row.querySelector('.purchase-price').name = `products[${index}][price_per_kg]`;
                   row.querySelector('.quantity').name = `products[${index}][quantity_kg]`;
                   const removeBtn = row.querySelector('.remove-btn');
                   removeBtn.disabled = rows.length === 1;
               });
               rowCount = rows.length;
           }

           document.getElementById('addRow').addEventListener('click', () => {
               const tbody = document.getElementById('productsBody');
               const newRow = document.createElement('tr');
               newRow.className = 'product-row';
               newRow.innerHTML = `
                   <td class="text-sm">${rowCount + 1}</td>
                   <td>
                       <select class="form-control product-select" name="products[${rowCount}][product_id]" required>
                           <option value="">-- Select Product --</option>
                           <?php foreach ($products as $item): ?>
                               <option value="<?php echo htmlspecialchars($item['id']); ?>" 
                                       data-stock="<?php echo htmlspecialchars($item['quantity'] ?? 0); ?>">
                                   <?php echo htmlspecialchars($item['product_name']); ?>
                               </option>
                           <?php endforeach; ?>
                       </select>
                   </td>
                   <td><input type="text" class="form-control current-stock" readonly></td>
                   <td><input type="number" class="form-control purchase-price" name="products[${rowCount}][price_per_kg]" step="0.01" min="0" required></td>
                   <td><input type="number" class="form-control quantity" name="products[${rowCount}][quantity_kg]" step="0.1" min="0.1" required></td>
                   <td><input type="text" class="form-control total-price" readonly></td>
                   <td class="text-center"><button type="button" class="remove-btn">Remove</button></td>
               `;
               tbody.appendChild(newRow);

               const select = newRow.querySelector('.product-select');
               const purchasePrice = newRow.querySelector('.purchase-price');
               const quantity = newRow.querySelector('.quantity');
               select.addEventListener('change', () => calculateRowTotal(newRow));
               purchasePrice.addEventListener('input', () => calculateRowTotal(newRow));
               quantity.addEventListener('input', () => calculateRowTotal(newRow));
               newRow.querySelector('.remove-btn').addEventListener('click', () => {
                   newRow.remove();
                   updateRowNumbers();
                   updateSubtotal();
               });

               rowCount++;
               updateRowNumbers();
           });

           document.querySelectorAll('.product-row').forEach(row => {
               const select = row.querySelector('.product-select');
               const purchasePrice = row.querySelector('.purchase-price');
               const quantity = row.querySelector('.quantity');
               const removeBtn = row.querySelector('.remove-btn');
               select.addEventListener('change', () => calculateRowTotal(row));
               purchasePrice.addEventListener('input', () => calculateRowTotal(row));
               quantity.addEventListener('input', () => calculateRowTotal(row));
               removeBtn.addEventListener('click', () => {
                   row.remove();
                   updateRowNumbers();
                   updateSubtotal();
               });
           });

           document.getElementById('supplier_id').addEventListener('change', updatePreviousDue);

           document.getElementById('purchaseForm').addEventListener('submit', (e) => {
               e.preventDefault();
               const form = document.getElementById('purchaseForm');
               if (!form.checkValidity()) {
                   form.reportValidity();
                   return;
               }

               $.ajax({
                   url: '/thenuka-stores/backend/controllers/PurchasesController.php?action=create',
                   type: 'POST',
                   data: $(form).serialize(),
                   dataType: 'json',
                   success: function(response) {
                       if (response.success) {
                           $('.card-header').after('<div class="alert alert-success text-white">Purchase created successfully!</div>');
                           $('.alert').fadeOut(5000);
                           form.reset();
                           $('#productsBody').html($('#productsBody tr:first').clone());
                           updateRowNumbers();
                           updateSubtotal();
                           updatePreviousDue();
                       } else {
                           $('.card-header').after('<div class="alert alert-danger text-white">' + (response.error || 'Unknown error') + '</div>');
                           $('.alert').fadeOut(5000);
                       }
                   },
                   error: function(xhr, status, error) {
                       $('.card-header').after('<div class="alert alert-danger text-white">Error: ' . error . '</div>');
                       $('.alert').fadeOut(5000);
                   }
               });
           });

           updateRowNumbers();
           updateSubtotal();
       </script>
   </body>
   </html>
   ```