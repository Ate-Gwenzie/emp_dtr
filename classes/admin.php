<?php
require_once __DIR__ . '/database.php';

class Admin {
    private $db;
    public $adid, $fname_ad, $lname_ad, $email_ad, $pass_ad;

    public function __construct() {
        $this->db = new Database();
    }
    
    public function getConnection() {
        return $this->db->getConnection();
    }
    
    public function addAdmin($password_hash) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();

            $stmt_user = $conn->prepare("INSERT INTO users (email, password, user_type) VALUES (:email, :pass, 'admin')");
            $stmt_user->bindParam(':email', $this->email_ad); 
            $stmt_user->bindParam(':pass', $password_hash);
            $stmt_user->execute();

            $new_user_id = $conn->lastInsertId();

            $stmt_admin = $conn->prepare("INSERT INTO admin (fname_ad, lname_ad, email_ad, pass_ad, user_id) VALUES (:fname, :lname, :email, :pass_hash_compat, :user_id)");
            
            $stmt_admin->bindParam(':fname', $this->fname_ad);
            $stmt_admin->bindParam(':lname', $this->lname_ad);
            $stmt_admin->bindParam(':email', $this->email_ad);
            $stmt_admin->bindParam(':pass_hash_compat', $password_hash);
            $stmt_admin->bindParam(':user_id', $new_user_id);
            
            $stmt_admin->execute();

            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw new Exception($e->getMessage()); 
        }
    }

    public function editAdmin() {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE admin SET fname_ad = :fname, lname_ad = :lname, email_ad = :email" . (!empty($this->pass_ad) ? ", pass_ad = :pass" : "") . " WHERE adid = :adid");
        $stmt->bindParam(':fname', $this->fname_ad);
        $stmt->bindParam(':lname', $this->lname_ad);
        $stmt->bindParam(':email', $this->email_ad);
        $stmt->bindParam(':adid', $this->adid);
        if (!empty($this->pass_ad)) $stmt->bindParam(':pass', $this->pass_ad);
        $stmt->execute();
    }

    public function deleteAdmin($adid) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("DELETE FROM admin WHERE adid = :adid");
        $stmt->bindParam(':adid', $adid);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function fetchAdmin($adid) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM admin WHERE adid = :adid");
        $stmt->bindParam(':adid', $adid);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function viewAdmin() {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM admin");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAdmins() {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}