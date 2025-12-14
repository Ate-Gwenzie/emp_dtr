<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../user/login.php");
    exit();
}

require_once '../includes/header_employee.php';
require_once '../classes/notification.php';

$empid = $_SESSION['employee_id'];
$notificationManager = new Notification();
$message = '';

// --- 1. Handle Delete Action ---
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    if ($deleteId > 0) {
        $notifications_check = $notificationManager->getNotifications('employee', $empid);
        $is_owner = false;
        foreach ($notifications_check as $n) {
            if ((int)$n['id'] === $deleteId) {
                $is_owner = true;
                break;
            }
        }
        
        if ($is_owner && $notificationManager->deleteNotification($deleteId)) {
            header("Location: viewNotification.php?status=deleted");
            exit();
        } else {
            header("Location: viewNotification.php?error=delete_failed");
            exit();
        }
    }
}

// --- 2. Handle Feedback Status from Redirect ---
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'marked_read') {
        $message = '<div class="alert alert-success">Notification marked as read.</div>';
    } elseif ($_GET['status'] === 'deleted') {
        $message = '<div class="alert alert-danger">Notification successfully deleted.</div>';
    }
}
if (isset($_GET['error']) && $_GET['error'] === 'invalid_notification') {
    $message = '<div class="alert alert-danger">Error: Could not retrieve the specific notification.</div>';
}

// --- 3. Fetch Notifications ---
$notifications = $notificationManager->getNotifications('employee', $empid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notification-view { 
            max-width: 850px; 
        }
        .notification-wrapper {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        /* FIX: Give the link a flexible width to use available space before the button */
        .notification-link {
            text-decoration: none;
            color: inherit;
            flex-basis: 92%; /* Set a preferred basis */
            flex-grow: 1; 
            flex-shrink: 0;
            margin-right: 10px; /* Space between link and button */
        }
        .notification-item {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: background-color 0.2s, border-color 0.2s;
            cursor: pointer;
            width: 100%;
            min-height: 70px; 
            display: flex; 
            align-items: center;
        }
        .notification-item:hover {
            background-color: #f7f7f7;
            border-color: #ffc107;
        }
        .notification-unread {
            background-color: #fff3cd; 
            border-left: 5px solid #ffc107;
            font-weight: bold;
        }
        .notification-type {
            font-size: 0.9em;
            color: #8b0000;
            text-transform: uppercase;
        }
        /* FIX: Remove max-width restriction on snippet */
        .notification-snippet {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-weight: normal;
            font-size: 0.9em;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        /* The inner flex container must properly distribute space */
        .notification-item .d-flex {
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        /* Ensure the text container (div inside d-flex) can shrink but the date is pushed right */
        .notification-item .d-flex > div:first-child {
            flex-grow: 1;
            flex-shrink: 1;
            /* Allow the text container to occupy most space */
            min-width: 0; 
        }
        .notification-item small {
            flex-shrink: 0; /* Prevents date from wrapping */
            margin-left: 10px;
        }

        .delete-btn {
            padding: 10px 15px;
            height: 45px; 
            font-weight: bold;
            border-radius: 8px;
            line-height: 1;
            flex-shrink: 0; 
        }
    </style>
</head>
<body>
<div class="app-container notification-view">
    <h1>My Notifications</h1>

    <?php echo $message; ?>
    
    <?php if (empty($notifications)): ?>
        <div class="alert alert-info">You have no notifications.</div>
    <?php else: ?>
        <?php foreach ($notifications as $notif): 
            $snippet = strip_tags($notif['message']);
            $snippet = preg_replace('/\s+/', ' ', $snippet);
            $snippet = substr($snippet, 0, 100);
        ?>
            <div class="notification-wrapper">
                <a href="viewSingleNotification.php?id=<?php echo $notif['id']; ?>" class="notification-link">
                    <div class="notification-item <?php echo $notif['is_read'] == 0 ? 'notification-unread' : ''; ?>">
                        <div class="d-flex w-100">
                            <div>
                                <span class="notification-type"><?php echo htmlspecialchars(str_replace('_', ' ', $notif['notification_type'])); ?></span>
                                <span class="notification-snippet"><?php echo htmlspecialchars($snippet); ?>...</span>
                            </div>
                            <small class="text-muted text-end"><?php echo date('F j, Y, g:i a', strtotime($notif['created_at'])); ?></small>
                        </div>
                    </div>
                </a>
                <a href="?delete_id=<?php echo $notif['id']; ?>" 
                   onclick="return confirm('Are you sure you want to delete this notification?');" 
                   class="btn btn-sm btn-outline-danger delete-btn" 
                   title="Delete Notification">
                   <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                      <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                      <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4h7.764L13 2.5 11.5 2H4.5L3 2.5zm-2 1a.5.5 0 0 0 0 1h12a.5.5 0 0 0 0-1z"/>
                    </svg>
                </a>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>