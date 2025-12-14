<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
}
require_once '../classes/database.php';
require_once '../classes/admin.php';
require_once '../classes/employee.php';
require_once '../classes/email_verification.php';

$errors = [];
$email = $_POST["email"] ?? "";
$password = $_POST["password"] ?? "";

// Load email configuration to determine if verification is required
$cfg = include __DIR__ . '/../config/email_config.php';
$require_verification = $cfg['require_email_verification'] ?? true;

// =========================================================================
// NEW LOGIC: Check for verification status from the URL and set session message
// =========================================================================
if (isset($_GET['verified'])) {
    if ($_GET['verified'] === 'success') {
        // SUCCESS MESSAGE (Requested by User)
        $_SESSION['feedback_message'] = "✅ Account validated, you may log in.";
        $_SESSION['feedback_type'] = "alert-success";
    } elseif ($_GET['verified'] === 'failed' || $_GET['verified'] === 'incomplete') {
        $_SESSION['feedback_message'] = "❌ Verification failed. The link may be invalid, expired, or the account is already verified.";
        $_SESSION['feedback_type'] = "alert-danger";
    } elseif ($_GET['verified'] === 'error') {
        $_SESSION['feedback_message'] = "❌ A technical error occurred during verification. Please contact system support.";
        $_SESSION['feedback_type'] = "alert-danger";
    }
    // Redirect back to login.php to remove the GET parameter from the URL, preventing re-display on refresh
    header('Location: login.php');
    exit();
}
// =========================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim(htmlspecialchars($_POST["email"] ?? ""));
    $password = trim(htmlspecialchars($_POST["password"] ?? ""));

    if (empty($email) || empty($password)) {
        $errors[] = "Email and password are required.";
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM admin WHERE email_ad = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['pass_ad'])) {
            // Admin users are allowed to login without requiring email verification
            $_SESSION['admin_id'] = $admin['adid'];
            $_SESSION['admin_name'] = $admin['fname_ad'] . ' ' . $admin['lname_ad'];
            header("Location: ../adminPage/adminMain.php");
            exit();
        }

        $stmt = $conn->prepare("SELECT * FROM employee WHERE email_emp = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // FIX: The employee verification check was redundant and complex. 
        // Using the 'is_verified' flag from the employee table is the simplest and most reliable way.
        if ($employee && password_verify($password, $employee['pass_emp'])) {
            
            // Assuming the `employee` table correctly has the `is_verified` column (0 or 1)
            $is_verified = (bool)($employee['is_verified'] ?? 0); 

            if ($require_verification && !$is_verified) {
                $errors[] = 'Please verify your email before logging in.';
            } else {
                $_SESSION['employee_id'] = $employee['empid'];
                $_SESSION['employee_name'] = $employee['fname_emp'] . ' ' . $employee['lname_emp'];
                header("Location: ../employeePage/mainpage.php");
                exit();
            }
        }

        $errors[] = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        
        body {
            background: #f4f7f6;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .logout:hover, .back:hover, .exit:hover {
            background-color: #f40000; 
        }

        h1 {
            text-align: center;
            margin: 30px 0 20px;
            color: #8b0000;
            font-weight: 300;
        }

        .login-card {
            max-width: 400px;
            margin: 50px auto;
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

        .btn-warning {
            background-color: #ffc107;
            color: black;
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            display: block;
            width: 100%; 
            margin: 20px auto 10px;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            color: white;
            transform: scale(1.02);
        }

        .forgot-link {
            display: block;
            text-align: center;
            color: #8b0000;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }
        .forgot-link:hover {
            color: #f40000;
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
        .alert-success { /* Added styling for success message */
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .forgotPass{
            text-decoration: none;
            color:black;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
 <h1>Login</h1>
<div class="app-container login-card">
    <div class="card">
        <div class="card-header">Login Details</div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['feedback_message'])): ?>
                <div class="alert <?php echo htmlspecialchars($_SESSION['feedback_type']); ?>" id="feedback-alert">
                    <?php echo htmlspecialchars($_SESSION['feedback_message']); ?>
                </div>
                <?php unset($_SESSION['feedback_message']); ?>
                <?php unset($_SESSION['feedback_type']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['request_status_message'])): ?>
                <div class="alert alert-info" id="success-alert">
                    <?php echo htmlspecialchars($_SESSION['request_status_message']); ?>
                </div>
                <?php unset($_SESSION['request_status_message']); ?>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-warning">Login</button>
            </form>
            <a class="forgotPass" href="changePassRequest.php" class="forgot-link">Forgot Password?</a>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Autohide existing request status message
        const requestAlert = document.getElementById('success-alert');
        if (requestAlert) {
            setTimeout(function() {
                requestAlert.style.display = 'none';
            }, 3000); 
        }
        // Autohide new verification feedback message
        const feedbackAlert = document.getElementById('feedback-alert');
        if (feedbackAlert) {
            setTimeout(function() {
                feedbackAlert.style.display = 'none';
            }, 5000); // Give users a little more time to read the validation success
        }
    });
</script>
</body>
</html>