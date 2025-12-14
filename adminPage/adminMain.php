<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/admin.php';
require_once '../classes/employee.php';
require_once '../classes/earlytimeoutrequest.php';
require_once '../classes/attendance.php';
require_once '../classes/password.php'; 
require_once '../classes/leave.php'; 
require_once '../classes/notification.php';
require_once '../classes/analytics.php';

$admin = new Admin();
$employee = new Employee();
$requestManager = new EarlyTimeoutRequest();
$attendanceManager = new Attendance();
$passwordRequestManager = new PasswordRequest(); 
$leaveManager = new Leave();
$notificationManager = new Notification();
$analyticsManager = new Analytics();

$adminCount = $admin->countAdmins();
$employeeCount = $employee->countEmployees();
$pendingDTRCount = count($attendanceManager->getPendingDTRs()); 
$pendingRequestCount = count($requestManager->getPendingRequests());
$pendingLeaveCount = count($leaveManager->getPendingRequests());
$pendingPasswordCount = count($passwordRequestManager->getPendingRequests());
$unreadNotificationCount = $notificationManager->countUnreadNotifications('admin', $_SESSION['admin_id']); 

$lateArrivals = $analyticsManager->countLateArrivalsLast30Days(); 
$earlyExits = $analyticsManager->countEarlyExitsLast30Days(); 

$totalPending = $pendingDTRCount + $pendingRequestCount + $pendingLeaveCount + $pendingPasswordCount;

$main_metrics = [
    ['title' => 'Total Admins', 'count' => $adminCount, 'link' => 'viewAdmin.php', 'color' => '#8b0000', 'icon' => '👤'],
    ['title' => 'Total Employees', 'count' => $employeeCount, 'link' => 'viewEmployee.php', 'color' => '#ffc107', 'text_color' => 'black', 'icon' => '👥'],
    ['title' => 'Late Arrivals (30d)', 'count' => $lateArrivals, 'link' => '#', 'color' => '#dc3545', 'icon' => '🚨'],
    ['title' => 'Early Exits (30d)', 'count' => $earlyExits, 'link' => '#', 'color' => '#007bff', 'icon' => '⏱️'],
];

$sidebar_actions = [
    ['label' => 'DTR Confirmations', 'link' => 'viewPendingPage.php', 'count' => $pendingDTRCount, 'pending' => $pendingDTRCount > 0],
    ['label' => 'Early Clock-out Requests', 'link' => 'viewEarlyTime.php', 'count' => $pendingRequestCount, 'pending' => $pendingRequestCount > 0],
    ['label' => 'Leave Requests', 'link' => 'viewLeaveRequests.php', 'count' => $pendingLeaveCount, 'pending' => $pendingLeaveCount > 0],
    ['label' => 'Password Reset Requests', 'link' => 'viewPasswordRequest.php', 'count' => $pendingPasswordCount, 'pending' => $pendingPasswordCount > 0],
    ['label' => 'System Notifications', 'link' => 'viewNotifications.php', 'count' => $unreadNotificationCount, 'pending' => $unreadNotificationCount > 0],
    ['label' => 'View All DTR Report', 'link' => 'viewEmployeeDaily.php', 'count' => 0, 'pending' => false],
];

$js_hourly_data = json_encode(array_values($analyticsManager->getHourlyActivityData()));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <style>
        
        :root {
            --sys-red: #8b0000;
            --sys-red-light: #dc3545;
            --sys-yellow: #ffc107;
            --sys-dark-bg: #2c3e50;
            --sys-text-muted: #6c757d;
            --sys-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --sys-radius: 8px;
        }
        body {
            background: #f0f2f5; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 50px;
            z-index: 1030;
            background-color: var(--sys-red);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .main-header .logo {
            font-size: 1.3rem;
            font-weight: 600;
            margin-left: 250px;
            letter-spacing: 0.5px;
            display:flex;
            align-items:center;
            gap:5px;
        }
        .main-header .main-logo { width:34px; height:34px; object-fit:contain; border-radius:6px; box-shadow: 0 2px 6px rgba(0,0,0,0.12); }
        .main-header .user-info {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }
        .main-header .logout {
            color: var(--sys-yellow);
            text-decoration: none;
            padding: 5px 10px;
            border: 1px solid var(--sys-yellow);
            border-radius: 4px;
            margin-left: 15px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .main-header .logout:hover {
            background-color: var(--sys-yellow);
            color: var(--sys-red);
        }
        .main-sidebar {
            width: 250px;
            position: fixed;
            top: 0; 
            bottom: 0;
            left: 0;
            z-index: 1000;
            background-color: var(--sys-dark-bg); 
            color: white;
            padding-top: 50px; 
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            color: #c0c0c0;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            color: white;
            background: #343a40;
            border-left-color: var(--sys-yellow);
        }
        
        .sidebar-menu .pending-indicator {
            background-color: var(--sys-red-light);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
            line-height: 1;
        }
        .content-wrapper {
            margin-left: 250px; 
            padding: 30px;
            width: calc(100% - 250px);
            box-sizing: border-box;
            padding-top: 80px; 
        }
        .content-header h1 {
            color: #333;
            font-weight: 400;
            margin-bottom: 25px;
        }
        .info-box {
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); 
            color: white;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .info-box:hover {
             transform: translateY(-3px);
             box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        .info-box-inner {
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .info-box-text {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }
        .info-box-number {
            font-size: 2.2rem;
            font-weight: 700;
            line-height: 1;
            margin-top: 5px;
        }
        .info-box-icon {
            font-size: 2.5rem;
            opacity: 0.7;
        }
        .info-box-link {
            display: block;
            background: rgba(0, 0, 0, 0.15);
            color: inherit;
            padding: 5px 15px;
            text-align: center;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .bg-red-system { background: linear-gradient(135deg, #a00000 0%, var(--sys-red) 100%); }
        .bg-danger { background: linear-gradient(135deg, #e3342f 0%, var(--sys-red-light) 100%); }
        .bg-blue-system { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
        .bg-yellow-system { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #333; }
        .bg-yellow-system .info-box-number, 
        .bg-yellow-system .info-box-icon,
        .bg-yellow-system .info-box-text { color: var(--sys-dark-bg); } 
        .bg-yellow-system .info-box-link { color: var(--sys-dark-bg); }
    
        .chart-widget {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--sys-shadow);
            margin-bottom: 30px;
        }
        .chart-title {
            color: var(--sys-red);
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        @media (max-width: 992px) {
            .main-header .logo {
                margin-left: 0;
                font-size: 1.1rem;
            }

            .main-sidebar {
                display: none;
            }

            .content-wrapper {
                margin-left: 0;
                width: 100%;
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .row > .col-lg-3 {
                 flex: 0 0 50%;
                 max-width: 50%;
            }
        }

        @media (max-width: 576px) {
            .row > .col-lg-3,
            .row > .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="main-header">
        <span class="logo"><img src="/emp_dtr/logo.png" class="main-logo" alt="DTR Logo" /><span>E-DTR SYSTEM</span></span>
        <div class="user-info">
            <?php echo "Welcome, " . htmlspecialchars($_SESSION['admin_name'] ?? 'System Admin'); ?>
            <a href="viewNotifications.php" title="Notifications" style="margin-left:12px;color:var(--sys-yellow);text-decoration:none;font-weight:700">🔔 <?php echo $unreadNotificationCount > 0 ? "<span class='notif-count' style='background:#ffc107;color:#000;padding:2px 6px;border-radius:999px;font-weight:700;margin-left:6px'>" . intval($unreadNotificationCount) . "</span>" : ''; ?></a>
            <a class="logout" href="../user/logout.php">Log Out</a>
        </div>
    </div>

    <div class="main-sidebar">
        <h3 style="padding: 15px; margin: 0; color: #ffc107; font-size: 1.1rem; border-bottom: 1px solid #343a40;">DTR Admin Panel</h3>
        <ul class="sidebar-menu">
            <li><a href="adminMain.php" class="active">Dashboard Home</a></li>
            
            <li style="padding: 10px 15px; font-weight: bold; color: #999; font-size: 0.85rem;">— User Management</li>
            <li><a href="viewAdmin.php">Admin Accounts</a></li>
            <li><a href="viewEmployee.php">Employee Accounts</a></li>
            
            <li style="padding: 10px 15px; font-weight: bold; color: #999; font-size: 0.85rem;">— Daily Review & Actions</li>
            <?php foreach ($sidebar_actions as $action): ?>
                <li class="<?php echo (basename($_SERVER['PHP_SELF']) == basename($action['link']) ? 'active' : ''); ?>"> 
                    <a href="<?php echo htmlspecialchars($action['link']); ?>">
                        <?php echo htmlspecialchars($action['label']); ?>
                        <?php if ($action['pending']): ?>
                            <span class="pending-indicator"><?php echo $action['count']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="content-wrapper">
        <div class="content-header">
            <h1 class="text-secondary">System Overview</h1>
        </div>
        
        <?php if ($totalPending > 0): ?>
            <div class="alert alert-danger text-center" role="alert">
                <span style="font-size: 1.1rem; font-weight: bold;">🔔 You have <?php echo $totalPending; ?> critical actions requiring review.</span>
            </div>
        <?php endif; ?>

        <div class="row">
            <?php foreach ($main_metrics as $i => $metric): 
                $bgColor = $i === 0 ? 'bg-red-system' : ($i === 1 ? 'bg-yellow-system' : ($i === 2 ? 'bg-danger' : 'bg-blue-system'));
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="info-box <?php echo $bgColor; ?>">
                    <div class="info-box-inner">
                        <div>
                            <div class="info-box-text"><?php echo htmlspecialchars($metric['title']); ?></div>
                            <div class="info-box-number"><?php echo htmlspecialchars($metric['count']); ?></div>
                        </div>
                        <div class="info-box-icon"><?php echo $metric['icon']; ?></div>
                    </div>
                    <a href="<?php echo htmlspecialchars($metric['link']); ?>" class="info-box-link">
                        <?php echo ($i < 2 ? 'Manage Accounts' : 'View Analytics'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row">
            <div class="col-12">
                <h3 class="chart-title">Employee Activity Chart</h3>
                <div class="chart-widget">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>
        </div>

    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');
        
        const activityData = <?php echo $js_hourly_data; ?>;
        
        const labels = Array.from({length: 24}, (_, i) => {
            if (i === 0) return '12 AM';
            if (i === 12) return '12 PM';
            return (i < 12) ? `${i} AM` : `${i - 12} PM`;
        });

        const performanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Clock Actions',
                    data: activityData,
                    backgroundColor: 'rgba(139, 0, 0, 0.7)', 
                    borderColor: '#8b0000',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Hour of Day (Last 30 Days)',
                            color: '#333'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Actions (In/Out)',
                            color: '#333'
                        },
                        ticks: {
                            stepSize: 1 
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false 
                    },
                    title: {
                        display: true,
                        text: 'Employee Activity Distribution',
                        color: '#333',
                        font: {
                            size: 16
                        }
                    }
                }
            }
        });
    });
</script>
</body>
</html>
