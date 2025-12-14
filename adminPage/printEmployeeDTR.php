<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}

require_once '../classes/database.php';
require_once '../classes/attendance.php'; 
require_once '../classes/employee.php'; 

$db = new Database();
$conn = $db->getConnection();
$attendanceManager = new Attendance();
$employeeManager = new Employee();

$search = trim($_GET['search'] ?? "");
$month  = trim($_GET['month'] ?? "");

if (empty($month)) {
    die("Error: Please select a month to print the DTR records.");
}

$query = " SELECT empid, employee_id, CONCAT(fname_emp, ' ', lname_emp) AS full_name FROM employee WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (fname_emp LIKE :search1 OR lname_emp LIKE :search2 OR employee_id LIKE :search3)";
}

$query .= " ORDER BY lname_emp ASC";
$stmt = $conn->prepare($query);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
    $stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
    $stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
}

$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$records = [];
foreach ($employees as $emp) {
    $emp_records = $attendanceManager->viewAttendance($emp['empid'], $month);
    
    foreach ($emp_records as $record) {
         $records[] = array_merge($record, [
            'employee_id' => $emp['employee_id'],
            'full_name' => $emp['full_name']
         ]);
    }
}


usort($records, function($a, $b) {
    if ($a['date'] === $b['date']) {
        return strcmp($a['full_name'], $b['full_name']);
    }
    return strtotime($b['date']) - strtotime($a['date']);
});

$monthName = date('F Y', mktime(0, 0, 0, $month, 1, date('Y')));
$reportTitle = "Consolidated DTR Report for {$monthName}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $reportTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 0;
            margin: 0;
            color: #333;
        }
        .print-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .header-info h2 {
            color: #8b0000;
            font-weight: bold;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
        }
        thead {
            background-color: #8b0000;
            color: white;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 8px;
        }
        .on-leave {
            background-color: #e6f7ff; 
            font-style: italic;
        }
        .filter-details {
            margin-bottom: 20px;
            text-align: center;
        }
        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        
        @media print {
            .print-container {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            body {
                font-size: 9pt;
                color: #000;
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
            }
            table {
                page-break-inside: auto;
            }
            thead {
                display: table-header-group;
                background-color: #eee !important;
            }
            th, td {
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="print-container">
        <div class="header-info">
            <h2><?php echo $reportTitle; ?></h2>
        </div>

        <div class="filter-details">
            Report Generated on: <?php echo date('Y-m-d H:i:s'); ?><br>
            Filter Applied: **Search Term:** <?php echo empty($search) ? 'None' : htmlspecialchars($search); ?>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Date</th>
                        <th>Time In AM</th>
                        <th>Time Out AM</th>
                        <th>Time In PM</th>
                        <th>Time Out PM</th>
                        <th>Status</th>
                        <th>DTR Confirmed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($records)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No records found matching the criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($records as $record): ?>
                            <tr class="<?php echo ($record['status'] === 'On Leave' ? 'on-leave' : ''); ?>">
                                <td><?php echo htmlspecialchars($record['employee_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($record['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($record['date'] ?? 'N/A'); ?></td>
                                <td><?php echo ($record['status'] === 'On Leave' ? '---' : htmlspecialchars($record['time_in_am'] ?? 'N/A')); ?></td>
                                <td><?php echo ($record['status'] === 'On Leave' ? '---' : htmlspecialchars($record['time_out_am'] ?? 'N/A')); ?></td>
                                <td><?php echo ($record['status'] === 'On Leave' ? '---' : htmlspecialchars($record['time_in_pm'] ?? 'N/A')); ?></td>
                                <td><?php echo ($record['status'] === 'On Leave' ? '---' : htmlspecialchars($record['time_out_pm'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars($record['status']); ?></td>
                                <td><?php echo htmlspecialchars($record['confirmation_status'] ?? 'Draft'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="no-print">
             <button onclick="window.close()" class="btn print-btn">Close Print View</button>
        </div>
    </div>
</body>
</html>