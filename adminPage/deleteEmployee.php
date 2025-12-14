<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../user/login.php");
    exit();
}
require_once '../classes/employee.php';

$employee = new Employee();
$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid request.";
    } else {
        $emp_id = trim($_POST['emp_id'] ?? '');
        if (empty($emp_id) || !is_numeric($emp_id)) {
            $errors[] = "Invalid employee ID.";
        } else {
            try {
                if ($employee->deleteEmployee($emp_id)) {
                    $success = "Employee deleted successfully.";
                } else {
                    $errors[] = "Failed to delete employee.";
                }
            } catch (Exception $e) {
                $errors[] = "Error deleting employee: " . $e->getMessage();
            }
        }
    }
    $_SESSION['delete_message'] = $success ?: implode(", ", $errors);
    header("Location: viewEmployee.php");
    exit();
} else {
    header("Location: viewEmployee.php");
    exit();
}
?>