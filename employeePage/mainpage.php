<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location:../user/login.php");
    exit();
}
require_once '../classes/employee.php';
require_once '../classes/attendance.php';
require_once '../classes/earlytimeoutrequest.php';
require_once '../classes/leave.php'; 
require_once '../classes/notification.php';

date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$employee = new Employee();
$attendance = new Attendance();
$earlyRequest = new EarlyTimeoutRequest(); 
$empData = $employee->fetchEmployee($_SESSION['employee_id']);
try {
    $notificationManager = new Notification();
    $unreadNotificationCount = count(array_filter($notificationManager->getNotifications('employee', $_SESSION['employee_id']), function($n){ return ($n['is_read']==0); }));
} catch (Exception $e) {
    $unreadNotificationCount = 0;
}
$lastAttendance = $attendance->getLastAttendance($_SESSION['employee_id']);
$errors = [];
$success = "";
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

if (isset($_SESSION['dtr_status_message'])) {
    $success = $_SESSION['dtr_status_message'];
    unset($_SESSION['dtr_status_message']);
}
if (isset($_SESSION['leave_request_message'])) { 
    $success = $_SESSION['leave_request_message'];
    unset($_SESSION['leave_request_message']);
}

$am_schedule_valid = ($empData['timein_am'] !== '00:00:00' || $empData['timeout_am'] !== '00:00:00');
$pm_schedule_valid = ($empData['timein_pm'] !== '00:00:00' || $empData['timeout_pm'] !== '00:00:00');
$is_pm_shift_only = !$am_schedule_valid && $pm_schedule_valid;
$is_am_shift_only = $am_schedule_valid && !$pm_schedule_valid;

$is_dtr_pending = $lastAttendance && $lastAttendance['date'] == $currentDate && ($lastAttendance['confirmation_status'] === 'Pending');
$is_dtr_confirmed = $lastAttendance && $lastAttendance['date'] == $currentDate && ($lastAttendance['confirmation_status'] === 'Confirmed');


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request.";
    } else {
        $action = $_POST['action'] ?? '';
        $timeInAm = $empData['timein_am'];
        $timeOutAm = $empData['timeout_am'];
        $timeInPm = $empData['timein_pm'];
        $timeOutPm = $empData['timeout_pm'];
        
        $isEarly = false;
        if ($action == 'time_out_am') {
             $scheduled_time_out_sec = strtotime($timeOutAm);
             $early_threshold_sec = $scheduled_time_out_sec - (30 * 60);
             if (strtotime($currentTime) < $early_threshold_sec) {
                 $isEarly = true;
             }
        } elseif ($action == 'time_out_pm') {
             $scheduled_time_out_sec = strtotime($timeOutPm);
             $early_threshold_sec = $scheduled_time_out_sec - (30 * 60);
             if (strtotime($currentTime) < $early_threshold_sec) { 
                 $isEarly = true;
             }
        }

        if ($isEarly) {
            $errors[] = "Early Time-out detected. Please use the modal to submit a request.";
        } else {
            try {
                if ($action == 'time_in_am') {
                    if ($lastAttendance && $lastAttendance['date'] == $currentDate && !empty($lastAttendance['time_in_am'])) {
                        $errors[] = "Already timed in for AM today.";
                    } else {
                        $isLate = strtotime($currentTime) > strtotime($timeInAm);
                        $attendance->addAttendance($_SESSION['employee_id'], $currentDate, $currentTime, null, null, null, 'Present');
                        $success = "Timed in for AM successfully, " . ($isLate ? "but late." : "on time.");
                    }
                } elseif ($action == 'time_out_am') {
                    if (!$lastAttendance || $lastAttendance['date'] != $currentDate || empty($lastAttendance['time_in_am'])) {
                        $errors[] = "Must time in AM first.";
                    } elseif (strtotime($currentTime) < strtotime($timeOutAm) - 30 * 60) {
                        $errors[] = "Too early to time out for AM. Please submit an early request or wait until closer to your scheduled time out.";
                    } else {
                        $isLate = strtotime($currentTime) > strtotime($timeOutAm);
                        $attendance->updateAttendance($_SESSION['employee_id'], $currentDate, null, $currentTime, null, null);
                        $success = "Timed out for AM successfully, " . ($isLate ? "but late." : "on time.");
                        
                        if ($is_am_shift_only) {
                            $attendance->updateAttendance($_SESSION['employee_id'], $currentDate, null, null, '00:00:00', '00:00:00');
                            $success = "Timed out for AM successfully. DTR submitted for Admin confirmation.";
                        }
                    }
                } elseif ($action == 'time_in_pm') {
                    $has_am_out = $lastAttendance && $lastAttendance['date'] == $currentDate && !empty($lastAttendance['time_out_am']);
                    $has_record_today = $lastAttendance && $lastAttendance['date'] == $currentDate;

                    if (!$is_pm_shift_only && !$has_am_out) {
                       
                        $errors[] = "Must complete AM session first.";
                    } elseif ($is_pm_shift_only && !$has_record_today) {
                        
                        $isLate = strtotime($currentTime) > strtotime($timeInPm);
                        $attendance->addAttendance($_SESSION['employee_id'], $currentDate, '00:00:00', '00:00:00', $currentTime, null, 'Present');
                        $success = "Timed in for PM successfully, " . ($isLate ? "but late." : "on time.");

                    } elseif (!empty($lastAttendance['time_in_pm'])) {
                        $errors[] = "Already timed in for PM today.";
                    } else {
                        $isLate = strtotime($currentTime) > strtotime($timeInPm);
                        $attendance->updateAttendance($_SESSION['employee_id'], $currentDate, null, null, $currentTime, null);
                        $success = "Timed in for PM successfully, " . ($isLate ? "but late." : "on time.");
                    }
                } elseif ($action == 'time_out_pm') {
                    if (!$lastAttendance || $lastAttendance['date'] != $currentDate || empty($lastAttendance['time_in_pm'])) {
                        $errors[] = "Must time in PM first.";
                    } elseif (strtotime($currentTime) < strtotime($timeOutPm) - 30 * 60) {
                        $errors[] = "Too early to time out for PM. Please submit an early request or wait until closer to your scheduled time out.";
                    } else {
                        $isLate = strtotime($currentTime) > strtotime($timeOutPm);
                        $attendance->updateAttendance($_SESSION['employee_id'], $currentDate, null, null, null, $currentTime);
                        $success = "Timed out for PM successfully. DTR submitted for Admin confirmation.";
                    }
                } else {
                    $errors[] = "Invalid action.";
                }
            } catch (Exception $e) {
                $errors[] = "Error processing attendance: " . $e->getMessage();
            }
        }
    }
    $lastAttendance = $attendance->getLastAttendance($_SESSION['employee_id']);
}

$AM_IN_MAX_TIME = '13:00:00'; $AM_OUT_MAX_TIME = '14:00:00'; $PM_MIN_TIME = '12:00:00';    
$current_time_sec = strtotime($currentTime);
$today = $lastAttendance && $lastAttendance['date'] == $currentDate;

$scheduled_am_out_sec = strtotime($empData['timeout_am']);
$scheduled_pm_out_sec = strtotime($empData['timeout_pm']);

$pending_am_request = $earlyRequest->getRequestByEmployeeDateSession($_SESSION['employee_id'], $currentDate, 'AM');
$pending_pm_request = $earlyRequest->getRequestByEmployeeDateSession($_SESSION['employee_id'], $currentDate, 'PM');

$am_in_disabled_seq = $today && !empty($lastAttendance['time_in_am']);
$am_out_disabled_seq = !$today || empty($lastAttendance['time_in_am']) || !empty($lastAttendance['time_out_am']);
$pm_in_disabled_seq = $is_pm_shift_only ? ($today && !empty($lastAttendance['time_in_pm'])) : (!$today || empty($lastAttendance['time_out_am']) || !empty($lastAttendance['time_in_pm']));
$pm_out_disabled_seq = !$today || empty($lastAttendance['time_in_pm']) || !empty($lastAttendance['time_out_pm']);

$am_in_disabled_time = $current_time_sec >= strtotime($AM_IN_MAX_TIME); 
$am_out_disabled_time = $current_time_sec >= strtotime($AM_OUT_MAX_TIME); 
$pm_disabled_time = $current_time_sec < strtotime($PM_MIN_TIME);

$early_am_out = $today && !empty($lastAttendance['time_in_am']) && empty($lastAttendance['time_out_am']) && ($current_time_sec < ($scheduled_am_out_sec - (30 * 60)));
$early_pm_out = $today && !empty($lastAttendance['time_out_am']) && !empty($lastAttendance['time_in_pm']) && empty($lastAttendance['time_out_pm']) && ($current_time_sec < ($scheduled_pm_out_sec - (30 * 60)));

$disable_all_dtr_complete = $is_dtr_pending || $is_dtr_confirmed;
$disable_time_in_am = $am_in_disabled_seq || $am_in_disabled_time || $disable_all_dtr_complete || $is_pm_shift_only;
$disable_time_out_am = $am_out_disabled_seq || $am_out_disabled_time || $pending_am_request || $disable_all_dtr_complete || $is_pm_shift_only;
$disable_time_in_pm = $pm_in_disabled_seq || $pm_disabled_time || $disable_all_dtr_complete || $is_am_shift_only;
$disable_time_out_pm = $pm_out_disabled_seq || $pm_disabled_time || $pending_pm_request || $disable_all_dtr_complete || $is_am_shift_only;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Main Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --sys-red: #8b0000;
            --sys-red-light: #dc3545;
            --sys-yellow: #ffc107;
            --sys-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body {
            background: #f0f2f5; /* Light background */
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        h1 {
            text-align: center;
            margin: 30px 0 20px;
            color: var(--sys-red); 
            font-weight: 300;
        }

        .current-time { 
            font-size: 1.5rem; /* Larger font */
            color: #333; 
            text-align: center; 
            margin-bottom: 25px; 
            font-weight: 500;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 15px;
        }

        .dashboard-section {
            background: white;
            border: 1px solid #e0e0e0; 
            border-radius: 12px; /* Smoother radius */
            box-shadow: var(--sys-shadow);
            margin-bottom: 25px;
            padding: 30px; /* Increased padding */
        }

        .section-header {
            color: var(--sys-red);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 5px;
            border-bottom: 2px solid #eee;
        }
        
        .welcome-title {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .btn {
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            padding: 15px 10px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 1rem;
        }
        
        .btn-time-action { 
            background-color: var(--sys-red);
            color: white; 
        }

        .btn-time-action:hover { 
            background-color: #a00000; 
            transform: translateY(-2px); 
        }

        .btn-time-action:disabled { 
            background-color: #ccc; 
            color: #666; 
            cursor: not-allowed; 
            transform: none;
        }

        .btn-request { 
            background-color: var(--sys-yellow); 
            color: black; 
        }

        .btn-request:hover { 
            background-color: #e0a800; 
            color: white; 
            transform: translateY(-2px); 
        }
        
        .btn-history {
            background-color: #007bff;
            color: white;
            display: block;
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            font-size: 1.1rem;
        }
        .btn-history:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
        
        .alert { 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 8px; 
            font-weight: 500; 
        }

        .schedule-detail, .record-detail {
            font-size: 1rem;
            margin-bottom: 12px;
            padding: 5px 0;
            border-bottom: 1px dotted #f0f0f0;
            display: flex;
            justify-content: space-between;
        }
        .record-detail:last-child {
            border-bottom: none;
        }

        .schedule-detail strong, .record-detail strong {
            color: #333;
            font-weight: 600;
        }
        .schedule-detail:nth-child(even), .record-detail:nth-child(even) {
            background-color: #f9f9f9;
        }

        .btn-profile-action {
            background-color: var(--sys-red);
            color: white;
            display: block;
            width: 100%;
            margin-top: 15px;
            padding: 12px;
            font-size: 1rem;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-profile-action:hover {
            background-color: #a00000;
            transform: translateY(-1px);
        }
        
        .icon-btn {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .dashboard-section {
                padding: 20px;
            }
            .welcome-title {
                font-size: 1.5rem;
            }
            .section-header {
                font-size: 1.2rem;
            }
            .action-grid {
                grid-template-columns: 1fr 1fr;
            }
            .btn {
                padding: 12px 8px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
             .action-grid {
                grid-template-columns: 1fr; 
            }
        }

    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header_employee.php'; ?>

    <div class="app-container">
    <div class="current-time">
        <svg class="icon-btn" style="width:24px;height:24px;stroke-width:1.5;margin-right:8px;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Current Time: <span id="live-time"><?php echo htmlspecialchars($currentTime); ?></span>
    </div>

    <div class="container">
        <div class="dashboard-section">
            <h2 class="welcome-title">Welcome, Employee No. <?php echo htmlspecialchars($empData['employee_id'] ?? 'N/A'); ?>!</h2>
            <div class="schedule-detail">Position: <span><strong><?php echo htmlspecialchars($empData['position'] ?? 'N/A'); ?></strong></span></div>
            
            <a href="requestLeave.php" class="btn-profile-action">
                <svg class="icon-btn" style="stroke: white;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><path d="M16 2v4M8 2v4M3 10h18"/><line x1="12" y1="14" x2="12" y2="20"/><line x1="9" y1="17" x2="15" y2="17"/></svg>
                Request Leave
            </a> 
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul><?php foreach ($errors as $error) echo "<li>$error</li>"; ?></ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" id="success-alert"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($is_dtr_confirmed): ?>
                <div class="alert alert-success">DTR for **<?php echo htmlspecialchars($currentDate); ?>** completed successfully!</div>
            <?php endif; ?>

            <?php if ($is_dtr_pending): ?>
                <div class="alert alert-warning">Your DTR for **<?php echo htmlspecialchars($currentDate); ?>** is **Pending Admin Confirmation**. No further actions allowed today.</div>
            <?php endif; ?>
            
            <?php if ($pending_am_request): ?>
                <div class="alert alert-warning">Your early time-out request for **AM** is currently **Pending** admin review.</div>
            <?php endif; ?>
            <?php if ($pending_pm_request): ?>
                <div class="alert alert-warning">Your early time-out request for **PM** is currently **Pending** admin review.</div>
            <?php endif; ?>

            <h3 class="section-header mt-4">Daily Actions</h3>
            <form method="POST" action="" id="time-actions-form" class="action-grid">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <button type="submit" name="action" value="time_in_am" class="btn btn-time-action" <?php echo $disable_time_in_am ? 'disabled' : ''; ?>>
                    <svg class="icon-btn" style="stroke: white;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2v10l4 4"/><path d="M12 2a10 10 0 1 0 10 10V2Z"/></svg>
                    Time In AM
                </button>
                
                <?php if ($early_am_out && !$pending_am_request && !$am_out_disabled_seq): ?>
                    <button type="button" class="btn btn-request" onclick="openEarlyRequestModal('AM')" <?php echo $disable_time_out_am ? 'disabled' : ''; ?>>
                        <svg class="icon-btn" style="stroke: black;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 12h18"/><path d="m17 16-4-4 4-4"/><path d="M21 12c0-5.5-4.5-10-10-10S1 6.5 1 12s4.5 10 10 10"/></svg>
                        Request Early Out AM
                    </button>
                <?php else: ?>
                    <button type="submit" name="action" value="time_out_am" class="btn btn-time-action" <?php echo $disable_time_out_am ? 'disabled' : ''; ?>>
                        <svg class="icon-btn" style="stroke: white;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2v10l4 4"/><path d="M12 2a10 10 0 1 1 0 20"/></svg>
                        Time Out AM
                    </button>
                <?php endif; ?>

                <button type="submit" name="action" value="time_in_pm" class="btn btn-time-action" <?php echo $disable_time_in_pm ? 'disabled' : ''; ?>>
                    <svg class="icon-btn" style="stroke: white;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2v10l4 4"/><path d="M12 2a10 10 0 1 0 10 10V2Z"/></svg>
                    Time In PM
                </button>
                
                <?php if ($early_pm_out && !$pending_pm_request && !$pm_out_disabled_seq): ?>
                    <button type="button" class="btn btn-request" onclick="openEarlyRequestModal('PM')" <?php echo $disable_time_out_pm ? 'disabled' : ''; ?>>
                        <svg class="icon-btn" style="stroke: black;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3 12h18"/><path d="m17 16-4-4 4-4"/><path d="M21 12c0-5.5-4.5-10-10-10S1 6.5 1 12s4.5 10 10 10"/></svg>
                        Request Early Out PM
                    </button>
                <?php else: ?>
                    <button type="submit" name="action" value="time_out_pm" class="btn btn-time-action" <?php echo $disable_time_out_pm ? 'disabled' : ''; ?>>
                        <svg class="icon-btn" style="stroke: white;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2v10l4 4"/><path d="M12 2a10 10 0 1 1 0 20"/></svg>
                        Time Out PM
                    </button>
                <?php endif; ?>
            </form>

            <a href="viewHistory.php" class="btn btn-history">
                <svg class="icon-btn" style="stroke: white;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
                View Attendance History
            </a>
        </div>
        
        <div class="dashboard-section row">
            <div class="col-md-6">
                <h3 class="section-header">Your Daily Schedule</h3>
                <div class="schedule-detail">Morning In: <strong><?php echo htmlspecialchars($empData['timein_am'] ?? 'N/A'); ?></strong></div>
                <div class="schedule-detail">Morning Out: <strong><?php echo htmlspecialchars($empData['timeout_am'] ?? 'N/A'); ?></strong></div>
                <div class="schedule-detail">Afternoon In: <strong><?php echo htmlspecialchars($empData['timein_pm'] ?? 'N/A'); ?></strong></div>
                <div class="schedule-detail">Afternoon Out: <strong><?php echo htmlspecialchars($empData['timeout_pm'] ?? 'N/A'); ?></strong></div> 
            </div>

            <div class="col-md-6 mt-4 mt-md-0">
                <h3 class="section-header">Today's Record</h3>
                <?php if ($lastAttendance && $lastAttendance['date'] == $currentDate): ?>
                    <div class="record-detail">Date: <strong><?php echo htmlspecialchars(date('l, F j, Y')); ?></strong></div>
                    <div class="record-detail">Time In AM: <strong><?php echo htmlspecialchars($lastAttendance['time_in_am'] ?? 'Not yet'); ?></strong></div>
                    <div class="record-detail">Time Out AM: <strong><?php echo htmlspecialchars($lastAttendance['time_out_am'] ?? 'Not yet'); ?></strong></div>
                    <div class="record-detail">Time In PM: <strong><?php echo htmlspecialchars($lastAttendance['time_in_pm'] ?? 'Not yet'); ?></strong></div>
                    <div class="record-detail">Time Out PM: <strong><?php echo htmlspecialchars($lastAttendance['time_out_pm'] ?? 'Not yet'); ?></strong></div>
                    <div class="record-detail">Status: <strong><?php echo htmlspecialchars($lastAttendance['status'] ?? 'N/A'); ?></strong></div>
                    <div class="record-detail">DTR Status: <strong><?php echo htmlspecialchars($lastAttendance['confirmation_status'] ?? 'Draft'); ?></strong></div>
                <?php else: ?>
                    <p class="text-center text-muted">No attendance recorded today.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
    
    <div class="modal fade" id="earlyRequestModal" tabindex="-1" aria-labelledby="earlyRequestModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="earlyRequestModalLabel">Early Time-out Request (<span id="session-type"></span>)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>You are attempting to clock out early. Please provide a reason for your early departure.</p>
            <form id="earlyRequestForm">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              <input type="hidden" name="date" value="<?php echo $currentDate; ?>">
              <input type="hidden" name="session" id="modal-session-type">
              <input type="hidden" name="requested_time" id="modal-requested-time" value="<?php echo htmlspecialchars($currentTime); ?>">
              <div class="mb-3">
                <label for="reason-text" class="form-label">Reason:</label>
                <textarea class="form-control" id="reason-text" name="reason" rows="3" required></textarea>
              </div>
              <div id="request-message" class="alert mt-3" style="display:none;"></div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-request" id="submitRequestBtn">Submit Request</button>
          </div>
        </div>
      </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
            document.getElementById('live-time').textContent = timeString;
            document.getElementById('modal-requested-time').value = timeString; 
        }
        updateTime(); 
        setInterval(updateTime, 1000);

        document.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('success-alert');
            if (successAlert) {
                setTimeout(function() {
                    successAlert.style.display = 'none';
                }, 3000); 
            }
            
            const modalElement = document.getElementById('earlyRequestModal');
            const modal = new bootstrap.Modal(modalElement);
            
            window.openEarlyRequestModal = function(sessionType) {
                updateTime();
                
                document.getElementById('session-type').textContent = sessionType;
                document.getElementById('modal-session-type').value = sessionType;
                document.getElementById('reason-text').value = '';
                document.getElementById('request-message').style.display = 'none';
                modal.show();
            };
            
            document.getElementById('submitRequestBtn').addEventListener('click', function() {
                const form = document.getElementById('earlyRequestForm');
                const formData = new FormData(form);
                const messageDiv = document.getElementById('request-message');
                
                if (!formData.get('reason')) {
                    messageDiv.className = 'alert mt-3 alert-danger';
                    messageDiv.textContent = 'Please provide a reason.';
                    messageDiv.style.display = 'block';
                    return;
                }

                this.disabled = true;
                this.textContent = 'Submitting...';
                
                formData.append('action', 'submit_early_timeout'); 
                
                fetch('request_handler.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData) 
                })
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        return response.text().then(text => { throw new Error('Server returned non-JSON response. Status: ' + response.status + ' | Content: ' + text); });
                    }
                })
                .then(data => {
                    if (data.success) {
                        messageDiv.className = 'alert mt-3 alert-success';
                        setTimeout(() => { window.location.reload(); }, 1500); 
                    } else {
                        messageDiv.className = 'alert mt-3 alert-danger';
                    }
                    messageDiv.textContent = data.message;
                    messageDiv.style.display = 'block';
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    messageDiv.className = 'alert mt-3 alert-danger';
                    messageDiv.textContent = 'An error occurred: ' + error.message;
                    messageDiv.style.display = 'block';
                })
                .finally(() => {
                    document.getElementById('submitRequestBtn').disabled = false;
                    document.getElementById('submitRequestBtn').textContent = 'Submit Request';
                });
            });
        });
    </script>
</body>
</html>
