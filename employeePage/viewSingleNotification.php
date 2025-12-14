<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../user/login.php");
    exit();
}

require_once '../includes/header_employee.php';
require_once '../classes/notification.php';

$empid = $_SESSION['employee_id'];
$notifId = intval($_GET['id'] ?? 0);

if ($notifId === 0) {
    header("Location: viewNotification.php");
    exit();
}

$notificationManager = new Notification();
$notification = null;

$notifications = $notificationManager->getNotifications('employee', $empid);

foreach ($notifications as $n) {
    if ((int)$n['id'] === $notifId) {
        $notification = $n;
        break;
    }
}

if (!$notification) {
    header("Location: viewNotification.php?error=invalid_notification");
    exit();
}

if ($notification['is_read'] == 0) {
    $notificationManager->markAsRead($notifId);
}

$title = htmlspecialchars(str_replace('_', ' ', $notification['notification_type']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification: <?php echo $title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .notification-detail-card {
            max-width: 800px;
            margin: 20px auto;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .message-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 20px;
            border-radius: 6px;
            white-space: pre-wrap;
            font-family: inherit;
        }
    </style>
</head>
<body>
<div class="app-container">
    <div class="card notification-detail-card">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><?php echo $title; ?></h5>
        </div>
        <div class="card-body">
            <p class="text-muted small">Received: <?php echo date('F j, Y, g:i a', strtotime($notification['created_at'])); ?></p>
            <hr>
            <div class="message-box">
                <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
            </div>
            <a href="viewNotification.php" class="btn btn-secondary mt-4">← Back to All Notifications</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
