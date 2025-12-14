<?php
class Mailer {
    public static function send($to, $subject, $htmlBody, $plainBody = '') {
        $cfg = @include __DIR__ . '/../config/email_config.php';
        if (!$cfg) $cfg = [];

        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                if (!empty($cfg['use_smtp'])) {
                    $mail->isSMTP();
                    $mail->Host = $cfg['smtp_host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $cfg['smtp_user'];
                    $mail->Password = $cfg['smtp_pass'];
                    $mail->SMTPSecure = $cfg['smtp_secure'] ?? 'tls';
                    $mail->Port = $cfg['smtp_port'] ?? 587;
                }
                $mail->setFrom($cfg['from_email'] ?? 'noreply@localhost', $cfg['from_name'] ?? 'DTR System');
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                if (!empty($plainBody)) $mail->AltBody = $plainBody;
                return $mail->send();
            } catch (Exception $e) {
                // fallback to mail()
            }
        }

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . ($cfg['from_name'] ?? 'DTR System') . ' <' . ($cfg['from_email'] ?? 'noreply@localhost') . '>' . "\r\n";
        return mail($to, $subject, $htmlBody, $headers);
    }
}
