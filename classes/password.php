<?php
require_once __DIR__ . '/database.php';

class PasswordRequest {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }
    
    public function hasPendingRequest($emp_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM password_reset_requests WHERE employee_id = :emp_id AND status = 'Pending'");
        $stmt->bindParam(':emp_id', $emp_id);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
    
    public function createRequest($emp_id, $email, $reason, $new_pass_hash) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO password_reset_requests (employee_id, employee_email, reason, new_pass_hash, status) VALUES (:emp_id, :email, :reason, :new_hash, 'Pending')");
        $stmt->bindParam(':emp_id', $emp_id);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':new_hash', $new_pass_hash);
        return $stmt->execute();
    }
    
    public function getPendingRequests() {
        $conn = $this->db->getConnection();
        $query = "
            SELECT prr.*, e.employee_id AS emp_number,CONCAT(e.fname_emp, ' ', e.lname_emp) AS full_name FROM password_reset_requests prr
            JOIN employee e ON prr.employee_id = e.empid
            WHERE prr.status = 'Pending'
            ORDER BY prr.created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRequestById($request_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT prr.*, e.pass_emp AS current_hash FROM password_reset_requests prr JOIN employee e ON prr.employee_id = e.empid WHERE prr.request_id = :id");
        $stmt->bindParam(':id', $request_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateRequestStatus($request_id, $status) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE password_reset_requests SET status = :status, admin_action_date = NOW() WHERE request_id = :request_id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':request_id', $request_id);
        return $stmt->execute();
    }
}
