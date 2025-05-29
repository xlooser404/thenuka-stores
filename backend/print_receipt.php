
   <?php
   session_start();
   require_once __DIR__ . '/../backend/controllers/SalesController.php';
   require __DIR__ . '/../vendor/autoload.php';
   use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
   use Mike42\Escpos\Printer;

   function printReceipt($sale_details) {
       try {
           $connector = new CupsPrintConnector("XP-P801A"); // Adjust printer name
           $printer = new Printer($connector);

           $printer->setJustification(Printer::JUSTIFY_CENTER);
           $printer->text("Thenuka Stores\n");
           $printer->text("123 Main St, Kekanadura\n");
           $printer->text("Phone: 074-033-6513\n");
           $printer->text("Receipt #: " . $sale_details['sale']['id'] . "\n");
           $printer->text("Date: " . date('Y-m-d H:i:s', strtotime($sale_details['sale']['sale_date'])) . "\n");
           $printer->text("Customer: " . $sale_details['sale']['customer_name'] . "\n");
           $printer->text("----------------------------------------\n");

           $printer->setJustification(Printer::JUSTIFY_LEFT);
           $printer->text(sprintf("%-20s %5s %10s %10s\n", "Item", "Qty", "Price", "Total"));
           foreach ($sale_details['items'] as $item) {
               $printer->text(sprintf("%-20s %5.2f %10.2f %10.2f\n", 
                   substr($item['product_name'], 0, 20),
                   $item['quantity_kg'],
                   $item['price_per_kg'],
                   $item['price']
               ));
           }
           $printer->text("----------------------------------------\n");

           $printer->text(sprintf("%-36s %10.2f\n", "Subtotal:", $sale_details['sale']['subtotal']));
           $printer->text("Payment Method: " . ucfirst($sale_details['sale']['payment_method']) . "\n");
           $printer->text("\n");
           $printer->setJustification(Printer::JUSTIFY_CENTER);
           $printer->text("Thank you for shopping with us!\n");
           $printer->cut();

           $printer->close();
           return true;
       } catch (Exception $e) {
           error_log("print_receipt.php - Error printing: " . $e->getMessage());
           return false;
       }
   }

   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sale_id'])) {
       $controller = new SalesController();
       $sale_details = $controller->getSaleDetails($_POST['sale_id']);
       if ($sale_details) {
           $result = printReceipt($sale_details);
           echo json_encode(['success' => $result]);
       } else {
           echo json_encode(['success' => false, 'error' => 'Sale not found']);
       }
   }
   ?>
   ```