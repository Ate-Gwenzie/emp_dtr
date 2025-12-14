<?php
// Secure SMTP configuration for a GMAIL account (Recommended)
return [
    'use_smtp' => true, 
    
    // FIX: Changed host to Gmail's server
    'smtp_host' => 'smtp.gmail.com', 
    
    // Port 587 is standard for TLS (STARTTLS)
    'smtp_port' => 587,
    'smtp_secure' => 'tls', 
    
    // Your Gmail account and App Password
    'smtp_user' => 'thieshagwynethbagnaan@gmail.com',
    'smtp_pass' => 'dvdb yojr qabd uluz', // NOTE: Must be a Gmail App Password!
    
    // The 'from_email' must match your 'smtp_user' for authentication to succeed.
    'from_email' => 'thieshagwynethbagnaan@gmail.com',
    'from_name' => 'DTR System',
    
    'require_email_verification' => true,
    'base_url' => 'http://localhost/emp_dtr', 
    'smtp_debug' => false // TEMPORARY: Set this to TRUE for the next test to see the exact PHPMailer error message
];