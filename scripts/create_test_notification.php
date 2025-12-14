<?php
require_once __DIR__ . '/../classes/notification.php';
// Creates a test notification for admin id 1
$nm = new Notification();
$success = $nm->recordNotification('admin', 1, 'test_notification', 'This is a test notification for admin 1');
if ($success) echo "Notification inserted\n"; else echo "Failed to insert\n";

?>
