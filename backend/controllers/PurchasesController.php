```php
   <?php
   class PurchasesController {
       private $db;

       public function __construct() {
           require_once __DIR__ . '/../config/database.php';
           $database = new Database();
           $this->db = $database->connect();
           if (!$this->db) {
               error_log("PurchasesController: Database connection failed.");
               throw new Exception("Database connection failed.");
           }
       }

       public function getAllSuppliers() {
           try {
               $query = "SELECT id, name FROM suppliers";
               $stmt = $this->db->query($query);
               return $stmt->fetchAll(PDO::FETCH_ASSOC);
           } catch (PDOException $e) {
               error_log("Error fetching suppliers: " . $e->getMessage());
               return [];
           }
       }

       public function getAllProducts() {
           try {
               $query = "SELECT id, product_name, price_per_kg, quantity 
                        FROM inventory";
               $stmt = $this->db->query($query);
               return $stmt->fetchAll(PDO::FETCH_ASSOC);
           } catch (PDOException $e) {
               error_log("Error fetching products: " . $e->getMessage());
               return [];
           }
       }

       public function getDue($supplierId) {
           try {
               $query = "SELECT total_due FROM suppliers WHERE id = :supplier_id";
               $stmt = $this->db->prepare($query);
               $stmt->execute(['supplier_id' => $supplierId]);
               $result = $stmt->fetch(PDO::FETCH_ASSOC);
               return $result ? (float)$result['total_due'] : 0.00;
           } catch (PDOException $e) {
               error_log("Error fetching supplier due: " . $e->getMessage());
               return 0.00;
           }
       }

       public function createPurchase($data) {
           try {
               $this->db->beginTransaction();

               // Insert purchase
               $query = "INSERT INTO purchases (supplier_id, purchase_date, subtotal, payment_method) 
                        VALUES (:supplier_id, :purchase_date, :subtotal, :payment_method)";
               $stmt = $this->db->prepare($query);
               $stmt->execute([
                   'supplier_id' => $data['supplier_id'],
                   'purchase_date' => $data['purchase_date'],
                   'subtotal' => $data['subtotal'],
                   'payment_method' => $data['payment_method']
               ]);
               $purchaseId = $this->db->lastInsertId();

               // Insert purchase items and update inventory
               foreach ($data['products'] as $item) {
                   // Insert purchase item
                   $query = "INSERT INTO purchase_items (purchase_id, product_id, quantity_kg, price_per_kg, price) 
                            VALUES (:purchase_id, :product_id, :quantity_kg, :price_per_kg, :price)";
                   $stmt = $this->db->prepare($query);
                   $stmt->execute([
                       'purchase_id' => $purchaseId,
                       'product_id' => $item['product_id'],
                       'quantity_kg' => $item['quantity_kg'],
                       'price_per_kg' => $item['price_per_kg'],
                       'price' => $item['price']
                   ]);

                   // Update inventory
                   $query = "UPDATE inventory 
                            SET quantity = quantity + :quantity, 
                                price_per_kg = :price_per_kg,
                                last_updated = CURRENT_TIMESTAMP
                            WHERE id = :product_id";
                   $stmt = $this->db->prepare($query);
                   $stmt->execute([
                       'quantity' => $item['quantity_kg'],
                       'price_per_kg' => $item['price_per_kg'],
                       'product_id' => $item['product_id']
                   ]);

                   // Log inventory transaction
                   $query = "INSERT INTO inventory_transactions (inventory_id, transaction_type, quantity, reason, created_by, created_at)
                            VALUES (:inventory_id, 'PURCHASE', :quantity, 'Purchase ID :purchase_id', :created_by, CURRENT_TIMESTAMP)";
                   $stmt = $this->db->prepare($query);
                   $stmt->execute([
                       'inventory_id' => $item['product_id'],
                       'quantity' => $item['quantity_kg'],
                       'purchase_id' => $purchaseId,
                       'created_by' => $_SESSION['user']['id'] ?? 7 // Fallback to superadmin ID
                   ]);
               }

               // Update supplier total_due if credit
               if ($data['payment_method'] === 'credit') {
                   $query = "UPDATE suppliers 
                            SET total_due = total_due + :subtotal 
                            WHERE id = :supplier_id";
                   $stmt = $this->db->prepare($query);
                   $stmt->execute([
                       'subtotal' => $data['subtotal'],
                       'supplier_id' => $data['supplier_id']
                   ]);
               }

               $this->db->commit();
               return ['success' => true, 'purchase_id' => $purchaseId];
           } catch (PDOException $e) {
               $this->db->rollBack();
               error_log("Error creating purchase: " . $e->getMessage());
               return ['success' => false, 'error' => 'Failed to create purchase: ' . $e->getMessage()];
           }
       }

       public function handleRequest() {
           if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'create') {
               header('Content-Type: application/json');
               $data = $_POST;

               if (!isset($data['csrf_token']) || $data['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                   echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
                   exit;
               }

               if (empty($data['supplier_id']) || empty($data['purchase_date']) || empty($data['products'])) {
                   echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                   exit;
               }

               $subtotal = 0;
               foreach ($data['products'] as &$item) {
                   $item['price'] = $item['quantity_kg'] * $item['price_per_kg'];
                   $subtotal += $item['price'];
               }
               $data['subtotal'] = $subtotal;

               $result = $this->createPurchase($data);
               echo json_encode($result);
               exit;
           }

           if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getDue') {
               header('Content-Type: application/json');
               $supplierId = $_GET['supplier_id'] ?? 0;
               $due = $this->getDue($supplierId);
               echo json_encode(['due' => $due]);
               exit;
           }
       }
   }

   if (session_status() === PHP_SESSION_NONE) {
       session_start();
   }
   $controller = new PurchasesController();
   $controller->handleRequest();
   ?>