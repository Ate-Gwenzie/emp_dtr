<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendVerificationEmail($recipientEmail, $link) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                           
        $mail->Host       = 'smtp.example.com'; // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = 'your_smtp_username'; // SMTP username
        $mail->Password   = 'your_smtp_password'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Use TLS encryption
        $mail->Port       = 587;                                    // TCP port to connect to

        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'Your Site Team');
        $mail->addAddress($recipientEmail);     

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Verify Your Email Address';
        $mail->Body    = "
            <h1>Account Verification</h1>
            <p>Thank you for registering. Please click the link below to verify your email:</p>
            <p><a href='{$link}'>{$link}</a></p>
            <p>This link will expire in 24 hours.</p>
        ";
        $mail->AltBody = "Please use the following link to verify your account: {$link}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error for debugging (e.g., file_put_contents('error_log.txt', $e->getMessage()))
        return false;
    }
}
?>