<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/admin.php';
require_once '../classes/email_verification.php';

$admin = new Admin();
$errors = [];
$success = "";

$admin->fname_ad = $_POST['fname_ad'] ?? '';
$admin->lname_ad = $_POST['lname_ad'] ?? '';
$admin->email_ad = $_POST['email_ad'] ?? '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors['general'] = "Invalid request.";
    } else {
        $admin->fname_ad = trim(htmlspecialchars($_POST['fname_ad'] ?? ""));
        $admin->lname_ad = trim(htmlspecialchars($_POST['lname_ad'] ?? ""));
        $admin->email_ad = trim(htmlspecialchars($_POST['email_ad'] ?? ""));
        $pass_ad = trim($_POST['pass_ad'] ?? "");
        $confirm_pass_ad = trim($_POST['confirm_pass_ad'] ?? "");

        if (empty($admin->fname_ad)) {
            $errors['fname_ad'] = "First Name is required.";
        }
        if (empty($admin->lname_ad)) {
            $errors['lname_ad'] = "Last Name is required.";
        }
        if (empty($admin->email_ad)) {
            $errors['email_ad'] = "Email is required.";
        } elseif (!filter_var($admin->email_ad, FILTER_VALIDATE_EMAIL)) {
            $errors['email_ad'] = "Invalid email format.";
        }
        if (empty($pass_ad)) {
            $errors['pass_ad'] = "Password is required.";
        } elseif (strlen($pass_ad) < 6) {
            $errors['pass_ad'] = "Password must be at least 6 characters long.";
        }
        if (empty($confirm_pass_ad)) {
            $errors['confirm_pass_ad'] = "Confirm Password is required.";
        } elseif ($pass_ad !== $confirm_pass_ad) {
            $errors['confirm_pass_ad'] = "Passwords do not match.";
        }

        if (empty($errors)) {
            $conn = $admin->getConnection();  
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin WHERE email_ad = :email");
            $stmt->bindParam(':email', $admin->email_ad);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $errors['email_ad'] = "Email already exists.";
            } else {
                $password_hash = password_hash($pass_ad, PASSWORD_DEFAULT);
                try {
                    $admin->addAdmin($password_hash);
                    $success = "Admin added successfully.";
                    header("Location: viewAdmin.php");
                    exit();
                } catch (Exception $e) {
                    $errors['general'] = "Error adding admin: " . $e->getMessage();
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
    <title>Add Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
       
        body {
            background: #f4f7f6;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

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
            padding: 10px 30px;
            border-radius: 50px;
            font-size: 1.1rem;
            display: block;
            width: 50%;
            margin: 20px auto 0;
        }

        .btn-danger:hover, .delete:hover {
            background-color: #c82333;
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
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header_admin.php'; ?>
<div class="app-container">
    <a href="viewAdmin.php" class="btn btn-secondary mb-3">Back</a>
    <h1>Add Admin</h1>
    <div class="card">
        <div class="card-header">New Admin Registration</div>
        <div class="card-body">
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" id="success-alert"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="mb-3">
                    <label for="fname_ad" class="form-label">✎First Name</label>
                    <input type="text" class="form-control" id="fname_ad" name="fname_ad" value="<?php echo htmlspecialchars($admin->fname_ad); ?>">
                    <?php if (isset($errors['fname_ad'])): ?>
                        <div class="error"><?php echo htmlspecialchars($errors['fname_ad']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="lname_ad" class="form-label">✎Last Name</label>
                    <input type="text" class="form-control" id="lname_ad" name="lname_ad" value="<?php echo htmlspecialchars($admin->lname_ad); ?>">
                    <?php if (isset($errors['lname_ad'])): ?>
                        <div class="error"><?php echo htmlspecialchars($errors['lname_ad']); ?></div>
                    <?php endif; ?>
                </div>
        
                <div class="mb-3">
                    <label for="email_ad" class="form-label">✎Email</label>
                    <input type="email" class="form-control" id="email_ad" name="email_ad" value="<?php echo htmlspecialchars($admin->email_ad); ?>">
                    <?php if (isset($errors['email_ad'])): ?>
                        <div class="error"><?php echo htmlspecialchars($errors['email_ad']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="pass_ad" class="form-label">✎Password</label>
                    <input type="password" class="form-control" id="pass_ad" name="pass_ad">
                    <?php if (isset($errors['pass_ad'])): ?>
                        <div class="error"><?php echo htmlspecialchars($errors['pass_ad']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="confirm_pass_ad" class="form-label">✎Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_pass_ad" name="confirm_pass_ad">
                    <?php if (isset($errors['confirm_pass_ad'])): ?>
                        <div class="error"><?php echo htmlspecialchars($errors['confirm_pass_ad']); ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-danger">Add Admin</button>
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
