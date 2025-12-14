<?php
require_once __DIR__ . '/../classes/email_sender.php';

function usage() {
    echo "Usage: php test_email_sender_smtp.php --to=recipient@example.com [--host=smtp.example.com] [--port=587] [--user=username] [--pass=password] [--secure=tls|ssl|none] [--from_email=sender@example.com] [--from_name='DTR System']\n";
    exit(1);
}

$options = [];
foreach ($argv as $i => $arg) {
    if ($i === 0) continue;
    if (strpos($arg, '--') !== 0) continue;
    $parts = explode('=', substr($arg, 2), 2);
    if (count($parts) === 2) $options[$parts[0]] = $parts[1];
}

$to = $options['to'] ?? null;
if (empty($to)) usage();

$cfg = [
    'use_smtp' => true,
    'smtp_host' => $options['host'] ?? 'localhost',
    'smtp_port' => intval($options['port'] ?? 25),
    'smtp_user' => $options['user'] ?? '',
    'smtp_pass' => $options['pass'] ?? '',
    'smtp_secure' => $options['secure'] ?? 'tls',
    'smtp_debug' => true,
    'from_email' => $options['from_email'] ?? 'noreply@example.com',
    'from_name' => $options['from_name'] ?? 'DTR Test',
];

$sender = new EmailSender($cfg);
$result = $sender->sendWelcomeEmail($to, 'Test User', 9999, 'dummy-smtp-token');
echo PHP_EOL . "Send result: "; var_export($result); echo PHP_EOL;
echo "Last Error: "; var_export($sender->getLastError()); echo PHP_EOL;
echo "Effective SMTP Config:\n";
var_export($cfg); echo PHP_EOL;

?>