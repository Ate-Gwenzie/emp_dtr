<?php
require_once __DIR__ . '/../classes/email_sender.php';

$cfg = [
    'use_smtp' => false,
    'from_email' => 'test@example.com',
    'from_name' => 'DTR Test'
];

$sender = new EmailSender($cfg);
$to = 'invalid+test@example.com';
$result = $sender->sendWelcomeEmail($to, 'Test User', 9999, 'dummy-token');
echo "Send result: ";
var_export($result);
echo PHP_EOL;
echo "Last Error: ";
var_export($sender->getLastError());
echo PHP_EOL;

?>
