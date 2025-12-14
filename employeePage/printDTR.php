<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/attendance.php';
require_once '../classes/employee.php';

$attendance = new Attendance();
$employee = new Employee();

$empId = $_SESSION['employee_id'];
$empData = $employee->fetchEmployee($empId);
$month = trim(htmlspecialchars($_GET['month'] ?? date('m')));
$history = $attendance->viewAttendance($empId, $month);

$monthName = date('F Y', mktime(0, 0, 0, $month, 1, date('Y')));
$fullName = htmlspecialchars(($empData['fname_emp'] ?? '') . ' ' . ($empData['lname_emp'] ?? ''));
$employeeNumber = htmlspecialchars($empData['employee_id'] ?? 'N/A');
$position = htmlspecialchars($empData['position'] ?? 'N/A');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print DTR - <?php echo $monthName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
                font-size: 10pt;
                color: #000;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            table {
                page-break-inside: auto;
                width: 100%;
                border-collapse: collapse;
                font-size: 9pt;
            }
            thead {
                display: table-header-group;
                background-color: #eee !important;
            }
            th, td {
                border: 1px solid #000;
                padding: 5px;
            }
            .header-info h2 {
                color: #8b0000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .on-leave {
                background-color: #f0f8ff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-style: italic;
            }
        }

        .print-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border: 1px solid #ccc;
        }
        .header-info {
            border-bottom: 2px solid #8b0000;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .header-info h2 {
            color: #8b0000;
            font-weight: bold;
            text-align: center;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 1rem;
        }
        .info-label {
            font-weight: 600;
        }
        table {
            width: 100%;
        }
        thead {
            background-color: #8b0000;
            color: white;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="print-container">
        <div class="header-info">
            <h2>DAILY TIME RECORD</h2>
            <div class="info-row">
                <span class="info-label">Employee Name:</span>
                <span><?php echo $fullName; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Employee ID:</span>
                <span><?php echo $employeeNumber; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Position:</span>
                <span><?php echo $position; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Period:</span>
                <span><?php echo $monthName; ?></span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time In AM</th>
                        <th>Time Out AM</th>
                        <th>Time In PM</th>
                        <th>Time Out PM</th>
                        <th>Status</th>
                        <th>Confirmed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No attendance records found for this month.</td>
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
                                <td><?php echo htmlspecialchars($record['status'] === 'On Leave' ? 'YES' : ($record['confirmation_status'] ?? 'Draft')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 20px;">
             <p>This document is ready for printing.</p>
             <button onclick="window.close()" class="btn btn-warning">Close Print View</button>
        </div>
    </div>
</body>
</html>