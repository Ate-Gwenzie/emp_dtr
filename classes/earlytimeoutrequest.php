<?php
require_once __DIR__ . '/database.php';

class EarlyTimeoutRequest {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getConnection() {
        return $this->db->getConnection();
    }

    public function createRequest($emp_id, $date, $session_type, $reason, $requested_time) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO early_timeout_requests (employee_id, request_date, session_type, reason, requested_time, status) VALUES (:emp_id, :date, :session, :reason, :req_time, 'Pending')");
        $stmt->bindParam(':emp_id', $emp_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':session', $session_type);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':req_time', $requested_time);
        return $stmt->execute();
    }

    public function getPendingRequests() {
        $conn = $this->db->getConnection();
        $query = "
            SELECT etr.*, e.employee_id AS emp_number, CONCAT(e.fname_emp, ' ', e.lname_emp) AS full_name FROM early_timeout_requests etr JOIN employee e ON etr.employee_id = e.empid WHERE etr.status = 'Pending'
            ORDER BY etr.request_date DESC, etr.requested_time ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

 
    public function updateRequestStatus($request_id, $status) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE early_timeout_requests SET status = :status, admin_action_date = NOW() WHERE request_id = :request_id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':request_id', $request_id);
        return $stmt->execute();
    }
    
    
    public function getRequestById($request_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM early_timeout_requests WHERE request_id = :request_id");
        $stmt->bindParam(':request_id', $request_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

   
    public function getRequestByEmployeeDateSession($emp_id, $date, $session_type) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM early_timeout_requests WHERE employee_id = :emp_id AND request_date = :date AND session_type = :session AND status = 'Pending'");
        $stmt->bindParam(':emp_id', $emp_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':session', $session_type);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}