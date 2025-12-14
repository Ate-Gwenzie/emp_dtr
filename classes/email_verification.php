<?php
require_once __DIR__ . '/database.php';

class EmailVerification {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->ensureTable();
    }

    private function ensureTable() {
        $conn = $this->db->getConnection();
        $conn->exec("CREATE TABLE IF NOT EXISTS email_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            verified TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    public function createToken($user_id, $token) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO email_verifications (user_id, token, verified) VALUES (:user_id, :token, 0)");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':token', $token);
        return $stmt->execute();
    }

    public function verifyToken($token) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM email_verifications WHERE token = :token LIMIT 1");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $update = $conn->prepare("UPDATE email_verifications SET verified = 1 WHERE id = :id");
            $update->bindParam(':id', $row['id']);
            $update->execute();
            return $row;
        }
        return false;
    }

    public function isVerified($user_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT verified FROM email_verifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? (bool)$row['verified'] : true;
    }
}
