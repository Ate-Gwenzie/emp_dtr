<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}

require_once '../classes/employee.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = null;
if (isset($_SESSION['delete_message'])) {
    $message = $_SESSION['delete_message'];
    unset($_SESSION['delete_message']);
}
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

$employee = new Employee();
$search = trim($_GET['search'] ?? "");
$errors = [];
$employees = [];

try {
    $employees = $employee->viewEmployee($search);

    if (isset($employees['error'])) {
        $errors[] = $employees['error'];
        $employees = [];
    }
} catch (Exception $e) {
    $errors[] = "Error fetching employees: " . $e->getMessage();
    $employees = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Employees</title>
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
            max-width: 1200px; 
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

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        thead {
            background-color: #8b0000;
            color: white;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #fff5f5; 
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

        .btn-primary, .btn-warning, .edit, .add-employee, .add-admin, .filter, .btn-action-primary {
            background-color: #ffc107;
            color: black;
        }

        .btn-primary:hover, .btn-warning:hover, .edit:hover, .add-employee:hover, .add-admin:hover, .filter:hover, .btn-action-primary:hover {
            background-color: #e0a800;
            color: white;
            transform: scale(1.02);
        }

        .btn-danger, .delete {
            background-color: #dc3545; 
            color: white;
        }

        .btn-danger:hover, .delete:hover {
            background-color: #c82333;
            transform: scale(1.02);
        }
        
        form.mb-3 {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        form.mb-3 .form-control {
            max-width: 400px;
        }
        .add-employee {
            display: block;
            background-color: #ffc107;
            color: black;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 20px;
            width: 250px;
            text-align: center;
        }
        .add-employee:hover {
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
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .view-action-group {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

    <div class="app-container">
        <a href="adminMain.php" class="btn btn-secondary mb-3">Back</a>

    <h1>View Employees</h1>

    <div class="card">
        <div class="card">
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-info" id="success-alert"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                    </div>
                <?php endif; ?>

                <form method="GET" action="" class="mb-3">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or ID" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-warning" type="submit">Search ⌕</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Position</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No employees found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $emp): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($emp['employee_id'] ?? $emp['empid']); ?></td>
                                        <td><?php echo htmlspecialchars(($emp['fname_emp'] ?? '') . ' ' . ($emp['lname_emp'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars($emp['email_emp'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($emp['position'] ?? ''); ?></td>
                                        <td class="view-action-group">
                                            <a href="editEmployee.php?id=<?php echo htmlspecialchars($emp['empid']); ?>" class="btn edit">✎ Edit</a>
                                            <form method="POST" action="deleteEmployee.php" style="display:inline;" onsubmit="return confirmDelete();">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($emp['empid']); ?>">
                                                <button type="submit" class="btn delete">🗑 Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <a href="addEmployee.php" class="add-employee">➕ Add Employee</a>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this employee? This action cannot be undone.");
        }
        
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