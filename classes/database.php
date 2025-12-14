<?php

class Database {
   
    private $host = "127.0.0.1";
    private $username = "root";
    private $password = "";
    private $dbname = "dtr_data";
    
    private $conn = null;

    public function __construct() {
        if ($this->conn === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8";
            
            $options = [
              
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          
                PDO::ATTR_EMULATE_PREPARES   => false                     
            ];

            try {
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                
                throw new RuntimeException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function __destruct() {
        $this->conn = null;
    }
}
?>
