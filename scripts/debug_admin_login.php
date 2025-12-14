<?php

require_once __DIR__ . '/../classes/database.php';

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

$db = new Database();
$conn = $db->getConnection();

if ($email && $password) {
    $stmt = $conn->prepare("SELECT a.*, u.password as user_password FROM admin a LEFT JOIN users u ON a.user_id = u.id WHERE a.email_ad = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo "No admin account found with email: $email\n";
        exit(1);
    }
    echo "Admin found: {$row['fname_ad']} {$row['lname_ad']} (adid: {$row['adid']}, user_id: {$row['user_id']})\n";
    $hash = $row['pass_ad'] ?? '';
    $user_hash = $row['user_password'] ?? '';
    echo "pass_ad (stored): " . substr($hash, 0, 60) . "...\n";
    echo "users.password (stored): " . substr($user_hash, 0, 60) . "...\n";
    $ok_ad = password_verify($password, $hash);
    $ok_user = password_verify($password, $user_hash);
    echo "Password verify vs admin.pass_ad: " . ($ok_ad ? 'OK' : 'MISMATCH') . "\n";
    echo "Password verify vs users.password: " . ($ok_user ? 'OK' : 'MISMATCH') . "\n";
    $stmt2 = $conn->prepare("SELECT verified FROM email_verifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 1");
    $uid = $row['user_id'] ?? null;
    $stmt2->bindParam(':uid', $uid);
    $stmt2->execute();
    $v = $stmt2->fetch(PDO::FETCH_ASSOC);
    $verified = $v ? (bool)$v['verified'] : true;
    echo "Email verification (for user_id {$uid}): " . ($verified ? 'VERIFIED or no record' : 'NOT VERIFIED') . "\n";
    exit(0);
} else {
    $stmt = $conn->query("SELECT adid, fname_ad, lname_ad, email_ad, user_id FROM admin");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Admins:\n";
    foreach ($rows as $r) {
        echo "- adid: {$r['adid']}, name: {$r['fname_ad']} {$r['lname_ad']}, email: {$r['email_ad']}, user_id: {$r['user_id']}\n";
    }
    exit(0);
}

?>
