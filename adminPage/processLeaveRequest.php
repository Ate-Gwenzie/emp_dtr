<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/leave.php';
require_once '../classes/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$leaveManager = new Leave();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid request token.";
    } else {
        $leave_id = $_POST['leave_id'] ?? null;
        $action = $_POST['action'] ?? '';

        if (empty($leave_id) || !is_numeric($leave_id) || ($action !== 'approve' && $action !== 'deny')) {
            $message = "Invalid request parameters.";
        } else {
            try {
                if ($action === 'approve') {
                    $leaveManager->updateRequestStatus($leave_id, 'Approved');
                    $message = "Leave request ID {$leave_id} was **APPROVED**. The employee's DTR will reflect 'On Leave' for the specified dates.";
                } elseif ($action === 'deny') {
                    $leaveManager->updateRequestStatus($leave_id, 'Denied');
                    $message = "Leave request ID {$leave_id} was **DENIED**.";
                }
            } catch (Exception $e) {
                $message = "Error processing leave request: " . $e->getMessage();
            }
        }
    }
} else {
    $message = "Invalid request method.";
}

$_SESSION['leave_admin_message'] = $message;
header("Location: viewLeaveRequests.php");
exit();
?>