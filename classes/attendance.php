<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/leave.php'; 

class Attendance {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function addAttendance($emp_id, $date, $time_in_am, $time_out_am, $time_in_pm, $time_out_pm, $status) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, date, time_in_am, time_out_am, time_in_pm, time_out_pm, status, confirmation_status) VALUES (:emp_id, :date, :time_in_am, :time_out_am, :time_in_pm, :time_out_pm, :status, 'Draft')");
        $stmt->bindParam(':emp_id', $emp_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time_in_am', $time_in_am);
        $stmt->bindParam(':time_out_am', $time_out_am);
        $stmt->bindParam(':time_in_pm', $time_in_pm);
        $stmt->bindParam(':time_out_pm', $time_out_pm);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
    }

    public function updateAttendance($emp_id, $date, $time_in_am, $time_out_am, $time_in_pm, $time_out_pm) {
        $conn = $this->db->getConnection();
        
        $query = "UPDATE attendance SET time_in_am = COALESCE(:time_in_am, time_in_am), time_out_am = COALESCE(:time_out_am, time_out_am), time_in_pm = COALESCE(:time_in_pm, time_in_pm), time_out_pm = COALESCE(:time_out_pm, time_out_pm)";
        
        if ($time_out_pm !== null && $time_out_pm !== '00:00:00') {
            $query .= ", confirmation_status = 'Pending'";
        } elseif ($time_out_am !== null && $time_out_pm === '00:00:00' && $time_in_pm === '00:00:00') {
             $query .= ", confirmation_status = 'Pending'";
        }
        
        $query .= " WHERE employee_id = :emp_id AND date = :date";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':emp_id', $emp_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':time_in_am', $time_in_am);
        $stmt->bindParam(':time_out_am', $time_out_am);
        $stmt->bindParam(':time_in_pm', $time_in_pm);
        $stmt->bindParam(':time_out_pm', $time_out_pm);
        $stmt->execute();
    }

    public function getLastAttendance($emp_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = :emp_id ORDER BY date DESC LIMIT 1");
        $stmt->bindParam(':emp_id', $emp_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function viewAttendance($emp_id, $month = null) {
        $conn = $this->db->getConnection();
        $query = "SELECT * FROM attendance WHERE employee_id = :emp_id";
        
        $year = date('Y');
        $start_date = $year . '-' . $month . '-01';
        $end_date = $year . '-' . $month . '-' . date('t', strtotime($start_date));
        
        if ($month) $query .= " AND MONTH(date) = :month";
        $query .= " ORDER BY date DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':emp_id', $emp_id);
        if ($month) $stmt->bindParam(':month', $month);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $leaveManager = new Leave();
        $leaves = $leaveManager->getApprovedLeavesForEmployee($emp_id, $start_date, $end_date);
        
        $leaveDates = [];
        foreach ($leaves as $leave) {
            $current = strtotime($leave['start_date']);
            $end = strtotime($leave['end_date']);
            while ($current <= $end) {
                $leaveDates[date('Y-m-d', $current)] = true;
                $current = strtotime('+1 day', $current);
            }
        }
        
        $finalRecords = [];
        $dtrDates = array_column($records, 'date');
        
        foreach ($leaveDates as $date => $isLeave) {
            if (!in_array($date, $dtrDates)) {
                $finalRecords[] = ['id' => null,'employee_id' => $emp_id,'date' => $date,'time_in_am' => null,'time_out_am' => null,'time_in_pm' => null,'time_out_pm' => null,
                'status' => 'On Leave','confirmation_status' => 'Confirmed' ];
            }
        }
        
        $finalRecords = array_merge($records, $finalRecords);
        usort($finalRecords, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $finalRecords;
    }
    
    public function confirmAttendance($attendance_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE attendance SET confirmation_status = 'Confirmed' WHERE id = :id AND confirmation_status = 'Pending'");
        $stmt->bindParam(':id', $attendance_id);
        return $stmt->execute();
    }
    
    public function getPendingDTRs() {
        $conn = $this->db->getConnection();
        $query = "
            SELECT a.id, a.employee_id, a.date, a.time_in_am, a.time_out_am, a.time_in_pm, a.time_out_pm, e.employee_id AS emp_number, CONCAT(e.fname_emp, ' ', e.lname_emp) AS full_name
            FROM attendance a
            JOIN employee e ON a.employee_id = e.empid
            WHERE a.confirmation_status = 'Pending'
            ORDER BY a.date DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}