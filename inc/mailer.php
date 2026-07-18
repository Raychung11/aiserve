<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

require_once __DIR__ . '/../vendor/phpmailer/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_smtp_mail(string $toEmail, string $toName, string $subject, string $htmlBody, string $plainBody = ''): array {
    $mailer = new PHPMailer(true);

    try {
        $host       = get_setting('smtp_host');
        $port       = (int)get_setting('smtp_port', '587');
        $username   = get_setting('smtp_username');
        $password   = get_setting('smtp_password');
        $encryption = get_setting('smtp_encryption', 'tls');
        $fromEmail  = get_setting('smtp_from_email', 'no-reply@aiserve.io');
        $fromName   = get_setting('smtp_from_name', COMPANY_NAME);

        $mailer->isSMTP();
        $mailer->Host = $host;
        $mailer->SMTPAuth = true;
        $mailer->Username = $username;
        $mailer->Password = $password;

        if ($encryption === 'ssl') {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mailer->Port = $port;
        $mailer->CharSet = 'UTF-8';

        $mailer->setFrom($fromEmail, $fromName);
        $mailer->addAddress($toEmail, $toName);

        $mailer->isHTML(true);
        $mailer->Subject = $subject;
        $mailer->Body = $htmlBody;
        $mailer->AltBody = $plainBody !== '' ? $plainBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mailer->send();

        return ['ok' => true, 'message' => 'sent'];
    } catch (Exception $e) {
        return ['ok' => false, 'message' => $mailer->ErrorInfo ?: $e->getMessage()];
    }
}