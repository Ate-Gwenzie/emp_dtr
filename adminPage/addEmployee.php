<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}
require_once '../classes/employee.php';
require_once '../classes/email_sender.php';
require_once '../classes/email_verification.php';
require_once '../classes/notification.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$employee = new Employee();
$errors = [];
$success = "";

try {
    $notificationManager = new Notification();
    $unreadNotifications = count(array_filter($notificationManager->getNotifications('admin', $_SESSION['admin_id'] ?? 0), function($n){ return ($n['is_read']==0); }));
} catch (Exception $e) {
    $unreadNotifications = 0;
}

$employee->employee_id = $_POST['employee_id'] ?? '';
$employee->fname_emp = $_POST['fname_emp'] ?? '';
$employee->lname_emp = $_POST['lname_emp'] ?? '';
$employee->email_emp = $_POST['email_emp'] ?? '';
$employee->position = $_POST['position'] ?? '';

$employee->timein_am = $_POST['timein_am'] ?? '';
$employee->timeout_am = $_POST['timeout_am'] ?? '';
$employee->timein_pm = $_POST['timein_pm'] ?? '';
$employee->timeout_pm = $_POST['timeout_pm'] ?? '';

$shift_type = $_POST['shift_type'] ?? 'full';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors['general'] = "Invalid request.";
    } else {

        $employee->employee_id = trim(htmlspecialchars($_POST['employee_id'] ?? ""));
        $employee->fname_emp = trim(htmlspecialchars($_POST['fname_emp'] ?? ""));
        $employee->lname_emp = trim(htmlspecialchars($_POST['lname_emp'] ?? ""));
        $employee->email_emp = trim(htmlspecialchars($_POST['email_emp'] ?? ""));
        $employee->position = trim(htmlspecialchars($_POST['position'] ?? ""));
        $pass_emp = trim($_POST['pass_emp'] ?? "");
        $confirm_pass_emp = trim($_POST['confirm_pass_emp'] ?? "");
        $shift_type = trim(htmlspecialchars($_POST['shift_type'] ?? "full")); 

        $employee->timein_am = trim(htmlspecialchars($_POST['timein_am'] ?? ""));
        $employee->timeout_am = trim(htmlspecialchars($_POST['timeout_am'] ?? ""));
        $employee->timein_pm = trim(htmlspecialchars($_POST['timein_pm'] ?? ""));
        $employee->timeout_pm = trim(htmlspecialchars($_POST['timeout_pm'] ?? ""));

        if ($shift_type === 'am_only') {
            $employee->timein_pm = '00:00:00';
            $employee->timeout_pm = '00:00:00';
        } elseif ($shift_type === 'pm_only') {
            $employee->timein_am = '00:00:00';
            $employee->timeout_am = '00:00:00';
        } 
        
        if (empty($employee->employee_id)) {
            $errors['employee_id'] = "Employee ID is required.";
        } elseif (!preg_match('/^\d+$/', $employee->employee_id)) {
            $errors['employee_id'] = "Employee ID must be numeric.";
        }
        if (empty($employee->fname_emp)) {
            $errors['fname_emp'] = "First Name is required.";
        }
        if (empty($employee->lname_emp)) {
            $errors['lname_emp'] = "Last Name is required.";
        }
        if (empty($employee->email_emp)) {
            $errors['email_emp'] = "Email is required.";
        } elseif (!filter_var($employee->email_emp, FILTER_VALIDATE_EMAIL)) {
            $errors['email_emp'] = "Invalid email format.";
        }
        if (empty($employee->position)) {
            $errors['position'] = "Position is required.";
        }
        if (empty($pass_emp)) {
            $errors['pass_emp'] = "Password is required.";
        } elseif (strlen($pass_emp) < 6) {
            $errors['pass_emp'] = "Password must be at least 6 characters long.";
        }
        if (empty($confirm_pass_emp)) {
            $errors['confirm_pass_emp'] = "Confirm Password is required.";
        } elseif ($pass_emp !== $confirm_pass_emp) {
            $errors['confirm_pass_emp'] = "Passwords do not match.";
        }
        
        if (
            !empty($errors['time']) ||
            ($shift_type === 'full' && (empty($employee->timein_am) || empty($employee->timeout_am) || empty($employee->timein_pm) || empty($employee->timeout_pm))) ||
            ($shift_type === 'am_only' && (empty($employee->timein_am) || empty($employee->timeout_am))) ||
            ($shift_type === 'pm_only' && (empty($employee->timein_pm) || empty($employee->timeout_pm)))
        ) {
             if (!isset($errors['time'])) {
                 $errors['time'] = "All required time fields must be filled for the selected shift type.";
             }
        }

        if (empty($errors)) {
            $conn = $employee->getConnection();
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM employee WHERE employee_id = :eid OR email_emp = :email");
            $stmt->bindParam(':eid', $employee->employee_id);
            $stmt->bindParam(':email', $employee->email_emp);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $errors['employee_id'] = "Employee ID or Email already exists.";
            } else {
                $password_hash = password_hash($pass_emp, PASSWORD_DEFAULT);
                // Respect system configuration for email verification
                $cfg = include __DIR__ . '/../config/email_config.php';
                $require_verification = $cfg['require_email_verification'] ?? true;
                if ($require_verification) {
                    $verification_token = bin2hex(random_bytes(16)); // Generate unique token
                    $is_verified = 0;
                } else {
                    $verification_token = null;
                    $is_verified = 1; // mark verified automatically when verification isn't required
                }

                try {
                    // MODIFIED: Pass the verification token and is_verified flag to addEmployee
                    $employee->addEmployee($password_hash, $verification_token, $is_verified);
                    
                    // Fetch the newly created employee's internal ID (empid) to log the notification
                    $lookup_stmt = $conn->prepare("SELECT empid FROM employee WHERE employee_id = :eid");
                    $lookup_stmt->bindParam(':eid', $employee->employee_id);
                    $lookup_stmt->execute();
                    $new_empid = $lookup_stmt->fetchColumn();

                    $emailSender = new EmailSender();
                    $ev = new EmailVerification();
                    // Create a verification record in the email_verifications table only when verification is required
                    $stmtUser = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
                    $stmtUser->bindParam(':email', $employee->email_emp);
                    $stmtUser->execute();
                    $userIdRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
                    $user_id = $userIdRow['id'] ?? null;
                    if ($user_id && $require_verification && !is_null($verification_token)) {
                        $ev->createToken($user_id, $verification_token);
                    }

                    $email_sent = false;
                    try {
                        $email_sent = $emailSender->sendWelcomeEmail(
                            $employee->email_emp, 
                            $employee->fname_emp . ' ' . $employee->lname_emp,
                            $new_empid,
                            $verification_token // Pass token or null
                        );
                    } catch (Exception $e) {
                        error_log('EmailSender::sendWelcomeEmail exception: ' . $e->getMessage());
                        $email_sent = false;
                    }

                    if ($email_sent) {
                        $_SESSION['message'] = "Employee added successfully and welcome email with verification link sent.";
                    } else {
                        $lastErr = $emailSender->getLastError();
                        $_SESSION['message'] = "Employee added successfully, but the welcome email failed to send. Please check SMTP settings and logs." . (!empty($lastErr) ? " Error: " . htmlspecialchars($lastErr) : '');
                        try {
                            $notification = new Notification();
                            $notification->recordNotification('admin', $_SESSION['admin_id'] ?? 0, 'email_failure', 'Employee welcome email failed to send for ' . $employee->email_emp . (!empty($lastErr) ? ' Error: ' . $lastErr : ''));
                        } catch (Exception $e) {
                            error_log('Notification log failure: ' . $e->getMessage());
                        }
                    }

                    header("Location: viewEmployee.php");
                    exit();
                } catch (Exception $e) {
                    $errors['general'] = "Error adding employee: " . $e->getMessage();
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
    <title>Add Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
       
        body {
            background: #f4f7f6;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        /* Header styles centralized to assets/css/app.css */

        h1 {
            text-align: center;
            margin: 30px 0 20px;
            color: #8b0000; 
            font-weight: 300;
        }

       
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .card-header {
            background-color: #8b0000;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            padding: 15px 25px;
            border-bottom: none;
            border-top-left-radius: 9px;
            border-top-right-radius: 9px;
        }

        .card-body {
            padding: 25px;
        }

        .form-control, .form-select {
            border-radius: 5px;
            border: 1px solid #ccc;
            padding: 10px;
            height: auto;
            transition: border-color 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #ffc107;
            box-shadow: 0 0 5px rgba(255, 193, 7, 0.5);
        }

        .form-label {
            font-weight: 600;
        }
        
        .form-section-title {
            color: #8b0000;
            font-weight: bold;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s, transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #ffc107; 
            color: black;
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            display: block;
            width: 50%;
            margin: 20px auto 0;
        }

        .btn-primary:hover {
            background-color: #e0a800;
            color: white;
            transform: scale(1.02);
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
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
        }
        
        .shift-option {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        .shift-option input[type="radio"] {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header_admin.php'; ?>
    <h1>Add Employee</h1>
    <div class="container">
        <div class="card">
            <div class="card-header">New Employee Registration</div>
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success" id="success-alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="form-section-title">Personal & Login Details</h5>
                            <div class="mb-3">
                                <label for="employee_id" class="form-label">✎Employee ID</label>
                                <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?php echo htmlspecialchars($employee->employee_id); ?>" >
                                <?php if (isset($errors['employee_id'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['employee_id']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="fname_emp" class="form-label">✎First Name</label>
                                <input type="text" class="form-control" id="fname_emp" name="fname_emp" value="<?php echo htmlspecialchars($employee->fname_emp); ?>" >
                                <?php if (isset($errors['fname_emp'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['fname_emp']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="lname_emp" class="form-label">✎Last Name</label>
                                <input type="text" class="form-control" id="lname_emp" name="lname_emp" value="<?php echo htmlspecialchars($employee->lname_emp); ?>" >
                                <?php if (isset($errors['lname_emp'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['lname_emp']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="email_emp" class="form-label">✎Email</label>
                                <input type="email" class="form-control" id="email_emp" name="email_emp" value="<?php echo htmlspecialchars($employee->email_emp); ?>" >
                                <?php if (isset($errors['email_emp'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['email_emp']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="position" class="form-label">✎Position</label>
                                <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($employee->position); ?>" >
                                <?php if (isset($errors['position'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['position']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="pass_emp" class="form-label">✎Password</label>
                                <input type="password" class="form-control" id="pass_emp" name="pass_emp" >
                                <?php if (isset($errors['pass_emp'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['pass_emp']); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_pass_emp" class="form-label">✎Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_pass_emp" name="confirm_pass_emp" >
                                <?php if (isset($errors['confirm_pass_emp'])): ?>
                                    <div class="error"><?php echo htmlspecialchars($errors['confirm_pass_emp']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="form-section-title">Daily Time Schedule</h5>

                            <div class="mb-4">
                                <label class="form-label">Select Shift Type</label>
                                <div class="d-flex flex-wrap">
                                    <div class="shift-option">
                                        <input type="radio" id="shift_full" name="shift_type" value="full" class="form-check-input" <?php echo ($shift_type === 'full' ? 'checked' : ''); ?>>
                                        <label for="shift_full" class="form-check-label ms-2">Full Day</label>
                                    </div>
                                    <div class="shift-option">
                                        <input type="radio" id="shift_am" name="shift_type" value="am_only" class="form-check-input" <?php echo ($shift_type === 'am_only' ? 'checked' : ''); ?>>
                                        <label for="shift_am" class="form-check-label ms-2">AM Shift</label>
                                    </div>
                                    <div class="shift-option">
                                        <input type="radio" id="shift_pm" name="shift_type" value="pm_only" class="form-check-input" <?php echo ($shift_type === 'pm_only' ? 'checked' : ''); ?>>
                                        <label for="shift_pm" class="form-check-label ms-2">PM Shift</label>
                                    </div>
                                </div>
                            </div>
                            <div id="am_shift_fields">
                                <div class="mb-3">
                                    <label for="timein_am" class="form-label">✎Clock In Start</label>
                                    <input type="time" class="form-control" id="timein_am" name="timein_am" value="<?php echo htmlspecialchars($employee->timein_am); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="timeout_am" class="form-label">✎Clock Out End</label>
                                    <input type="time" class="form-control" id="timeout_am" name="timeout_am" value="<?php echo htmlspecialchars($employee->timeout_am); ?>">
                                </div>
                            </div>
                            
                            <div id="pm_shift_fields">
                                <div class="mb-3">
                                    <label for="timein_pm" class="form-label">✎Clock In Start</label>
                                    <input type="time" class="form-control" id="timein_pm" name="timein_pm" value="<?php echo htmlspecialchars($employee->timein_pm); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="timeout_pm" class="form-label">✎Clock Out End</label>
                                    <input type="time" class="form-control" id="timeout_pm" name="timeout_pm" value="<?php echo htmlspecialchars($employee->timeout_pm); ?>">
                                </div>
                            </div>

                            <?php if (isset($errors['time'])): ?>
                                <div class="error"><?php echo htmlspecialchars($errors['time']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </form>
            </div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.display = 'none';
            }, 3000); 
        }

        const shiftTypeRadios = document.querySelectorAll('input[name="shift_type"]');
        const amFields = document.getElementById('am_shift_fields');
        const pmFields = document.getElementById('pm_shift_fields');
        const timeInAm = document.getElementById('timein_am');
        const timeOutAm = document.getElementById('timeout_am');
        const timeInPm = document.getElementById('timein_pm');
        const timeOutPm = document.getElementById('timeout_pm');
        
        function toggleShiftFields() {
            const selected = document.querySelector('input[name="shift_type"]:checked').value;
            
            if (selected === 'am_only') {
                amFields.style.display = 'block';
                pmFields.style.display = 'none';
                
                timeInAm.required = true;
                timeOutAm.required = true;
                timeInPm.required = false;
                timeOutPm.required = false;
                
            } else if (selected === 'pm_only') {
                amFields.style.display = 'none';
                pmFields.style.display = 'block';
                
                timeInAm.required = false;
                timeOutAm.required = false;
                timeInPm.required = true;
                timeOutPm.required = true;

            } else { 
                amFields.style.display = 'block';
                pmFields.style.display = 'block';
                
                timeInAm.required = true;
                timeOutAm.required = true;
                timeInPm.required = true;
                timeOutPm.required = true;
            }
        }

        shiftTypeRadios.forEach(radio => {
            radio.addEventListener('change', toggleShiftFields);
        });
        
        toggleShiftFields();
    });
</script>
</body>
</html>