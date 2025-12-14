<?php
require_once __DIR__ . '/database.php';

class AccountRequest {
    private $db;
    public $id, $full_name, $email, $type, $employee_id, $details, $status;

    public function __construct() {
        $this->db = new Database();
        $this->ensureTable();
    }

    private function ensureTable() {
        $conn = $this->db->getConnection();
        $conn->exec("CREATE TABLE IF NOT EXISTS account_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            type ENUM('employee','admin') NOT NULL,
            employee_id VARCHAR(50) DEFAULT NULL,
            details TEXT DEFAULT NULL,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    public function save() {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO account_requests (full_name,email,type,employee_id,details,status) VALUES (:full_name,:email,:type,:employee_id,:details,'pending')");
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':type', $this->type);
        $stmt->bindParam(':employee_id', $this->employee_id);
        $stmt->bindParam(':details', $this->details);
        return $stmt->execute();
    }

    public function getPendingRequests() {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM account_requests WHERE status = 'pending' ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markApproved($id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE account_requests SET status = 'approved' WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function markRejected($id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE account_requests SET status = 'rejected' WHERE id = :id");
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function fetch($id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM account_requests WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
