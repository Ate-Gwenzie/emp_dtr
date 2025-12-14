<?php
$passwordPlaintext = "gw3n12345";

$passwordHash = password_hash($passwordPlaintext, PASSWORD_DEFAULT);

echo "Plain Text: " . $passwordPlaintext . "<br>";
echo "Hashed Password: " . $passwordHash . "<br>";

echo "Hash Length: " . strlen($passwordHash);
?>
