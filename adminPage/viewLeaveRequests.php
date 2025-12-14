<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/leave.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$leaveManager = new Leave();
$requests = $leaveManager->getPendingRequests();

$message = null;
if (isset($_SESSION['leave_admin_message'])) {
    $message = $_SESSION['leave_admin_message'];
    unset($_SESSION['leave_admin_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Leave Requests</title>
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
    

    <h1>Pending Leave Requests (<?php echo count($requests); ?>)</h1>

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
                                <th>Dates</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Requested On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No pending leave requests.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $request): 
                                    $duration = date_diff(date_create($request['start_date']), date_create($request['end_date']));
                                    $days = $duration->days + 1;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['emp_number']); ?></td>
                                        <td><?php echo htmlspecialchars($request['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['start_date']) . ' to ' . htmlspecialchars($request['end_date']); ?></td>
                                        <td><?php echo $days . ' day' . ($days > 1 ? 's' : ''); ?></td>
                                        <td><?php echo htmlspecialchars(substr($request['reason'], 0, 40)) . (strlen($request['reason']) > 40 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars(date('M j, H:i', strtotime($request['created_at']))); ?></td>
                                        <td>
                                            <form method="POST" action="processLeaveRequest.php" style="display:inline;" onsubmit="return confirm('Approve leave? This will mark DTR records as On Leave.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="leave_id" value="<?php echo htmlspecialchars($request['leave_id']); ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success">✓ Approve</button>
                                            </form>
                                            <form method="POST" action="processLeaveRequest.php" style="display:inline;" onsubmit="return confirm('Deny leave request?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="leave_id" value="<?php echo htmlspecialchars($request['leave_id']); ?>">
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