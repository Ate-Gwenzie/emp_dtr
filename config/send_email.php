<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendVerificationEmail($recipientEmail, $link) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();                                           
        $mail->Host       = 'smtp.example.com'; 
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = 'thieshagwynethbagnaan@gmail.com'; 
        $mail->Password   = 'dvdb yojr qabd uluz'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         
        $mail->Port       = 587;                                    
        $mail->setFrom('no-reply@yourdomain.com', 'Your Site Team');
        $mail->addAddress($recipientEmail);     

        $mail->isHTML(true);                                 
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
        return false;
    }
}
?>
