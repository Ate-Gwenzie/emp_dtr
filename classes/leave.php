<?php
require_once __DIR__ . '/database.php';

class Leave {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function createRequest($employee_id, $start_date, $end_date, $reason) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare(" INSERT INTO leave_applications (employee_id, start_date, end_date, reason, status) VALUES (:employee_id, :start_date, :end_date, :reason, 'Pending')");
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->bindParam(':reason', $reason);
        return $stmt->execute();
    }

    public function getPendingRequests() {
        $conn = $this->db->getConnection();
        $query = "
            SELECT la.leave_id, la.start_date, la.end_date, la.reason, la.created_at, e.employee_id AS emp_number, CONCAT(e.fname_emp, ' ', e.lname_emp) AS full_name
            FROM leave_applications la 
            JOIN employee e ON la.employee_id = e.empid
            WHERE la.status = 'Pending'
            ORDER BY la.created_at ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
   
    public function updateRequestStatus($leave_id, $status) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare(" UPDATE leave_applications SET status = :status WHERE leave_id = :leave_id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':leave_id', $leave_id);
        return $stmt->execute();
    }
    
    public function getApprovedLeavesForEmployee($emp_id, $start_date, $end_date) {
        $conn = $this->db->getConnection();
        $query = " SELECT start_date, end_date, reason FROM leave_applications WHERE employee_id = :emp_id AND status = 'Approved' AND (start_date <= :end_date AND end_date >= :start_date)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':emp_id', $emp_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}