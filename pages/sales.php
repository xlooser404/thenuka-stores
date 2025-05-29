<?php
session_start();
error_log("sales.php - Session ID: " . session_id());
error_log("sales.php - Session data: " . print_r($_SESSION, true));
error_log("sales.php - CSRF token: " . ($_SESSION['csrf_token'] ?? 'Not set'));

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    error_log("sales.php - Unauthorized access.");
    $_SESSION['error'] = 'Unauthorized access.';
    header('Location: /thenuka-stores/pages/login.php?redirect=' . urlencode('/thenuka-stores/pages/sales.php'));
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    error_log("sales.php - Generated new CSRF token: " . $_SESSION['csrf_token']);
}

require_once __DIR__ . '/../backend/controllers/SalesController.php';
$controller = new SalesController();
$customers = $controller->getAllCustomers();
$products = $controller->getAllProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <title>Sales - Thenuka Stores</title>
    <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet" />
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <link id="pagestyle" href="../assets/css/soft-ui-dashboard.css?v=1.1.0" rel="stylesheet" />
    <style>
        table#productsTable .remove-btn {
            display: inline-block !important;
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
        .receipt { 
            width: 88mm; 
            font-family: monospace; 
            font-size: 12px; 
            padding: 10px; 
            display: none; 
        }
        .receipt table { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .receipt th, .receipt td { 
            text-align: left; 
            padding: 2px; 
        }
    </style>
    <script src="../assets/js/jquery.min.js"></script>
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
                                        <h6>Create New Sale</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="saleForm" method="POST">
                                            <div class="mb-3">
                                                <label for="customer_id" class="form-label">Select Customer</label>
                                                <?php if (empty($customers)): ?>
                                                    <p class="text-danger">No customers available. <a href="/thenuka-stores/pages/customers.php">Add a customer</a>.</p>
                                                <?php else: ?>
                                                    <select class="form-control" id="customer_id" name="customer_id" required>
                                                        <option value="">-- Select Customer --</option>
                                                        <?php foreach ($customers as $customer): ?>
                                                            <option value="<?php echo htmlspecialchars($customer['id']); ?>">
                                                                <?php echo htmlspecialchars($customer['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php endif; ?>
                                            </div>
                                            <div class="table-responsive p-0">
                                                <table class="table align-items-center mb-0" id="productsTable">
                                                    <thead>
                                                        <tr>
                                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">#</th>
                                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Product</th>
                                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Unit Price (Per KG)</th>
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
                                                                                data-price="<?php echo htmlspecialchars($item['price_per_kg']); ?>">
                                                                            <?php echo htmlspecialchars($item['product_name']); ?> (<?php echo htmlspecialchars($item['quantity']); ?> KG)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control unit-price" name="products[0][unit_price]" readonly>
                                                            </td>
                                                            <td>
                                                                <input type="number" class="form-control quantity" name="products[0][quantity_kg]" step="0.1" min="0.1" required>
                                                            </td>
                                                            <td>
                                                                <input type="text" class="form-control total-price" name="products[0][price]" readonly>
                                                            </td>
                                                            <td class="text-center">
                                                                <button type="button" id="remove-btn-0" class="remove-btn" disabled style="display: inline-block !important;">Remove</button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                <button type="button" class="btn btn-primary mt-3" id="addRow">Add Product</button>
                                            </div>
                                            <div class="card mt-4">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <label class="form-label">Subtotal</label>
                                                            <input type="number" step="0.01" class="form-control" id="subtotal" name="subtotal" readonly>
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
                                                    <button type="button" class="btn btn-success mt-3" id="printSubmit">Print & Submit</button>
                                                </div>
                                            </div>
                                        </form>
                                        <!-- Receipt Template -->
                                        <div id="receipt" class="receipt">
                                            <center>
                                                <h3>Thenuka Stores</h3>
                                                <p>123 Main St, Kekanadura</p>
                                                <p>Phone: 074-033-6513</p>
                                                <p>Receipt #: <span id="receipt_sale_id"></span></p>
                                                <p>Date: <span id="receipt_date"></span></p>
                                                <p>Customer: <span id="receipt_customer"></span></p>
                                            </center>
                                            <hr>
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Item</th>
                                                        <th>Qty</th>
                                                        <th>Price</th>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="receipt_items"></tbody>
                                            </table>
                                            <hr>
                                            <p><strong>Subtotal: LKR <span id="receipt_subtotal"></span></strong></p>
                                            <p>Payment Method: <span id="receipt_payment"></span></p>
                                            <center><p>Thank you for shopping with us!</p></center>
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

    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
    <script src="../assets/js/soft-ui-dashboard.min.js"></script>
    <script>
        console.log('Loaded scripts:', Array.from(document.scripts).map(s => s.src));

        var win = navigator.platform.indexOf('Win') > -1;
        if (win && document.querySelector('#sidenav-scrollbar')) {
            var options = { damping: '0.5' };
            Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
        }

        let rowCount = 1;
        const products = <?php echo json_encode($products); ?>;

        function calculateRowTotal(row) {
            try {
                const select = row.querySelector('.product-select');
                const quantityInput = row.querySelector('.quantity');
                const unitPriceInput = row.querySelector('.unit-price');
                const totalPriceInput = row.querySelector('.total-price');

                const pricePerKg = parseFloat(select.options[select.selectedIndex].dataset.price) || 0;
                const quantity = parseFloat(quantityInput.value) || 0;

                unitPriceInput.value = pricePerKg.toFixed(2);
                const total = quantity * pricePerKg;
                totalPriceInput.value = total.toFixed(2);

                updateSubtotal();
            } catch (e) {
                console.error('Error calculating row total:', e);
            }
        }

        function updateSubtotal() {
            try {
                let subtotal = 0;
                document.querySelectorAll('.total-price').forEach(input => {
                    subtotal += parseFloat(input.value) || 0;
                });
                document.getElementById('subtotal').value = subtotal.toFixed(2);
            } catch (e) {
                console.error('Error updating subtotal:', e);
            }
        }

        function updatePreviousDue() {
            try {
                const customerId = document.getElementById('customer_id').value;
                if (customerId) {
                    fetch(`/thenuka-stores/backend/controllers/SalesController.php?action=getDue&customer_id=${customerId}`)
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('previous_due').value = data.due.toFixed(2);
                        })
                        .catch(error => console.error('Error fetching due:', error));
                } else {
                    document.getElementById('previous_due').value = '0.00';
                }
            } catch (e) {
                console.error('Error updating previous due:', e);
            }
        }

        function updateRowNumbers() {
            try {
                const rows = document.querySelectorAll('.product-row');
                rowCount = rows.length;
                rows.forEach((row, index) => {
                    row.querySelector('td:first-child').textContent = index + 1;
                    row.querySelector('.product-select').name = `products[${index}][product_id]`;
                    row.querySelector('.unit-price').name = `products[${index}][unit_price]`;
                    row.querySelector('.quantity').name = `products[${index}][quantity_kg]`;
                    row.querySelector('.total-price').name = `products[${index}][price]`;
                    const removeBtn = row.querySelector('.remove-btn');
                    if (removeBtn) {
                        removeBtn.disabled = (rowCount === 1);
                        console.log(`Row ${index + 1} remove button ID: ${removeBtn.id}`);
                    }
                });
                console.log(`Updated ${rowCount} rows`);
            } catch (e) {
                console.error('Error updating row numbers:', e);
            }
        }

        function bindRemoveEvent(btn, row, index) {
            try {
                console.log(`Binding remove event for row ${index + 1}, button ID: ${btn.id}`);
                btn.addEventListener('click', () => {
                    console.log(`Remove button clicked for row ${index + 1}, ID: ${btn.id}`);
                    row.remove();
                    updateRowNumbers();
                    updateSubtotal();
                });
            } catch (e) {
                console.error('Error binding remove:', e);
            }
        }

        document.getElementById('addRow').addEventListener('click', () => {
            try {
                console.log('Add row clicked');
                const tbody = document.getElementById('productsBody');
                const newRow = document.createElement('tr');
                newRow.classList.add('product-row');
                newRow.innerHTML = `
                    <td class="text-sm">${rowCount + 1}</td>
                    <td>
                        <select class="form-control product-select" name="products[${rowCount}][product_id]" required>
                            <option value="">-- Select Product --</option>
                            ${products.map(p => `
                                <option value="${p.id}" data-price="${p.price_per_kg}">
                                    ${p.product_name} (${p.quantity} KG)
                                </option>
                            `).join('')}
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control unit-price" name="products[${rowCount}][unit_price]" readonly>
                    </td>
                    <td>
                        <input type="number" class="form-control quantity" name="products[${rowCount}][quantity_kg]" step="0.1" min="0.1" required>
                    </td>
                    <td>
                        <input type="text" class="form-control total-price" name="products[${rowCount}][price]" readonly>
                    </td>
                    <td class="text-center">
                        <button type="button" id="remove-btn-${rowCount}" class="remove-btn" style="display: inline-block !important;">Remove</button>
                    </td>
                `;
                tbody.appendChild(newRow);
                const select = newRow.querySelector('.product-select');
                const quantity = newRow.querySelector('.quantity');
                const removeBtn = newRow.querySelector('.remove-btn');
                select.addEventListener('change', () => calculateRowTotal(newRow));
                quantity.addEventListener('input', () => calculateRowTotal(newRow));
                bindRemoveEvent(removeBtn, newRow, rowCount);
                rowCount++;
                updateRowNumbers();
            } catch (e) {
                console.error('Error adding row:', e);
            }
        });

        setTimeout(() => {
            document.querySelectorAll('.product-row').forEach((row, index) => {
                try {
                    console.log(`Initializing row ${index + 1}`);
                    const select = row.querySelector('.product-select');
                    const quantity = row.querySelector('.quantity');
                    const removeBtn = row.querySelector('.remove-btn');
                    console.log(`Row ${index + 1} remove button: ${removeBtn ? removeBtn.id : 'Not found'}`);
                    select.addEventListener('change', () => calculateRowTotal(row));
                    quantity.addEventListener('input', () => calculateRowTotal(row));
                    if (removeBtn) {
                        bindRemoveEvent(removeBtn, row, index);
                    }
                } catch (e) {
                    console.error(`Error initializing row ${index + 1}:`, e);
                }
            });
        }, 100);

        document.getElementById('customer_id').addEventListener('change', updatePreviousDue);
        updateSubtotal();
        updateRowNumbers();

        // Print & Submit
        document.getElementById('printSubmit').addEventListener('click', () => {
            const form = document.getElementById('saleForm');
            const rows = document.querySelectorAll('.product-row');
            let valid = true;
            rows.forEach((row, index) => {
                const select = row.querySelector('.product-select').value;
                const quantity = row.querySelector('.quantity').value;
                if (!select || !quantity || parseFloat(quantity) <= 0) {
                    valid = false;
                    console.error(`Invalid data in row ${index + 1}`);
                }
            });

            if (!valid || !form.checkValidity()) {
                alert('Please ensure all product rows have valid selections and quantities.');
                form.reportValidity();
                return;
            }

            $.ajax({
                url: '/thenuka-stores/backend/controllers/SalesController.php?action=create',
                type: 'POST',
                data: $(form).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Populate receipt
                        let sale = response.sale_details.sale;
                        let items = response.sale_details.items;
                        $('#receipt_sale_id').text(sale.id);
                        $('#receipt_date').text(new Date(sale.sale_date).toLocaleString());
                        $('#receipt_customer').text(sale.customer_name);
                        $('#receipt_subtotal').text(parseFloat(sale.subtotal).toFixed(2));
                        $('#receipt_payment').text(sale.payment_method.charAt(0).toUpperCase() + sale.payment_method.slice(1));

                        let itemsHtml = '';
                        items.forEach(item => {
                            itemsHtml += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${parseFloat(item.quantity_kg).toFixed(2)} kg</td>
                                    <td>LKR ${parseFloat(item.price_per_kg).toFixed(2)}</td>
                                    <td>LKR ${parseFloat(item.price).toFixed(2)}</td>
                                </tr>
                            `;
                        });
                        $('#receipt_items').html(itemsHtml);

                        // Server-side printing
                        $.ajax({
                            url: '/thenuka-stores/backend/print_receipt.php',
                            type: 'POST',
                            data: { sale_id: response.sale_id },
                            dataType: 'json',
                            success: function(printResponse) {
                                if (printResponse.success) {
                                    $('.card-header').after('<div class="alert alert-success text-white">Sale created and receipt printed!</div>');
                                    $('.alert').fadeOut(5000);
                                } else {
                                    $('.card-header').after('<div class="alert alert-warning text-white">Sale created but printing failed. Showing preview.</div>');
                                    $('.alert').fadeOut(5000);
                                    // Fallback to browser print
                                    let receiptWindow = window.open('', '_blank');
                                    receiptWindow.document.write(`
                                        <html>
                                        <head><title>Receipt</title>
                                        <style>
                                            body { width: 88mm; font-family: monospace; font-size: 12px; margin: 0; padding: 10px; }
                                            table { width: 100%; border-collapse: collapse; }
                                            th, td { text-align: left; padding: 2px; }
                                            hr { border: none; border-top: 1px dashed #000; }
                                            .center { text-align: center; }
                                        </style></head>
                                        <body>
                                            ${document.getElementById('receipt').innerHTML}
                                        </body>
                                        </html>
                                    `);
                                    receiptWindow.document.close();
                                    receiptWindow.print();
                                    receiptWindow.close();
                                }
                                // Reset form
                                form.reset();
                                $('#productsBody').html($('#productsBody tr:first').clone());
                                updateRowNumbers();
                                updateSubtotal();
                                updatePreviousDue();
                            },
                            error: function() {
                                $('.card-header').after('<div class="alert alert-warning text-white">Sale created but printing failed. Showing preview.</div>');
                                $('.alert').fadeOut(5000);
                                // Fallback to browser print
                                let receiptWindow = window.open('', '_blank');
                                receiptWindow.document.write(`
                                    <html>
                                    <head><title>Receipt</title>
                                    <style>
                                        body { width: 88mm; font-family: monospace; font-size: 12px; margin: 0; padding: 10px; }
                                        table { width: 100%; border-collapse: collapse; }
                                        th, td { text-align: left; padding: 2px; }
                                        hr { border: none; border-top: 1px dashed #000; }
                                        .center { text-align: center; }
                                    </style></head>
                                    <body>
                                        ${document.getElementById('receipt').innerHTML}
                                    </body>
                                    </html>
                                `);
                                receiptWindow.document.close();
                                receiptWindow.print();
                                receiptWindow.close();
                                // Reset form
                                form.reset();
                                $('#productsBody').html($('#productsBody tr:first').clone());
                                updateRowNumbers();
                                updateSubtotal();
                                updatePreviousDue();
                            }
                        });
                    } else {
                        $('.card-header').after('<div class="alert alert-danger text-white">' + response.error + '</div>');
                        $('.alert').fadeOut(5000);
                    }
                },
                error: function(xhr, status, error) {
                    $('.card-header').after('<div class="alert alert-danger text-white">Error: ' + error + '</div>');
                    $('.alert').fadeOut(5000);
                }
            });
        });
    </script>
</body>
</html>