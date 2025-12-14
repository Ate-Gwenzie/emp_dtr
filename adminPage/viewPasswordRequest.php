<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/password.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$requestManager = new PasswordRequest();
$requests = $requestManager->getPendingRequests();

$message = null;
if (isset($_SESSION['password_request_message'])) {
    $message = $_SESSION['password_request_message'];
    unset($_SESSION['password_request_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Password Requests</title>
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
        .card-body { 
            padding: 25px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
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
            padding: 8px 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-weight: bold; 
            text-decoration: none; 
            display: inline-block; 
            transition: background-color 0.3s; 
        }
        .btn-success { 
            background-color: #28a745; 
            color: white; 
        }
        .btn-success:hover { 
            background-color: #1e7e34; 
        }
        .btn-danger { 
            background-color: #dc3545; 
            color: white; 
        }
        .btn-danger:hover { 
            background-color: #c82333; 
        }
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 5px; 
            font-weight: 500; 
        }
        .alert-info { 
            background-color: #d1ecf1; 
            color: #0c5460; 
            border: 1px solid #bee5eb; 
        }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

    <div class="app-container">
        <a href="adminMain.php" class="btn btn-secondary mb-3">Back</a>

    <h1>Pending Password Reset Requests (<?php echo count($requests); ?>)</h1>

    <div class="card">
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-info" id="success-alert"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Emp ID</th>
                                <th>Employee Name</th>
                                <th>Email</th>
                                <th>Reason</th>
                                <th>Requested On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No pending password reset requests.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['emp_number']); ?></td>
                                        <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['employee_email']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($request['reason'], 0, 50)) . (strlen($request['reason']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars($request['created_at']); ?></td>
                                        <td>
                                            <form method="POST" action="processPassRequest.php" style="display:inline;" onsubmit="return confirm('Confirming will immediately set the employee\'s new password.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                                                <input type="hidden" name="action" value="confirm">
                                                <button type="submit" class="btn btn-success">✓ Confirm & Apply</button>
                                            </form>
                                            <form method="POST" action="processPassRequest.php" style="display:inline;" onsubmit="return confirm('Deny this request?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['request_id']); ?>">
                                                <input type="hidden" name="action" value="deny">
                                                <button type="submit" class="btn btn-danger">X Deny</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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