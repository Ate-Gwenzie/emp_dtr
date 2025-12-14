<?php
session_start();
require_once __DIR__ . '/../classes/account_request.php';
require_once __DIR__ . '/../classes/notification.php';
require_once __DIR__ . '/../classes/admin.php';
// NEW: Include the EmailSender class
require_once __DIR__ . '/../classes/email_sender.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';

$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$type = $_POST['type'] ?? 'employee';
$employee_id = $_POST['employee_id'] ?? '';
$details = $_POST['details'] ?? '';
$fname = $_POST['fname'] ?? '';
$lname = $_POST['lname'] ?? '';
$position = $_POST['position'] ?? '';
$shift_type = $_POST['shift_type'] ?? 'full';
$timein_am = $_POST['timein_am'] ?? '';
$timeout_am = $_POST['timeout_am'] ?? '';
$timein_pm = $_POST['timein_pm'] ?? '';
$timeout_pm = $_POST['timeout_pm'] ?? '';
$agree = $_POST['agree'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request.';
    } else {
        $full_name = trim(htmlspecialchars($_POST['full_name'] ?? ''));
        $email = trim(htmlspecialchars($_POST['email'] ?? ''));
        $type = $_POST['type'] ?? 'employee';
        $employee_id = trim(htmlspecialchars($_POST['employee_id'] ?? ''));
        $fname = trim(htmlspecialchars($_POST['fname'] ?? ''));
        $lname = trim(htmlspecialchars($_POST['lname'] ?? ''));
        $position = trim(htmlspecialchars($_POST['position'] ?? ''));
        $shift_type = trim(htmlspecialchars($_POST['shift_type'] ?? 'full'));
        $timein_am = trim(htmlspecialchars($_POST['timein_am'] ?? ''));
        $timeout_am = trim(htmlspecialchars($_POST['timeout_am'] ?? ''));
        $timein_pm = trim(htmlspecialchars($_POST['timein_pm'] ?? ''));
        $timeout_pm = trim(htmlspecialchars($_POST['timeout_pm'] ?? ''));
        $details = trim(htmlspecialchars($_POST['details'] ?? ''));
        $agree = isset($_POST['agree']) ? true : false;

        // Validation logic
        if (empty($fname) && empty($full_name)) $errors[] = 'First name or full name is required.';
        if (empty($lname) && empty($full_name)) $errors[] = 'Last name or full name is required.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if ($type === 'employee' && empty($employee_id)) $errors[] = 'Employee ID is required for employee accounts.';
        if (!$agree) $errors[] = 'You must agree that the provided information is true and correct.';

        if ($type === 'employee') {
            if (empty($position)) $errors[] = 'Position is required for employee requests.';
            if ($shift_type === 'full' && (empty($timein_am) || empty($timeout_am) || empty($timein_pm) || empty($timeout_pm))) {
                $errors[] = 'All time fields are required for full shift type.';
            } elseif ($shift_type === 'am_only' && (empty($timein_am) || empty($timeout_am))) {
                $errors[] = 'AM time fields are required for AM only shift.';
            } elseif ($shift_type === 'pm_only' && (empty($timein_pm) || empty($timeout_pm))) {
                $errors[] = 'PM time fields are required for PM only shift.';
            }
        }

        if (empty($errors)) {
            $req = new AccountRequest();
            $requester_name = ($fname && $lname) ? $fname . ' ' . $lname : $full_name;
            
            $req->full_name = $requester_name;
            $req->email = $email;
            $req->type = $type;
            $req->employee_id = $employee_id ?: null;
            
            $extra = [
                'fname' => $fname,
                'lname' => $lname,
                'position' => $position,
                'shift_type' => $shift_type,
                'timein_am' => $timein_am,
                'timeout_am' => $timeout_am,
                'timein_pm' => $timein_pm,
                'timeout_pm' => $timeout_pm,
                'details_text' => $details
            ];
            $req->details = json_encode($extra, JSON_UNESCAPED_UNICODE);
            
            if ($req->save()) {
                
                // ADMIN NOTIFICATION LOGIC START
                try {
                    $notifManager = new Notification();
                    $adminManager = new Admin();
                    $admins = $adminManager->viewAdmin();
                    
                    // Build the detailed notification message
                    $schedule = '';
                    if ($type === 'employee') {
                        $schedule = "Shift Type: " . ucfirst(str_replace('_', ' ', $shift_type)) . "\n"
                                  . "Position: " . ($position ?: 'N/A') . "\n";
                        
                        $schedule .= "\n--- Scheduled Times ---\n";
                        
                        if ($shift_type === 'full' || $shift_type === 'am_only') {
                            $schedule .= "AM Time In: " . ($timein_am ?: 'N/A') . "\n";
                            $schedule .= "AM Time Out: " . ($timeout_am ?: 'N/A') . "\n";
                        }
                        if ($shift_type === 'full') {
                            $schedule .= "--- Break ---\n";
                        }
                        if ($shift_type === 'full' || $shift_type === 'pm_only') {
                            $schedule .= "PM Time In: " . ($timein_pm ?: 'N/A') . "\n";
                            $schedule .= "PM Time Out: " . ($timeout_pm ?: 'N/A') . "\n";
                        }
                        $schedule .= "-----------------------\n";
                    }

                    $notification_message = "New Account Request submitted:\n"
                                          . "--------------------------------\n"
                                          . "Requester Name: " . $requester_name . "\n"
                                          . "Email: " . $email . "\n"
                                          . "Account Type: " . ucfirst($type) . "\n"
                                          . ($type === 'employee' ? "Employee ID: " . $employee_id . "\n" : '')
                                          . "--------------------------------\n"
                                          . $schedule
                                          . "Additional Details:\n" . ($details ?: 'None provided.') . "\n"
                                          . "--------------------------------\n"
                                          . "\nAction Required: Please review this request in the Admin Panel.";

                    // NEW: Email setup
                    $emailSender = new EmailSender();
                    $emailSubject = "ACTION REQUIRED: New Account Request Submitted";
                    
                    // Notify all admins found (in-app AND via email)
                    foreach ($admins as $admin) {
                        // 1. In-App Notification (Original logic)
                        $notifManager->recordNotification(
                            'admin', 
                            $admin['adid'], 
                            'new_account_request', 
                            $notification_message
                        );
                        
                        // 2. Email Notification (New logic)
                        $emailSender->sendEmail(
                            $admin['email_ad'], // Admin's email address
                            $emailSubject, 
                            nl2br(htmlspecialchars($notification_message)), // HTML content
                            $notification_message // Plain text content
                        );
                    }
                } catch (Exception $e) {
                    error_log("Failed to send admin notification for account request: " . $e->getMessage());
                }
                // ADMIN NOTIFICATION LOGIC END

                $_SESSION['request_status_message'] = 'Your request has been submitted. The admin will review it.';
                header('Location: login.php');
                exit();
            } else {
                $errors[] = 'Failed to submit request.';
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
    <title>Request Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{background:#f4f7f6;font-family:Segoe UI, Tahoma, Arial}
        .container{max-width:700px;margin:60px auto}
        .hide{display:none}
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="app-container container">
    <h1>Request Account</h1>
    <p>Please Select the account type and provide details.</p>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>'; ?></ul>
        </div>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="mb-3">
            <label class="form-label">Account Type</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="type" id="type_employee" value="employee" <?php echo ($type==='employee' ? 'checked' : ''); ?>>
                <label class="form-check-label" for="type_employee">Employee</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="type" id="type_admin" value="admin" <?php echo ($type==='admin' ? 'checked' : ''); ?>>
                <label class="form-check-label" for="type_admin">Admin</label>
            </div>
        </div>

        <div class="row">
        <div class="mb-3 col-md-6">
            <label class="form-label">First Name</label>
            <input type="text" class="form-control" name="fname" value="<?php echo htmlspecialchars($fname ?: ($full_name ? explode(' ', $full_name)[0] : '')); ?>">
        </div>
        <div class="mb-3 col-md-6">
            <label class="form-label">Last Name</label>
            <input type="text" class="form-control" name="lname" value="<?php echo htmlspecialchars($lname ?: (count(explode(' ', $full_name)) > 1 ? explode(' ', $full_name, 2)[1] : '')); ?>">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>">
    </div>

    <div id="employee_fields" class="mb-3 <?php echo ($type !== 'employee' ? 'hide' : ''); ?>">
        <div class="mb-3">
            <label class="form-label">Employee ID</label>
            <input type="text" class="form-control" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Position</label>
            <input type="text" class="form-control" name="position" value="<?php echo htmlspecialchars($position); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Shift Type</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="shift_type" id="shift_full" value="full" <?php echo ($shift_type === 'full' ? 'checked' : ''); ?>>
                <label class="form-check-label" for="shift_full">Full</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="shift_type" id="shift_am" value="am_only" <?php echo ($shift_type === 'am_only' ? 'checked' : ''); ?>>
                <label class="form-check-label" for="shift_am">AM Only</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="shift_type" id="shift_pm" value="pm_only" <?php echo ($shift_type === 'pm_only' ? 'checked' : ''); ?>>
                <label class="form-check-label" for="shift_pm">PM Only</label>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">AM Time In</label>
                <input type="time" class="form-control" name="timein_am" value="<?php echo htmlspecialchars($timein_am); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">AM Time Out</label>
                <input type="time" class="form-control" name="timeout_am" value="<?php echo htmlspecialchars($timeout_am); ?>">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">PM Time In</label>
                <input type="time" class="form-control" name="timein_pm" value="<?php echo htmlspecialchars($timein_pm); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">PM Time Out</label>
                <input type="time" class="form-control" name="timeout_pm" value="<?php echo htmlspecialchars($timeout_pm); ?>">
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Additional Details (optional)</label>
        <textarea class="form-control" name="details"><?php echo htmlspecialchars($details); ?></textarea>
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="agree" name="agree" value="1" <?php echo ($agree ? 'checked' : ''); ?> required>
        <label class="form-check-label" for="agree">I confirm that the information provided is true and correct.</label>
    </div>

    <button class="btn btn-outline-warning" type="submit">Submit Request</button>
    <a href="login.php" class="btn btn-outline-danger">Back to Login</a>
</form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const empRadio = document.getElementById('type_employee');
        const adminRadio = document.getElementById('type_admin');
        const empFields = document.getElementById('employee_fields');
        const shiftFull = document.getElementById('shift_full');
        const shiftAM = document.getElementById('shift_am');
        const shiftPM = document.getElementById('shift_pm');
        const amInputs = [document.querySelector('input[name="timein_am"]'), document.querySelector('input[name="timeout_am"]')];
        const pmInputs = [document.querySelector('input[name="timein_pm"]'), document.querySelector('input[name="timeout_pm"]')];
        function toggle(){
            empFields.style.display = empRadio.checked ? 'block' : 'none';
            toggleShift();
        }
        function toggleShift(){
            if (shiftFull.checked) {
                amInputs.forEach(i => i.disabled = false);
                pmInputs.forEach(i => i.disabled = false);
            } else if (shiftAM.checked) {
                amInputs.forEach(i => i.disabled = false);
                pmInputs.forEach(i => i.disabled = true);
            } else if (shiftPM.checked) {
                amInputs.forEach(i => i.disabled = true);
                pmInputs.forEach(i => i.disabled = false);
            }
        }
        empRadio.addEventListener('change', toggle);
        adminRadio.addEventListener('change', toggle);
        shiftFull.addEventListener('change', toggleShift);
        shiftAM.addEventListener('change', toggleShift);
        shiftPM.addEventListener('change', toggleShift);
        toggle();
    });
</script>
</body>
</html>