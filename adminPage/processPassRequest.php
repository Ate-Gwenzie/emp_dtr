<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/password.php';
require_once '../classes/database.php';
require_once '../classes/employee.php'; 

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$requestManager = new PasswordRequest();
$employeeManager = new Employee();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid request token.";
    } else {
        $request_id = $_POST['request_id'] ?? null;
        $action = $_POST['action'] ?? '';

        if (empty($request_id) || !is_numeric($request_id) || ($action !== 'confirm' && $action !== 'deny')) {
            $message = "Invalid request parameters.";
        } else {
            try {
                $request = $requestManager->getRequestById($request_id); 
                
                if (!$request) {
                     $message = "Request not found or already processed.";
                } elseif ($action === 'confirm') {
                    
                    $emp_id = $request['employee_id'];
                    $new_hash = $request['new_pass_hash']; 

                    $conn = $employeeManager->getConnection();
                    $stmt = $conn->prepare("UPDATE employee SET pass_emp = :new_hash WHERE empid = :empid");
                    $stmt->bindParam(':new_hash', $new_hash);
                    $stmt->bindParam(':empid', $emp_id);
                    $stmt->execute();

                    $requestManager->updateRequestStatus($request_id, 'Resolved');
                    
                    $message = "Password change request for Employee ID {$request['emp_number']} was **CONFIRMED** and the new password is now active.";
                    
                    $_SESSION['password_confirmed_email'] = $request['employee_email'];
                    $_SESSION['password_confirmed_msg'] = "Your new password has been confirmed by the Admin and is now active. Please log in.";

                } elseif ($action === 'deny') {
                   
                    $requestManager->updateRequestStatus($request_id, 'Denied');
                    $message = "Password change request (ID: {$request_id}) was **DENIED**.";
                }
            } catch (Exception $e) {
                $message = "Error processing request: " . $e->getMessage();
            }
        }
    }
} else {
    $message = "Invalid request method.";
}

$_SESSION['password_request_message'] = $message;
header("Location: viewPasswordRequest.php");
exit();