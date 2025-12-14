<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/admin.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = null;
if (isset($_SESSION['delete_message'])) {
    $message = $_SESSION['delete_message'];
    unset($_SESSION['delete_message']);
}

$admin = new Admin();
$admins = $admin->viewAdmin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Admins</title>
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
            max-width: 1000px; 
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
        .add-admin {
            display: block;
            width: 200px;
            height: 40px;
            margin: 20px auto;
            padding: 10px;
            font-size: 16px;
            color: black;
            background-color: #ffc107;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            text-align: center;
            transition: background 0.3s;
        }
        .add-admin:hover {
            background-color: #e0a800;
            color: white;
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
        .view-action-group {
            display: flex;
            gap: 10px;
        }
        .view-action-group .edit {
            background-color: #ffc107;
            color: black;
            margin-right: 5px;
        }
        .view-action-group .edit:hover {
            background-color: #e0a800;
            color: white;
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

    <div class="app-container">
        <a href="adminMain.php" class="btn btn-secondary mb-3">Back</a>

    <h1>View Admins</h1>

    <div class="card">
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-info" id="success-alert"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $ad): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ad['adid']); ?></td>
                                <td><?php echo htmlspecialchars(($ad['fname_ad'] ?? '') . ' ' . ($ad['lname_ad'] ?? '')); ?></td>
                                <td><?php echo htmlspecialchars($ad['email_ad'] ?? ''); ?></td>
                                <td class="view-action-group">
                                    <a href="editAdmin.php?id=<?php echo htmlspecialchars($ad['adid']); ?>" class="btn edit">✎ Edit</a>
                                    <form method="POST" action="deleteAdmin.php" style="display:inline;" onsubmit="return confirmDelete();">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="ad_id" value="<?php echo htmlspecialchars($ad['adid']); ?>">
                                        <button type="submit" class="btn delete">⌦ Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a class="add-admin" href="addAdmin.php">Add Admin +👤</a>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this admin? This action cannot be undone.");
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