<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location:../user/login.php");
    exit();
}
require_once '../classes/leave.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = "";
$start_date = $_POST['start_date'] ?? date('Y-m-d');
$end_date = $_POST['end_date'] ?? date('Y-m-d');
$reason = $_POST['reason'] ?? '';
$employee_id = $_SESSION['employee_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $start_date_post = $_POST['start_date'] ?? '';
    $end_date_post = $_POST['end_date'] ?? '';
    $reason_post = trim(htmlspecialchars($_POST['reason'] ?? ''));

    if ($csrf_token !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request token.";
    } elseif (empty($start_date_post) || empty($end_date_post) || empty($reason_post)) {
        $errors[] = "All fields are required.";
    } elseif (strtotime($start_date_post) < strtotime(date('Y-m-d'))) {
        $errors[] = "Leave start date cannot be in the past.";
    } elseif (strtotime($start_date_post) > strtotime($end_date_post)) {
        $errors[] = "Start date cannot be after the end date.";
    } else {
        $leaveManager = new Leave();
        try {
            $leaveManager->createRequest($employee_id, $start_date_post, $end_date_post, $reason_post);
            $_SESSION['leave_request_message'] = "Leave request submitted successfully for " . $start_date_post . " to " . $end_date_post . ". Awaiting Admin approval.";
            header("Location: mainpage.php");
            exit();
        } catch (Exception $e) {
            $errors[] = "Error submitting request: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Leave</title>
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
    <h1>Request Leave</h1>

    <div class="container">
        <div class="card">
            <div class="card-header">Submit Leave Request</div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason / Type of Leave</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required><?php echo htmlspecialchars($reason); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>