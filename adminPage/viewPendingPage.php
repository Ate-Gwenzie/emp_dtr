<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/attendance.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$attendanceManager = new Attendance();
$pendingDTRs = $attendanceManager->getPendingDTRs();

$message = null;
if (isset($_SESSION['dtr_confirmation_message'])) {
    $message = $_SESSION['dtr_confirmation_message'];
    unset($_SESSION['dtr_confirmation_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending DTR Confirmations</title>
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

        .btn-success { background-color: #28a745; color: white; }
        .btn-success:hover { background-color: #1e7e34; transform: scale(1.02); }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-danger:hover { background-color: #c82333; transform: scale(1.02); }

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

    <h1>Pending DTR Confirmations (<?php echo count($pendingDTRs); ?>)</h1>

    <div class="card">
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
                                <th>Name</th>
                                <th>Date</th>
                                <th>Time In AM</th>
                                <th>Time Out AM</th>
                                <th>Time In PM</th>
                                <th>Time Out PM</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingDTRs)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No pending DTRs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingDTRs as $dtr): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($dtr['emp_number']); ?></td>
                                        <td><?php echo htmlspecialchars($dtr['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($dtr['date']); ?></td>
                                        <td><?php echo htmlspecialchars($dtr['time_in_am'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($dtr['time_out_am'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($dtr['time_in_pm'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($dtr['time_out_pm'] ?? 'N/A'); ?></td>
                                        <td>
                                            <form method="POST" action="processDTRConfirm.php" style="display:inline;" onsubmit="return confirm('Confirm DTR for <?php echo htmlspecialchars($dtr['full_name']); ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="attendance_id" value="<?php echo htmlspecialchars($dtr['id']); ?>">
                                                <input type="hidden" name="action" value="confirm">
                                                <button type="submit" class="btn btn-success">✓ Confirm</button>
                                            </form>
                                            <form method="POST" action="processDTRConfirm.php" style="display:inline;" onsubmit="return confirm('Reject DTR for <?php echo htmlspecialchars($dtr['full_name']); ?>? This will set it back to Draft.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="attendance_id" value="<?php echo htmlspecialchars($dtr['id']); ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger">X Reject</button>
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