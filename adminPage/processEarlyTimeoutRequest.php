<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/earlytimeoutrequest.php';
require_once '../classes/attendance.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$requestManager = new EarlyTimeoutRequest();
$attendanceManager = new Attendance();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid request token.";
    } else {
        $request_id = $_POST['request_id'] ?? null;
        $action = $_POST['action'] ?? '';

        if (empty($request_id) || !is_numeric($request_id) || ($action !== 'approve' && $action !== 'deny')) {
            $message = "Invalid request parameters.";
        } else {
            $request = $requestManager->getRequestById($request_id);

            if (!$request || $request['status'] !== 'Pending') {
                $message = "Request not found or already processed.";
            } else {
                try {
                    if ($action === 'approve') {

                        $emp_id = $request['employee_id'];
                        $date = $request['request_date'];
                        $time = $request['requested_time'];
                        $session = $request['session_type'];
                        
                        $time_out_am = ($session === 'AM') ? $time : null;
                        $time_out_pm = ($session === 'PM') ? $time : null;

                        $attendanceManager->updateAttendance($emp_id, $date, null, $time_out_am, null, $time_out_pm);
                        
                        $requestManager->updateRequestStatus($request_id, 'Approved');
                        $message = "Early Time-out request for Employee ID " . $request['emp_number'] . " on " . $date . " was **APPROVED** and attendance updated.";

                    } elseif ($action === 'deny') {
                        $requestManager->updateRequestStatus($request_id, 'Denied');
                        $message = "Early Time-out request for Employee ID " . $request['emp_number'] . " on " . $date . " was **DENIED**.";
                    }
                } catch (Exception $e) {
                    $message = "Error processing attendance: " . $e->getMessage();
                }
            }
        }
    }
} else {
    $message = "Invalid request method.";
}

$_SESSION['request_message'] = $message;
header("Location: viewEarlyTime.php");
exit();
?>