<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}

require_once '../classes/database.php';
require_once '../classes/attendance.php'; 

$db = new Database();
$conn = $db->getConnection();
$attendanceManager = new Attendance();

$search = trim($_GET['search'] ?? "");
$month  = trim($_GET['month'] ?? "");

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

if ($stmt->execute()) {
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $employees = [];
}

$records = [];
$filter_message = "Please select a month to view DTR records.";

if (!empty($month)) {
    foreach ($employees as $emp) {
        $emp_records = $attendanceManager->viewAttendance($emp['empid'], $month);
        
        foreach ($emp_records as $record) {
             $records[] = array_merge($record, [
                'employee_id' => $emp['employee_id'],
                'full_name' => $emp['full_name']
             ]);
        }
    }
    $monthName = date('F Y', mktime(0, 0, 0, $month, 1, date('Y')));
    $filter_message = count($records) > 0 ? "Showing records for {$monthName}" : "No records found for {$monthName} matching your search.";
}

usort($records, function($a, $b) {
    if ($a['date'] === $b['date']) {
        return strcmp($a['full_name'], $b['full_name']);
    }
    return strtotime($b['date']) - strtotime($a['date']);
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Daily Time Record</title>
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
            font-weight: 300; }
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
        .filter-form { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 15px; 
            align-items: center; 
            justify-content: center; 
            margin: 30px auto; 
            max-width: 800px; 
            padding: 20px; 
            background-color: #fff5f5; 
            border: 1px solid #f40000; 
            border-radius: 10px; 
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.05); }
        .filter-form input[type="text"], .filter-form select { 
            flex: 1 1 200px; 
            padding: 10px 12px; 
            font-size: 16px; 
            border: 1px solid #ccc; 
            border-radius: 6px; 
        }
        .filter { 
            background-color: #ffc107; 
            color: black; 
            padding: 10px 20px; 
            min-width: 120px; 
        }
        .print-btn-container { 
            text-align: right; 
            margin-bottom: 20px; 
        }
        .print-btn { 
            background-color: #8b0000; 
            color: white; 
            padding: 10px 20px; 
            font-weight: 600; 
            border-radius: 5px; 
            text-decoration: none; 
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
    <a href="adminMain.php" class="btn btn-secondary mb-3">Back</a>
 <h1>Employee Daily Time Record</h1>
<div class="card">
        <div class="card-body">
            <form method="GET" action="" class="filter-form mb-3">
                    <input type="text" class="form-control" name="search" placeholder="Search by name or Employee ID" value="<?php echo htmlspecialchars($search); ?>">
                        <select class="form-select" name="month">
                            <option value="">-- Select Month --</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($month == $i) ? 'selected' : ''; ?>><?php echo date('F', mktime(0, 0, 0, $i, 1)); ?></option>
                            <?php endfor; ?>
                        </select>
                    <button class="btn filter">Search ⌕</button>
            </form>
            
            <?php if (!empty($month)): ?>
            <div class="print-btn-container">
                <a href="printEmployeeDTR.php?month=<?php echo htmlspecialchars($month); ?>&search=<?php echo htmlspecialchars($search); ?>" target="_blank" class="btn print-btn">
                    Print Employee DTR LIST
                </a>
            </div>
            <?php endif; ?>
            
            <p class="text-muted text-center"><?php echo $filter_message; ?></p>
            
            <div class="table-responsive">
                <table class="table table-striped">
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records) || (count($records) == 1 && $records[0]['status'] === 'Please filter by month.')): ?>
                            <tr>
                                <td colspan="8" class="text-center"><?php echo ($month ? 'No attendance records found for the selected month.' : 'Please filter by month.'); ?></td>
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