<?php
// Start session for potential session-based messages (though we primarily use GET/redirect here)
session_start();
require_once '../classes/database.php';
require_once '../classes/employee.php';

// 1. Get the parameters (both email and token are sent in the email link)
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if (empty($email) || empty($token)) {
    // If either is missing, redirect to login with a failure message.
    header("Location: login.php?verified=incomplete");
    exit();
}

try {
    // 2. Use the Employee class to perform verification
    $employee = new Employee();
    
    // The verifyAccount method updates the database (is_verified = 1, token = NULL) 
    // if the email and token match and the account is not yet verified.
    $isVerified = $employee->verifyAccount($email, $token); 

    if ($isVerified) {
        // 3. Success: Account is now validated.
        // We use 'verified=success' flag for login.php to pick up and display the message.
        header("Location: login.php?verified=success");
        exit();
    } else {
        // 4. Failure (e.g., invalid token, token already used, or account already verified)
        header("Location: login.php?verified=failed");
        exit();
    }
} catch (Exception $e) {
    // Log the error for internal debugging
    error_log("Verification failed for email {$email}: " . $e->getMessage());
    
    // Redirect to login with a technical error flag
    header("Location: login.php?verified=error");
    exit();
}
?>