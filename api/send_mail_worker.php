<?php
/**
 * ============================================
 * BACKGROUND MAIL WORKER
 * ============================================
 * Internal script to handle slow SMTP sending
 */

if (php_sapi_name() !== 'cli' && !isset($_GET['internal_key'])) {
    die('Restricted access');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Get arguments (Email, OTP, Subject)
$email = $argv[1] ?? $_GET['email'] ?? '';
$otp = $argv[2] ?? $_GET['otp'] ?? '';
$subject = $argv[3] ?? $_GET['subject'] ?? 'Your Login OTP';

if (empty($email) || empty($otp)) {
    exit("Missing parameters\n");
}

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'sustainu.environment@gmail.com'; 
    $mail->Password   = 'wjpctxittbftmggt'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->SMTPDebug  = 0;
    $mail->Timeout    = 20;

    $mail->setFrom('sustainu.environment@gmail.com', 'Sustain-U Security');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = "<h3>$subject</h3><p>Your One-Time Password is <strong>$otp</strong>. It is valid for 5 minutes.</p>";
    $mail->AltBody = "Your One-Time Password is $otp. It is valid for 5 minutes.";

    $mail->send();
    echo "Success: Mail sent to $email\n";
} catch (Exception $e) {
    error_log("Background Mailer Error: {$mail->ErrorInfo}");
    echo "Error: {$mail->ErrorInfo}\n";
}
