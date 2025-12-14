<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../user/login.php');
    exit();
}
require_once __DIR__ . '/../classes/account_request.php';
require_once __DIR__ . '/../classes/admin.php';
require_once __DIR__ . '/../classes/employee.php';
require_once __DIR__ . '/../classes/email_verification.php';
require_once __DIR__ . '/../classes/email_sender.php';

$reqManager = new AccountRequest();
$ev = new EmailVerification();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'create';
    $r = $reqManager->fetch($id);
    if (!$r) {
        $_SESSION['message'] = 'Request not found.';
        header('Location: viewAccountRequests.php'); exit();
    }

    if ($action === 'reject') {
        $reqManager->markRejected($id);
        $_SESSION['message'] = 'Request rejected.';
        header('Location: viewAccountRequests.php'); exit();
    }

    // create account
    $email = $r['email'];
    $full = $r['full_name'];
    $type = $r['type'];
    $employee_id = $r['employee_id'];

    // basic split name
    $names = preg_split('/\s+/', $full, 2);
    $fname = $names[0] ?? '';
    $lname = $names[1] ?? '';

    // decode details (if any) for additional info
    $extra = [];
    if (!empty($r['details'])) {
        $tmp = json_decode($r['details'], true);
        if (is_array($tmp)) $extra = $tmp;
    }

    // allow details to override or provide name parts
    if (!empty($extra['fname'])) $fname = $extra['fname'];
    if (!empty($extra['lname'])) $lname = $extra['lname'];

    $position = $extra['position'] ?? '';
    $shift_type = $extra['shift_type'] ?? 'full';
    $timein_am = $extra['timein_am'] ?? '08:00:00';
    $timeout_am = $extra['timeout_am'] ?? '12:00:00';
    $timein_pm = $extra['timein_pm'] ?? '13:00:00';
    $timeout_pm = $extra['timeout_pm'] ?? '17:00:00';


    // generate temporary password
    $temp_pass = bin2hex(random_bytes(4));
    $hash = password_hash($temp_pass, PASSWORD_DEFAULT);

    $cfg = include __DIR__ . '/../config/email_config.php';
    $require_verification = $cfg['require_email_verification'] ?? true;

    $dbClass = ($type === 'admin') ? new Admin() : new Employee();
    $conn = $dbClass->getConnection();

    // check duplicates
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['c'] > 0) {
        $_SESSION['message'] = 'Email already exists.';
        header('Location: viewAccountRequests.php'); exit();
    }

    try {
        if ($type === 'admin') {
            $admin = new Admin();
            $admin->fname_ad = $fname;
            $admin->lname_ad = $lname;
            $admin->email_ad = $email;
            $admin->addAdmin($hash);
        } else {
            $employee = new Employee();
            $employee->employee_id = $employee_id ?: '';
            $employee->fname_emp = $fname;
            $employee->lname_emp = $lname;
            $employee->email_emp = $email;
            $employee->position = $position ?: 'Employee';
            $employee->timein_am = $timein_am ?: '08:00:00';
            $employee->timeout_am = $timeout_am ?: '12:00:00';
            $employee->timein_pm = $timein_pm ?: '13:00:00';
            $employee->timeout_pm = $timeout_pm ?: '17:00:00';
            if ($require_verification) {
                $verification_token = bin2hex(random_bytes(16));
                $is_verified = 0;
            } else {
                $verification_token = null;
                $is_verified = 1;
            }
            $employee->addEmployee($hash, $verification_token, $is_verified);
        }

        // find user id
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_id = $user['id'] ?? null;
        if ($user_id && $type !== 'admin') {
            // Only create verification for non-admin accounts
            $token = $verification_token;
            // create/generate token only when email verification is required
            if ($require_verification && empty($token)) {
                $token = bin2hex(random_bytes(16));
            }
            if ($require_verification && !empty($token)) {
                $ev->createToken($user_id, $token);
            }

            // send verification email using Mailer
            $verify_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../verify.php?email=' . urlencode($email) . '&token=' . ($token ?? '');
            $subject = 'Your account was created - verify your email';
            $plain = "Hello $full\n\nAn account was created for you. Verify here: $verify_link\nTemporary password: $temp_pass";
            $data = ['full' => $full, 'verify_link' => $verify_link, 'temp_pass' => $temp_pass];
            // Also update the employee table's verification_token so employee.verifyAccount can match if needed
            $stmt_up = $conn->prepare("UPDATE employee SET verification_token = :token, is_verified = :is_verified WHERE user_id = :user_id");
            $stmt_up->bindParam(':token', $token);
            $stmt_up->bindParam(':is_verified', $is_verified);
            $stmt_up->bindParam(':user_id', $user_id);
            $stmt_up->execute();

            // Use EmailSender (PHPMailer) for better diagnostics. Fallback to Mailer if needed is handled internally.
            $emailSender = new EmailSender();
            $sent = $emailSender->sendWelcomeEmail($email, $full, $user_id, $require_verification ? $token : null);
            if (!$sent) {
                // If sending failed, record admin notification and adjust message
                try {
                    if ($require_verification) $ev->createToken($user_id, $token); // ensure token exists
                } catch (Exception $e) {
                    error_log('Failed to create verification token: ' . $e->getMessage());
                }
                $lastErr = $emailSender->getLastError();
                $_SESSION['message'] = 'Account created but verification email failed to send. Check SMTP settings or logs.' . (!empty($lastErr) ? ' Error: ' . htmlspecialchars($lastErr) : '');
                try {
                    $adminNotif = new Notification();
                    $adminNotif->recordNotification('admin', $_SESSION['admin_id'] ?? 0, 'email_failure', 'Verification email failed to send to ' . $email . (!empty($lastErr) ? ' Error: ' . $lastErr : ''));
                } catch (Exception $e) {
                    error_log('Notification record failed: ' . $e->getMessage());
                }
            } else {
                $_SESSION['message'] = 'Account created and verification email sent.';
            }
        }

        $reqManager->markApproved($id);

    } catch (Exception $e) {
        $_SESSION['message'] = 'Error creating account: ' . $e->getMessage();
    }

    header('Location: viewAccountRequests.php'); exit();
}

header('Location: viewAccountRequests.php');
exit();
