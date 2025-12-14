<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Preferred: autoload from Composer (vendor). Fallbacks below.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else if (file_exists(__DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    // PHPMailer present in vendor but Composer autoload is missing - load PHPMailer files directly
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
} else {
    // If composer isn't installed, try a local PHPMailer (if ever bundled here), otherwise gracefully degrade
    if (file_exists(__DIR__ . '/phpMailer/src/Exception.php')) require_once __DIR__ . '/phpMailer/src/Exception.php';
    if (file_exists(__DIR__ . '/phpMailer/src/PHPMailer.php')) require_once __DIR__ . '/phpMailer/src/PHPMailer.php';
    if (file_exists(__DIR__ . '/phpMailer/src/SMTP.php')) require_once __DIR__ . '/phpMailer/src/SMTP.php';
}
// load our Notification class; config is loaded by the EmailSender constructor
require_once __DIR__ . '/notification.php'; // Load the Notification class

class EmailSender {
    private $cfg = [];
    private $lastError = '';

    public function __construct(array $cfg = null) {
        if (!is_null($cfg)) {
            $this->cfg = $cfg;
            return;
        }
        if (file_exists(__DIR__ . '/../config/email_config.php')) {
            $cfgFile = include __DIR__ . '/../config/email_config.php';
            if (is_array($cfgFile)) {
                $this->cfg = $cfgFile;
            }
        }
    }

    /**
     * Configures and returns a fully initialized PHPMailer instance.
     */
    protected function createMailer() {
        $mail = new PHPMailer(true); // Enable exceptions

        try {
            // Server settings
            $mail->SMTPDebug = !empty($this->cfg['smtp_debug']) ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF; // Set debug if configured
            // If config requests the use of SMTP and the keys exist, configure PHPMailer as SMTP
            if (!empty($this->cfg['use_smtp'])) {
                $mail->isSMTP();
                $mail->Host       = $this->cfg['smtp_host'] ?? 'localhost';
                $mail->SMTPAuth   = true;
                $mail->Username   = $this->cfg['smtp_user'] ?? '';
                $mail->Password   = $this->cfg['smtp_pass'] ?? '';
                $mail->SMTPSecure = $this->cfg['smtp_secure'] ?? 'tls';
                $mail->Port       = $this->cfg['smtp_port'] ?? 587;
            }

            // Sender settings
            $mail->CharSet = 'UTF-8';
            $from_email = $this->cfg['from_email'] ?? ($this->cfg['from'] ?? 'noreply@localhost');
            $from_name  = $this->cfg['from_name'] ?? ($this->cfg['from_name'] ?? 'DTR System');
            $mail->setFrom($from_email, $from_name);

            return $mail;

        } catch (Exception $e) {
            error_log("PHPMailer setup failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sends a general email, allowing explicit plain text content.
     * This replaces the old protected send() method and fixes the fatal error.
     * * @param string $recipientEmail
     * @param string $subject
     * @param string $bodyHtml HTML body content
     * @param string|null $bodyText Optional plain text alternative. If null, it's stripped from HTML body.
     * @return bool True on success
     */
    public function sendEmail($recipientEmail, $subject, $bodyHtml, $bodyText = null) {
        $mail = $this->createMailer();

        if (is_null($mail)) {
            // Fallback to simple Mailer wrapper if PHPMailer isn't available
            if (file_exists(__DIR__ . '/mailer.php')) {
                require_once __DIR__ . '/mailer.php';
                // Pass both HTML and Text body to the fallback mailer
                return Mailer::send($recipientEmail, $subject, $bodyHtml, $bodyText ?: strip_tags($bodyHtml));
            }
            return false;
        }

        try {
            $mail->addAddress($recipientEmail);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            // Use provided plain text body, otherwise strip tags from HTML body
            $mail->AltBody = $bodyText ?: strip_tags($bodyHtml); 
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            $err = $mail->ErrorInfo ?? $e->getMessage();
            $this->lastError = $err;
            error_log("PHPMailer Send Error to {$recipientEmail}: {$err}");
            
            // Fallback: use the simple Mailer wrapper if available
            if (file_exists(__DIR__ . '/mailer.php')) {
                require_once __DIR__ . '/mailer.php';
                try {
                    // Pass both HTML and Text body to the fallback mailer
                    $fb = Mailer::send($recipientEmail, $subject, $bodyHtml, $bodyText ?: strip_tags($bodyHtml));
                    if (!$fb) {
                        $this->lastError = 'Mailer fallback failed';
                    }
                    return $fb;
                } catch (\Throwable $t) {
                    $this->lastError = $t->getMessage();
                    error_log("Mailer::send fallback failed: " . $t->getMessage());
                    return false;
                }
            }
            return false;
        }
    }
    
    // REMOVED: The old protected function send() is no longer needed.

    public function getLastError() {
            return $this->lastError;
        }

    /**
     * Internal helper function to log the notification to the database.
     */
    protected function logNotification($recipient_type, $recipient_id, $notification_type, $subject, $body) {
        try {
            $notificationManager = new Notification();
            // Use the email subject and strip HTML tags from the body for the notification message
            $message = "Subject: {$subject}\n" . strip_tags(str_replace('<br>', "\n", $body));
            return $notificationManager->recordNotification($recipient_type, $recipient_id, $notification_type, $message);
        } catch (\Exception $e) {
            error_log("Error logging notification: " . $e->getMessage());
            return false;
        }
    }

    // MODIFIED: Updated to call sendEmail()
    public function sendWelcomeEmail($recipientEmail, $name, $empid, $verification_token = null) {
        // Prefer a configured base URL in config/email_config.php; otherwise fallback to the placeholder
        $baseUrl = $this->cfg['base_url'] ?? 'http://YOUR_BASE_URL/emp_dtr';
        $verification_url = rtrim($baseUrl, '/') . '/user/verify.php?email=' . urlencode($recipientEmail) . '&token=' . urlencode($verification_token);

        $subject = is_null($verification_token) ? "Employee Daily Time Record System - Welcome" : "Employee Daily Time Record System - Account Verification 🎉";
        
        // Build the HTML body
        $bodyHtml = "
            <html>
            <body style='font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; margin: 0;'>
                <center>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1); border-top: 5px solid #ffc107;'>
                    <div style='background-color: #8b0000; color: white; padding: 20px 25px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 24px; font-weight: bold;'>Account Verification Required!</h2>
                    </div>
                    <div style='padding: 30px 25px; line-height: 1.7; color: #333333;'>
                        <p style='margin-bottom: 15px;'>Dear <strong style='color: #8b0000;'>{$name}</strong>,</p>
                        <p style='margin-bottom: 25px;'>Your account for the DTR System has been created.</p>
                        ";

        // Add verification block if token exists
        if (!is_null($verification_token) && !empty($verification_token)) {
            $bodyHtml .= "
                        <p style='margin-bottom: 25px;'>Before logging in, you must verify your email address by clicking the button below:</p>
                        <p style='margin-bottom: 25px;'>Your registered email: <strong>{$recipientEmail}</strong>.</p>
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <a href='" . htmlspecialchars($verification_url) . "' style='display: inline-block; padding: 12px 25px; background-color: #28a745; color: white; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);'>
                                Verify Account Now
                            </a>
                        </div>
                        <p style='font-size: 14px; text-align: center; color: #666;'>If the button above does not work, copy and paste this link into your browser: <a href='" . htmlspecialchars($verification_url) . "'>" . htmlspecialchars($verification_url) . "</a></p>
                        ";
        }
                        
        $bodyHtml .= "
                        <p style='font-size: 14px; color: #666666; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eeeeee;'>Yours Sincerely, <br><strong style='color: #8b0000;'>" . ($this->cfg['from_name'] ?? 'DTR System') . "</strong></p>
                    </div>
                </div>
                </center>
            </body>
            </html>
        ";
        
        // Build the Plain Text body for AltBody
        $bodyText = "Account Verification Required!\n\n"
                    . "Dear {$name},\n"
                    . "Your account for the DTR System has been created.\n\n";

        if (!is_null($verification_token) && !empty($verification_token)) {
            $bodyText .= "Before logging in, you must verify your email address. \n"
                       . "Your registered email: {$recipientEmail}.\n\n"
                       . "Verification Link: {$verification_url}\n\n";
        }

        $bodyText .= "Yours Sincerely, " . ($this->cfg['from_name'] ?? 'DTR System');


        $email_sent = $this->sendEmail($recipientEmail, $subject, $bodyHtml, $bodyText);
        
        if ($email_sent) {
            $this->logNotification('employee', $empid, 'welcome', $subject, $bodyHtml);
        }
        return $email_sent;
    }
    
    // MODIFIED: Updated to call sendEmail()
    public function sendPasswordUpdateEmail($recipientEmail, $name, $empid) {
        $subject = "Password Reset Confirmation ✅";
        
        // Build HTML Body
        $bodyHtml = "
            <html>
            <body style='font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; margin: 0;'>
                <center>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1); border-top: 5px solid #28a745;'>
                    <div style='background-color: #28a745; color: white; padding: 20px 25px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 24px; font-weight: bold;'>Your Password Has Been Updated! ✅</h2>
                    </div>
                    <div style='padding: 30px 25px; line-height: 1.7; color: #333333;'>
                        <p style='margin-bottom: 15px;'>Dear <strong style='color: #28a745;'>{$name}</strong>,</p>
                        <p style='margin-bottom: 25px;'>Your password reset request has been **CONFIRMED** by the administrator. Your new password is now active.</p>
                        <p style='margin-bottom: 30px; font-weight: bold;'>Please proceed to the login page and sign in with your email and the new password you submitted in your request.</p>
                        
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <a href='#' style='display: inline-block; padding: 12px 25px; background-color: #ffc107; color: #000000; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 16px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);'>
                                Log In Now
                            </a>
                        </div>
                        
                        <p style='font-size: 14px; color: #666666; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eeeeee;'>Yours Sincerely, <br><strong style='color: #8b0000;'>" . ($this->cfg['from_name'] ?? 'DTR System') . "</strong></p>
                    </div>
                </div>
                </center>
            </body>
            </html>
        ";
        
        // Build Plain Text Body
        $bodyText = "Password Reset Confirmation\n\n"
                  . "Dear {$name},\n"
                  . "Your password reset request has been CONFIRMED by the administrator. Your new password is now active.\n\n"
                  . "Please proceed to the login page and sign in with your email and the new password.";

        $email_sent = $this->sendEmail($recipientEmail, $subject, $bodyHtml, $bodyText);
        
        if ($email_sent) {
            $this->logNotification('employee', $empid, 'password_update', $subject, $bodyHtml);
        }
        return $email_sent;
    }
    
    // MODIFIED: Updated to call sendEmail()
    public function sendRequestStatusEmail($recipientEmail, $name, $requestType, $status, $details, $empid) {
        $color = ($status === 'Approved') ? '#28a745' : '#dc3545';
        $statusText = strtoupper($status);
        $emoji = ($status === 'Approved') ? '👍' : '❌';
        $secondaryColor = ($status === 'Approved') ? '#28a745' : '#8b0000';
        $subject = "Your {$requestType} Request Status: {$statusText} {$emoji}";
        
        // Build HTML Body
        $bodyHtml = "
            <html>
            <body style='font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; margin: 0;'>
                <center>
                <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1); border-top: 5px solid {$color};'>
                    <div style='background-color: {$color}; color: white; padding: 20px 25px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 24px; font-weight: bold;'>{$requestType} Request: {$statusText} {$emoji}</h2>
                    </div>
                    <div style='padding: 30px 25px; line-height: 1.7; color: #333333;'>
                        <p style='margin-bottom: 15px;'>Dear <strong style='color: #8b0000;'>{$name}</strong>,</p>
                        <p style='margin-bottom: 25px;'>Your request for **{$requestType}** has been <b style='color: {$secondaryColor};'>{$statusText}</b>.</p>
                        
                        <p style='font-weight: bold; margin-bottom: 10px; color: #8b0000;'>Details:</p>
                        <div style='background-color: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #eeeeee; margin-bottom: 30px; font-size: 14px;'>
                            {$details}
                        </div>
                        
                        <p style='font-size: 14px; color: #666666; margin-top: 30px; padding-top: 15px; border-top: 1px solid #eeeeee;'>Yours Sincerely, <br><strong style='color: #8b0000;'>" . ($this->cfg['from_name'] ?? 'DTR System') . "</strong></p>
                    </div>
                </div>
                </center>
            </body>
            </html>
        ";
        
        // Build Plain Text Body (removing HTML formatting from $details)
        $bodyText = "Your {$requestType} Request Status: {$statusText}\n\n"
                  . "Dear {$name},\n"
                  . "Your request for {$requestType} has been {$statusText}.\n\n"
                  . "Details:\n"
                  . strip_tags(str_replace(['<br>', '</div>', '<p>'], ["\n", "\n", "\n"], $details));

        $email_sent = $this->sendEmail($recipientEmail, $subject, $bodyHtml, $bodyText);
        
        if ($email_sent) {
            $notification_type = str_replace(' ', '_', strtolower($requestType)) . '_status';
            $this->logNotification('employee', $empid, $notification_type, $subject, $bodyHtml);
        }
        return $email_sent;
    }
}