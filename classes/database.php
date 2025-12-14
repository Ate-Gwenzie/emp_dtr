<?php
/**
 * Database class to handle PDO connection.
 * * NOTE: For production environments, database credentials should be stored 
 * in a separate, secure configuration file (e.g., .env) and loaded here 
 * to prevent hardcoding them directly in the class file.
 */
class Database {
    // Database credentials
    private $host = "127.0.0.1";
    private $username = "root";
    private $password = "";
    private $dbname = "dtr_data";
    
    private $conn = null;

    public function __construct() {
        // Only attempt connection if not already connected
        if ($this->conn === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8";
            
            $options = [
                // Recommended PDO options for security and error handling
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Default fetch mode to associative array
                PDO::ATTR_EMULATE_PREPARES   => false                      // Crucial: Use native prepared statements for security
            ];

            try {
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                // FIX: Instead of `die()`, a reusable class should throw a custom exception
                // to allow the calling script to handle the connection failure gracefully.
                // This makes the class more functional in a complex application.
                throw new RuntimeException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }
    }

    /**
     * Get the database connection object.
     * @return PDO|null The active PDO connection object.
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Close the database connection when the object is destroyed.
     */
    public function __destruct() {
        $this->conn = null;
    }
}
?>