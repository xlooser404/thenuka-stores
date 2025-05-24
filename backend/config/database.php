<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'thenuka_db';
    private $username = 'root';
    private $password = ''; // Consider storing in a .env file
    private $conn;

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            return $this->conn;
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            return false;
        }
    }
}
?>
