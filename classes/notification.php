<?php
require_once __DIR__ . '/database.php';

class Notification {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Counts the number of unread notifications for a specific user.
     * @param string $recipient_type 'admin' or 'employee'
     * @param int $recipient_id The ID of the recipient
     * @return int The count of unread notifications.
     */
    public function countUnreadNotifications($recipient_type, $recipient_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE recipient_type = :rtype AND recipient_id = :rid AND is_read = 0");
        $stmt->bindParam(':rtype', $recipient_type);
        $stmt->bindParam(':rid', $recipient_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Records a new notification in the database.
     * @param string $recipient_type 'admin' or 'employee'
     * @param int $recipient_id The ID of the recipient (adid for admin, empid for employee)
     * @param string $notification_type A short, descriptive type (e.g., 'password_reset', 'leave_status')
     * @param string $message The full message content
     * @return bool True on success
     */
    public function recordNotification($recipient_type, $recipient_id, $notification_type, $message) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("INSERT INTO notifications (recipient_type, recipient_id, notification_type, message) VALUES (:rtype, :rid, :ntype, :message)");
        $stmt->bindParam(':rtype', $recipient_type);
        $stmt->bindParam(':rid', $recipient_id);
        $stmt->bindParam(':ntype', $notification_type);
        $stmt->bindParam(':message', $message);
        return $stmt->execute();
    }

    /**
     * Fetches all notifications for a specific user.
     */
    public function getNotifications($recipient_type, $recipient_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE recipient_type = :rtype AND recipient_id = :rid ORDER BY created_at DESC");
        $stmt->bindParam(':rtype', $recipient_type);
        $stmt->bindParam(':rid', $recipient_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Marks a notification as read.
     */
    public function markAsRead($notification_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id");
        $stmt->bindParam(':id', $notification_id);
        return $stmt->execute();
    }

    /**
     * Deletes a specific notification by ID.
     * @param int $notification_id The ID of the notification to delete.
     * @return bool True on success.
     */
    public function deleteNotification($notification_id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = :id");
        $stmt->bindParam(':id', $notification_id);
        return $stmt->execute();
    }
}
?>