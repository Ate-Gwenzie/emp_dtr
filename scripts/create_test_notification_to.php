<?php
if ($argc < 2) {
    echo "Usage: php create_test_notification_to.php <admin_id>\n";
    exit(1);
}
$id = intval($argv[1]);
require_once __DIR__ . '/../classes/notification.php';
$nm = new Notification();
$ok = $nm->recordNotification('admin', $id, 'test_notification', 'Test notification');
echo $ok ? "Inserted for admin $id\n" : "Failed to insert for admin $id\n";

?>
