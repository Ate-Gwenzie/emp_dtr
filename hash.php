<?php
// 1. The plain text password you want to hash
$passwordPlaintext = "gw3n12345";

// 2. Hash the password
// PASSWORD_DEFAULT uses the strongest algorithm available in your PHP version (currently Bcrypt).
$passwordHash = password_hash($passwordPlaintext, PASSWORD_DEFAULT);

// 3. Output the results
echo "Plain Text: " . $passwordPlaintext . "<br>";
echo "Hashed Password: " . $passwordHash . "<br>";

// 4. (Optional) Info about the hash
// It will always start with $2y$ and be 60 characters long (for Bcrypt).
echo "Hash Length: " . strlen($passwordHash);
?>