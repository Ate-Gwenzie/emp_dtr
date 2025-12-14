<?php
require_once __DIR__ . '/database.php';

class Employee {
    private $db;
    public $empid, $employee_id, $fname_emp, $lname_emp, $email_emp, $position, $pass_emp, $timein_am, $timeout_am, $timein_pm, $timeout_pm;

    public function __construct() {
        $this->db = new Database();
    }
    public function getConnection() {
        return $this->db->getConnection();
    }

    public function getEmployeeByEmail($email) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT empid, employee_id, fname_emp, lname_emp FROM employee WHERE email_emp = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addEmployee($password_hash, $verification_token = null, $is_verified = 0) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();

            $stmt_user = $conn->prepare("INSERT INTO users (email, password, user_type) VALUES (:email, :pass, 'employee')");
            $stmt_user->bindParam(':email', $this->email_emp); 
            $stmt_user->bindParam(':pass', $password_hash);
            $stmt_user->execute();

            $new_user_id = $conn->lastInsertId();

            $stmt_emp = $conn->prepare("INSERT INTO employee (employee_id, fname_emp, lname_emp, email_emp, position, pass_emp, timein_am, timeout_am, timein_pm, timeout_pm, user_id, verification_token, is_verified) 
            VALUES (:eid, :fname, :lname, :email, :pos, :pass_hash_compat, :tin_am, :tout_am, :tin_pm, :tout_pm, :user_id, :token, :is_verified)");
            
            $stmt_emp->bindParam(':eid', $this->employee_id);
            $stmt_emp->bindParam(':fname', $this->fname_emp);
            $stmt_emp->bindParam(':lname', $this->lname_emp);
            $stmt_emp->bindParam(':email', $this->email_emp);
            $stmt_emp->bindParam(':pos', $this->position);
            $stmt_emp->bindParam(':pass_hash_compat', $password_hash); 
            $stmt_emp->bindParam(':tin_am', $this->timein_am);
            $stmt_emp->bindParam(':tout_am', $this->timeout_am);
            $stmt_emp->bindParam(':tin_pm', $this->timein_pm);
            $stmt_emp->bindParam(':tout_pm', $this->timeout_pm);
            $stmt_emp->bindParam(':user_id', $new_user_id);
            if (is_null($verification_token)) {
                $stmt_emp->bindValue(':token', null, PDO::PARAM_NULL);
            } else {
                $stmt_emp->bindValue(':token', $verification_token, PDO::PARAM_STR);
            }
            $stmt_emp->bindValue(':is_verified', (int)$is_verified, PDO::PARAM_INT);
            
            $stmt_emp->execute();

            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw new Exception($e->getMessage()); 
        }
    }

    public function editEmployee() {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE employee SET employee_id = :eid, fname_emp = :fname, lname_emp = :lname, email_emp = :email, position = :pos" . (!empty($this->pass_emp) ? ", pass_emp = :pass" : "") . ", timein_am = :tin_am, timeout_am = :tout_am, timein_pm = :tin_pm, timeout_pm = :tout_pm WHERE empid = :empid");
        $stmt->bindParam(':eid', $this->employee_id);
        $stmt->bindParam(':fname', $this->fname_emp);
        $stmt->bindParam(':lname', $this->lname_emp);
        $stmt->bindParam(':email', $this->email_emp);
        $stmt->bindParam(':pos', $this->position);
        $stmt->bindParam(':tin_am', $this->timein_am);
        $stmt->bindParam(':tout_am', $this->timeout_am);
        $stmt->bindParam(':tin_pm', $this->timein_pm);
        $stmt->bindParam(':tout_pm', $this->timeout_pm);
        $stmt->bindParam(':empid', $this->empid);
        if (!empty($this->pass_emp)) $stmt->bindParam(':pass', $this->pass_emp);
        $stmt->execute();
    }

    public function deleteEmployee($empid) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("DELETE FROM employee WHERE empid = :empid");
        $stmt->bindParam(':empid', $empid);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function fetchEmployee($empid) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT *, is_verified FROM employee WHERE empid = :empid");
        $stmt->bindParam(':empid', $empid);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updatePassword($empid, $old_password, $new_password) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT pass_emp FROM employee WHERE empid = :empid");
        $stmt->bindParam(':empid', $empid);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result && password_verify($old_password, $result['pass_emp'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE employee SET pass_emp = :new_hash WHERE empid = :empid");
            $update_stmt->bindParam(':new_hash', $new_hash);
            $update_stmt->bindParam(':empid', $empid);
            $update_stmt->execute();
            return true;
        }
        return false;
    }


 public function viewEmployee($search = "")
{
    try {
        $conn = $this->db->getConnection();

        $query = "SELECT empid, employee_id, fname_emp, lname_emp, email_emp, position, is_verified FROM employee WHERE 1=1";

        if (!empty($search)) {
            $query .= " AND (fname_emp LIKE :search1 OR lname_emp LIKE :search2 OR employee_id LIKE :search3)";
        }

        $stmt = $conn->prepare($query);

        if (!empty($search)) {
            $search_param = "%$search%";
            $stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
            $stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
            $stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
        }

        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $employees ?: [];

    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}


    public function countEmployees() {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM employee");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function verifyAccount($email, $token) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("SELECT empid FROM employee WHERE email_emp = :email AND verification_token = :token AND is_verified = 0");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($employee) {
            $update_stmt = $conn->prepare("UPDATE employee SET is_verified = 1, verification_token = NULL WHERE empid = :empid");
            $update_stmt->bindParam(':empid', $employee['empid']);
            $update_stmt->execute();
            return true;
        }
        return false;
    }
}
