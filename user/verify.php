<?php
session_start();
require_once '../classes/database.php';
require_once '../classes/employee.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if (empty($email) || empty($token)) {
    header("Location: login.php?verified=incomplete");
    exit();
}

try {
    $employee = new Employee();
    
    $isVerified = $employee->verifyAccount($email, $token); 

    if ($isVerified) {
        header("Location: login.php?verified=success");
        exit();
    } else {
        header("Location: login.php?verified=failed");
        exit();
    }
} catch (Exception $e) {
    error_log("Verification failed for email {$email}: " . $e->getMessage());
    
    header("Location: login.php?verified=error");
    exit();
}
?>
