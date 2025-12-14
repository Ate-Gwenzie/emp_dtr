<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    require_once '../classes/earlytimeoutrequest.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $emp_id = $_SESSION['employee_id'];
        $date = $_POST['date'] ?? date('Y-m-d');
        $session = $_POST['session'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        $requested_time = $_POST['requested_time'] ?? date('H:i:s');
        $csrf_token = $_POST['csrf_token'] ?? '';

        if (empty($csrf_token) || $csrf_token !== $_SESSION['csrf_token']) {
            $response['message'] = "Invalid request token.";
        } elseif (empty($session) || ($session !== 'AM' && $session !== 'PM')) {
            $response['message'] = "Invalid session type.";
        } elseif (empty($reason)) {
            $response['message'] = "Reason for early timeout is required.";
        } else {
            $requestManager = new EarlyTimeoutRequest();
            
          
            if ($requestManager->getRequestByEmployeeDateSession($emp_id, $date, $session)) {
                $response['message'] = "You already have a pending early time-out request for the {$session} session today.";
            } else {
                
                if ($requestManager->createRequest($emp_id, $date, $session, htmlspecialchars($reason), $requested_time)) {
                    $response['success'] = true;
                    $response['message'] = "Early Time-out request submitted for {$session} session. Waiting for Admin approval.";
                } else {
                    $response['message'] = "Failed to submit request to the database.";
                }
            }
        }
    } else {
        $response['message'] = "Invalid request method.";
    }

} catch (Exception $e) {
    
    $response['message'] = "An internal error occurred. Please contact the administrator. (Error Code 500)";
    error_log("Early Timeout Request Error: " . $e->getMessage());
}

echo json_encode($response);
?>