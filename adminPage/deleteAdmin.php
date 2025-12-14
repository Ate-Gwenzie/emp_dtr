<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/admin.php';

$admin = new Admin();
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request.";
    } else {
        $ad_id = trim($_POST['ad_id'] ?? '');
        if (empty($ad_id) || !is_numeric($ad_id)) {
            $errors[] = "Invalid admin ID.";
        } else {
            try {
                if ($admin->deleteAdmin($ad_id)) {
                    $success = "Admin deleted successfully.";
                } else {
                    $errors[] = "Failed to delete admin.";
                }
            } catch (Exception $e) {
                $errors[] = "Error deleting admin: " . $e->getMessage();
            }
        }
    }
    $_SESSION['delete_message'] = $success ?: implode(", ", $errors);
    header("Location: viewAdmin.php");
    exit();
} else {
    header("Location: viewAdmin.php");
    exit();
}
?>