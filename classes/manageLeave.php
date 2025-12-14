<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/leave.php';
require_once '../classes/notification.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$leaveManager = new Leave();
$employees = $leaveManager->getEmployeeList();
$errors = [];
$success = "";
try {
    $notificationManager = new Notification();
    $unreadNotifications = count(array_filter($notificationManager->getNotifications('admin', $_SESSION['admin_id'] ?? 0), function($n){ return ($n['is_read']==0); }));
} catch (Exception $e) {
    $unreadNotifications = 0;
}

$employee_id = $_POST['employee_id'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$reason = $_POST['reason'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $employee_id = $_POST['employee_id'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = trim(htmlspecialchars($_POST['reason'] ?? ''));

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request token.";
    } elseif (empty($employee_id) || empty($start_date) || empty($end_date) || empty($reason)) {
        $errors[] = "All fields are required.";
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $errors[] = "Start date cannot be after the end date.";
    } else {
        try {
            $leaveManager->applyLeave($employee_id, $start_date, $end_date, $reason);
            $success = "Leave for Employee ID {$employee_id} from {$start_date} to {$end_date} has been successfully applied (Approved).";
        } catch (Exception $e) {
            $errors[] = "Error applying leave: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employee Leave</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; }
        h1 { 
            text-align: center; 
            margin: 30px 0 20px; 
            color: #8b0000; 
            font-weight: 300; 
        }
        .container { 
            max-width: 600px; 
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
            padding: 10px; height: auto; 
            transition: border-color 0.3s; 
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
    <?php require_once __DIR__ . '/../includes/header_admin.php'; ?>
    <div style="padding: 12px 15px; max-width: 1100px; margin: 24px auto 0;">
        <a class="btn btn-secondary mb-3" href="adminMain.php">Back</a>
    </div>

    <h1>Manage Employee Leave</h1>

    <div class="container">
        <div class="card">
            <div class="card-header">Apply New Leave Record (Admin Approved)</div>
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
                        <label for="employee_id" class="form-label">Select Employee</label>
                        <select class="form-select" id="employee_id" name="employee_id" required>
                            <option value="">-- Select Employee --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo htmlspecialchars($emp['empid']); ?>" <?php echo ($employee_id == $emp['empid'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars("{$emp['employee_id']} - {$emp['fname_emp']} {$emp['lname_emp']}"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason / Type of Leave</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required><?php echo htmlspecialchars($reason); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Apply Leave</button>
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