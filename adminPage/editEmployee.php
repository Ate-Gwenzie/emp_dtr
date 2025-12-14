<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/employee.php';

$employee = new Employee();
$errors = [];
$success = "";

if (isset($_GET['id'])) {
    $empData = $employee->fetchEmployee($_GET['id']);
    if (!$empData) {
        header("Location: viewEmployee.php");
        exit();
    }
    $employee->empid = $empData['empid'];
    $employee->employee_id = $empData['employee_id'];
    $employee->fname_emp = $empData['fname_emp'];
    $employee->lname_emp = $empData['lname_emp'];
    $employee->email_emp = $empData['email_emp'];
    $employee->position = $empData['position'];
    $employee->timein_am = $empData['timein_am'];
    $employee->timeout_am = $empData['timeout_am'];
    $employee->timein_pm = $empData['timein_pm'];
    $employee->timeout_pm = $empData['timeout_pm'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee->empid = $_POST['empid'];
    $employee->employee_id = trim(htmlspecialchars($_POST['employee_id'] ?? ""));
    $employee->fname_emp = trim(htmlspecialchars($_POST['fname_emp'] ?? ""));
    $employee->lname_emp = trim(htmlspecialchars($_POST['lname_emp'] ?? ""));
    $employee->email_emp = trim(htmlspecialchars($_POST['email_emp'] ?? ""));
    $employee->position = trim(htmlspecialchars($_POST['position'] ?? ""));
    
    $employee->pass_emp = null; 
    
    $employee->timein_am = trim(htmlspecialchars($_POST['timein_am'] ?? ""));
    $employee->timeout_am = trim(htmlspecialchars($_POST['timeout_am'] ?? ""));
    $employee->timein_pm = trim(htmlspecialchars($_POST['timein_pm'] ?? ""));
    $employee->timeout_pm = trim(htmlspecialchars($_POST['timeout_pm'] ?? ""));

    if (empty($employee->employee_id) || empty($employee->fname_emp) || empty($employee->lname_emp) || empty($employee->email_emp) || empty($employee->position)) {
        $errors[] = "Required fields are missing.";
    } else {
        try {
            $employee->editEmployee();
            
            $success = "Employee details updated successfully.";
            header("location: viewEmployee.php");
            exit();

        } catch (Exception $e) {
            $errors[] = "Error updating employee: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f7f6;
            padding-bottom: 50px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        /* Header styles centralized to assets/css/app.css */

        .logout:hover, .back:hover, .exit:hover {
            background-color: #f40000; 
        }

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
            margin-top: 30px;
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

        .btn-primary {
            background-color: #ffc107;
            color: black;
            font-weight: bold;
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
        }

        .btn-primary:hover {
            background-color: #e0a800;
            color: white;
            transform: scale(1.02);
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 10px;
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
        .alert-warning-reset {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .error {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="app-container">
    <a href="viewEmployee.php" class="btn btn-secondary mb-3">Back</a>
    <h1 class="text-center">Edit Employee Details</h1>
    <div class="row justify-content-center">
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-header">Edit Employee</div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success" id="success-alert"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="empid" value="<?php echo htmlspecialchars($employee->empid); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3 text-muted">Personal & Login Details</h5>
                                <div class="mb-3">
                                    <label for="employee_id" class="form-label">Employee ID</label>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?php echo htmlspecialchars($employee->employee_id); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="fname_emp" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="fname_emp" name="fname_emp" value="<?php echo htmlspecialchars($employee->fname_emp); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="lname_emp" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lname_emp" name="lname_emp" value="<?php echo htmlspecialchars($employee->lname_emp); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email_emp" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email_emp" name="email_emp" value="<?php echo htmlspecialchars($employee->email_emp); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($employee->position); ?>" required>
                                </div>
                                <p class="text-muted mt-4">Note: Passwords can only be reset by the employee using the 'Forgot Password' request.</p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-3 text-muted">Daily Time Schedule</h5>
                                <div class="mb-3">
                                    <label for="timein_am" class="form-label">Time In AM</label>
                                    <input type="time" class="form-control" id="timein_am" name="timein_am" value="<?php echo htmlspecialchars($employee->timein_am); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="timeout_am" class="form-label">Time Out AM</label>
                                    <input type="time" class="form-control" id="timeout_am" name="timeout_am" value="<?php echo htmlspecialchars($employee->timeout_am); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="timein_pm" class="form-label">Time In PM</label>
                                    <input type="time" class="form-control" id="timein_pm" name="timein_pm" value="<?php echo htmlspecialchars($employee->timein_pm); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="timeout_pm" class="form-label">Time Out PM</label>
                                    <input type="time" class="form-control" id="timeout_pm" name="timeout_pm" value="<?php echo htmlspecialchars($employee->timeout_pm); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Update Employee</button>
                        </div>
                    </form>
                </div>
            </div>
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