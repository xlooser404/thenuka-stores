```php
   <?php
   class BaseController {
       protected $db;

       public function __construct() {
           require_once __DIR__ . '/../config/database.php';
           if (!isset($db)) {
               error_log("BaseController: Database connection not initialized.");
               throw new Exception("Database connection failed.");
           }
           $this->db = $db;
       }
   }
   ?>