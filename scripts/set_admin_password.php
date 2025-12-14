<?php

require_once __DIR__ . '/../classes/database.php';

$email = $argv[1] ?? null;
$newPassword = $argv[2] ?? null;

if (!$email || !$newPassword) {
    echo "Usage: php set_admin_password.php email new_password\n";
    exit(1);
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM admin WHERE email_ad = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    echo "No admin found with email: $email\n";
    exit(1);
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$update = $conn->prepare("UPDATE admin SET pass_ad = :pass WHERE adid = :adid");
$update->bindParam(':pass', $hash);
$update->bindParam(':adid', $admin['adid']);
$update->execute();

$uid = $admin['user_id'] ?? null;
if ($uid) {
    $update2 = $conn->prepare("UPDATE users SET password = :pass WHERE id = :uid");
    $update2->bindParam(':pass', $hash);
    $update2->bindParam(':uid', $uid);
    $update2->execute();
}

echo "Password updated successfully for admin {$admin['email_ad']}.\n";
exit(0);

?>
