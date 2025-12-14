<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/attendance.php';

$attendance = new Attendance();
$month = trim(htmlspecialchars($_GET['month'] ?? date('m')));
$history = $attendance->viewAttendance($_SESSION['employee_id'], $month);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>      
        body {
            background: #f4f7f6;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        /*Header styles centralized to assets/css/app.css */
        h1 {
            text-align: center;
            margin: 30px 0 20px;
            color: #8b0000;
            font-weight: 300;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
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
        
        .filter-form {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #f9f9f9;
            border-radius: 8px;
            background-color: #fff5f5; 
        }

        .input-group-text {
            background-color: #ffc107; 
            color: black;
            border: 1px solid #ffc107;
            font-weight: bold;
        }
        
        .form-group {
            flex: 1; 
            min-width: 150px;
        }

        .table {
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
        
        .on-leave {
            background-color: #e6f7ff; 
            font-style: italic;
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

        .btn-warning {
            background-color: #ffc107; 
            color: black;
            font-weight: bold;
            transition: background-color 0.3s;
            padding: 10px 20px;
            height: 44px;
            border-radius: 5px;
        }

        .btn-warning:hover {
            background-color: #e0a800;
            color: white;
            transform: none;
        }
        
        .print-btn-container {
            text-align: right;
            margin-top: -10px; 
            margin-bottom: 20px;
        }
        .print-btn {
            background-color: #8b0000;
            color: white;
            padding: 10px 20px;
            font-weight: 600;
        }
        .print-btn:hover {
            background-color: #f40000;
            color: white;
        }

    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="app-container">
    <h1>Attendance History</h1>

    <div class="container">
        <div class="card">
            <div class="card-header">Monthly Attendance Review</div>
            <div class="card-body">
                <form method="GET" action="" class="filter-form">
                    <div class="form-group">
                        <label for="month" class="form-label visually-hidden">Select Month</label>
                        <div class="input-group">
                            <label for="month" class="input-group-text">Month</label>
                            <select class="form-select" id="month" name="month">
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <?php $m = str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                    <option value="<?php echo $m; ?>" <?php echo ($month == $m) ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $i, 1)); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-warning" type="submit">Search</button>
                </form>
                
                <div class="print-btn-container">
                    <a href="printDTR.php?month=<?php echo htmlspecialchars($month); ?>" target="_blank" class="btn print-btn">
                        Print DTR
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In AM</th>
                                <th>Time Out AM</th>
                                <th>Time In PM</th>
                                <th>Time Out PM</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No attendance records found for this month.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history as $record): ?>
                                    <tr class="<?php echo ($record['status'] === 'On Leave' ? 'on-leave' : ''); ?>">
                                        <td><?php echo htmlspecialchars($record['date']); ?></td>
                                        <td><?php echo ($record['status'] === 'On Leave' ? '---' : htmlspecialchars($record['time_in_am'] ?? 'N/A')); ?></td>
                                        <td><?php echo ($record['status'] === 'On Leave' ? '---' : htmlspecialchars($record['time_out_am'] ?? 'N/A')); ?></td>
                                        <td><?php echo ($record['status'] === 'On Leave' ? '---' : htmlspecialchars($record['time_in_pm'] ?? 'N/A')); ?></td>
                                        <td><?php echo ($record['status'] === 'On Leave' ? '---' : htmlspecialchars($record['time_out_pm'] ?? 'N/A')); ?></td>
                                        <td><?php echo htmlspecialchars($record['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>