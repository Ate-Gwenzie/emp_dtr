<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/attendance.php';
require_once '../classes/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$attendanceManager = new Attendance();
$db = new Database();
$conn = $db->getConnection();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid request token.";
    } else {
        $attendance_id = $_POST['attendance_id'] ?? null;
        $action = $_POST['action'] ?? '';

        if (empty($attendance_id) || !is_numeric($attendance_id) || ($action !== 'confirm' && $action !== 'reject')) {
            $message = "Invalid request parameters.";
        } else {
            try {
                if ($action === 'confirm') {
                    $attendanceManager->confirmAttendance($attendance_id);
                    $message = "DTR Confirmation for Attendance ID {$attendance_id} was **CONFIRMED**.";
                    
                } elseif ($action === 'reject') {
                    $stmt = $conn->prepare("UPDATE attendance SET confirmation_status = 'Draft' WHERE id = :id AND confirmation_status = 'Pending'");
                    $stmt->bindParam(':id', $attendance_id);
                    $stmt->execute();
                    $message = "DTR Confirmation for Attendance ID {$attendance_id} was **REJECTED** (status set to Draft).";
                }
            } catch (Exception $e) {
                $message = "Error processing DTR: " . $e->getMessage();
            }
        }
    }
} else {
    $message = "Invalid request method.";
}

$_SESSION['dtr_confirmation_message'] = $message;
header("Location: viewPendingPage.php");
exit();
?>