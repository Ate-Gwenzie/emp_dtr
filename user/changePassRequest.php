<?php
session_start();
require_once '../classes/database.php';
require_once '../classes/employee.php';
require_once '../classes/password.php';
require_once '../classes/admin.php'; 
require_once '../classes/notification.php'; 
require_once '../classes/email_sender.php'; 

$errors = [];
$success = "";
$email = $_POST['email'] ?? '';
$reason = $_POST['reason'] ?? '';
$employee = new Employee();
$requestManager = new PasswordRequest();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(htmlspecialchars($_POST['email'] ?? ''));
    $reason = trim(htmlspecialchars($_POST['reason'] ?? ''));
    $new_password = trim(htmlspecialchars($_POST['new_password'] ?? ''));
    $confirm_password = trim(htmlspecialchars($_POST['confirm_password'] ?? ''));
    $csrf_token = $_POST['csrf_token'] ?? '';

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request token.";
    } elseif (empty($email) || empty($reason) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    }
    
    if (empty($errors)) {
        $empData = $employee->getEmployeeByEmail($email); 

        if (!$empData) {
            $errors[] = "No employee found with that email address.";
        } else {
            $empid = $empData['empid'];
            $employee_name = $empData['fname_emp'] . ' ' . $empData['lname_emp'];
            $employee_sys_id = $empData['employee_id']; 
            
            if ($requestManager->hasPendingRequest($empid)) { 
                $errors[] = "You already have a pending password reset request. Please wait for an administrator to respond.";
            } else {
                try {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $requestManager->createRequest($empid, $email, $reason, $new_hash);

                    $notifManager = new Notification();
                    $adminManager = new Admin();
                    $admins = $adminManager->viewAdmin();
                    $emailSender = new EmailSender();
                    
                    $emailSubject = "ACTION REQUIRED: New Password Reset Request";
                    
                    $notification_message_text = "New Password Reset Request submitted:\n"
                                          . "--------------------------------\n"
                                          . "Employee Name: " . $employee_name . "\n"
                                          . "Email: " . $email . "\n"
                                          . "Employee ID: " . $employee_sys_id . "\n"
                                          . "Reason: " . $reason . "\n"
                                          . "--------------------------------\n"
                                          . "\nAction Required: Please review this request in the Admin Panel.";

                    $notification_message_html = "
                    <html>
                    <body style='font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px;'>
                        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1); border-top: 5px solid #8b0000;'>
                            
                            <div style='background-color: #8b0000; color: white; padding: 20px 25px; text-align: center;'>
                                <h2 style='margin: 0; font-size: 24px; font-weight: bold;'>Password Reset Request</h2>
                            </div>
                            
                            <div style='padding: 30px 25px; line-height: 1.7; color: #333333;'>
                                
                                <p>Dear Administrator,</p>
                                
                                <p style='font-size: 16px; font-weight: bold; color: #dc3545; margin-top: 10px;'>A new password reset request requires your attention.</p>
                                
                                <table style='width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px;'>
                                    <tr><td style='padding: 8px 0; border-bottom: 1px dashed #eee; width: 35%; font-weight: bold;'>Requester Name:</td><td style='padding: 8px 0; border-bottom: 1px dashed #eee;'>". htmlspecialchars($employee_name) ."</td></tr>
                                    <tr><td style='padding: 8px 0; border-bottom: 1px dashed #eee; font-weight: bold;'>Requester Email:</td><td style='padding: 8px 0; border-bottom: 1px dashed #eee;'>". htmlspecialchars($email) ."</td></tr>
                                    <tr><td style='padding: 8px 0; border-bottom: 1px dashed #eee; font-weight: bold;'>Employee ID:</td><td style='padding: 8px 0; border-bottom: 1px dashed #eee;'>". htmlspecialchars($employee_sys_id) ."</td></tr>
                                    <tr><td colspan='2' style='padding: 15px 0 5px 0; font-weight: bold;'>Reason for Request:</td></tr>
                                    <tr><td colspan='2' style='padding: 0 0 15px 0; background: #fff3cd; padding: 10px; border-radius: 6px; border: 1px solid #ffe0a8;'>". nl2br(htmlspecialchars($reason)) ."</td></tr>
                                </table>
                                
                                <p style='font-size: 14px; text-align: center; color: #6c757d; margin-top: 30px;'>
                                    <strong>Action Required:</strong> Please log in to the Admin Panel to review and process this request immediately.
                                </p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";

                    foreach ($admins as $admin) {
                        $notifManager->recordNotification(
                            'admin', 
                            $admin['adid'], 
                            'new_password_reset', 
                            $notification_message_text
                        );
                        
                        $emailSender->sendEmail(
                            $admin['email_ad'],
                            $emailSubject, 
                            $notification_message_html, 
                            $notification_message_text  
                        );
                    }

                    $_SESSION['request_status_message'] = "Password change request submitted successfully! Your new password will be applied once the administrator confirms your request.";
                    header("Location: login.php");
                    exit();
                } catch (Exception $e) {
                    $errors[] = "Error submitting request: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f4f7f6; 
            margin: 0; 
            padding: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            min-height: 100vh; 
        }
        .app-container { 
            max-width: 500px; 
            margin: 50px auto 20px auto; 
            padding: 0 15px; 
            width: 100%;
        }
        h1 { 
            text-align: center; 
            margin: 30px 0 20px; 
            color: #8b0000; 
            font-weight: 600; 
        }
        .request-card { 
            width: 100%;
        }
        .card { 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 10px; 
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); 
            margin-bottom: 30px; 
        }
        .card-header { 
            background-color: #8b0000; 
            color: white; 
            font-size: 1.2rem; 
            font-weight: bold; 
            padding: 15px 25px; 
            border-top-left-radius: 9px; 
            border-top-right-radius: 9px;
        }
        .card-body { 
            padding: 25px; 
        }
        .form-control { 
            border-radius: 5px; 
            padding: 10px; 
        }
        .btn-primary { 
            background-color: #8b0000; 
            color: white; 
            padding: 10px 30px; 
            border-radius: 50px; 
            font-size: 1.1rem; 
            display: block; 
            width: 70%;
            border: none;
            margin: 25px auto 0; 
            transition: background-color 0.3s, transform 0.3s, color 0.3s;
        }
        .btn-primary:hover { 
            background-color: #a80000;
            color: #ffc107; 
            transform: scale(1.03); 
        }
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 5px; 
            font-weight: 500; 
        }
        .alert-danger { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
    </style>
</head>
<body>
<?php 
require_once __DIR__ . '/../includes/header_forgot_pass.php'; 
?>
    <div class="app-container">
        <div class="request-card">
            <div class="card">
                <div class="card-header">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-key-fill me-2" viewBox="0 0 16 16">
                        <path d="M3.5 11.5a3.5 3.5 0 1 1 3.163-5H14L15 8l-1 1h-7.837a3.5 3.5 0 0 1-3.163 2.5"/>
                        <path d="M12 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/>
                    </svg>
                    Password Change Request
                </div>
                <div class="card-body">
                    <p class="text-muted">Enter your registered email and your desired new password. An administrator will review and confirm this change.</p>
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">Your Registered Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label fw-bold">Reason for Request</label>
                            <textarea class="form-control" id="reason" name="reason" rows="2" required><?php echo htmlspecialchars($reason); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-bold">Desired New Password (Min 6 chars)</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label fw-bold">Confirm Desired Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
