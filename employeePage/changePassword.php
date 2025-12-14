<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location:../user/login.php");
    exit();
}
require_once '../classes/employee.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = "";
$employee = new Employee();
$empid = $_SESSION['employee_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $old_password = trim(htmlspecialchars($_POST['old_password'] ?? ''));
    $new_password = trim(htmlspecialchars($_POST['new_password'] ?? ''));
    $confirm_password = trim(htmlspecialchars($_POST['confirm_password'] ?? ''));

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request token.";
    } elseif (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match.";
    } else {
        try {
            if ($employee->updatePassword($empid, $old_password, $new_password)) {
                $success = "Your password has been changed successfully!";
                session_regenerate_id(true); 
            } else {
                $errors[] = "The old password you entered is incorrect.";
            }
        } catch (Exception $e) {
            $errors[] = "Error changing password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
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
            max-width: 500px; 
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
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header_employee.php'; ?>
<div class="app-container">
    <a href="mainpage.php" class="btn btn-secondary mb-3">Back</a>

    <h1>Change Password</h1>

    <div class="container">
        <div class="card">
            <div class="card-header">Self-Service Password Update</div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success" id="success-alert"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="mb-3">
                        <label for="old_password" class="form-label">Old Password</label>
                        <input type="password" class="form-control" id="old_password" name="old_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password (Min 6 characters)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Change Password</button>
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
        });
    </script>
</body>
</html>