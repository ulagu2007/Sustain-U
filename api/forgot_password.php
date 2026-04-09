<?php
/**
 * ============================================
 * FORGOT PASSWORD API ENDPOINT
 * ============================================
 * Requests an OTP for password reset
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Parse input
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($input['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Valid email is required.';
    echo json_encode($response);
    exit;
}

// 1. Check if user exists
$stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    // SECURITY: Don't reveal if email exists or not? 
    // Actually, for "Forgot Password", it's helpful but potentially risky. 
    // The user request says "if we give the email id ... otp should go".
    // I'll show a friendly error if not found.
    $response['message'] = 'If this email is registered, you will receive an OTP.';
    $response['success'] = true; // Still say true to avoid account enumeration
    echo json_encode($response);
    exit;
}

// 2. Generate OTP and save to DB
$otp = (string)random_int(100000, 999999);
$expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

$stmt = $conn->prepare("INSERT INTO otp_verification (email, otp, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $otp, $expires_at);

if (!$stmt->execute()) {
    logError('Forgot Password: Insert OTP Failed', ['error' => $stmt->error, 'email' => $email]);
    $response['message'] = 'System error. Please try again.';
    echo json_encode($response);
    exit;
}
$stmt->close();

// 3. Send Email via PHPMailer
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    $mail->Username   = 'sustainu.environment@gmail.com'; 
    $mail->Password   = 'wjpctxittbftmggt'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('sustainu.environment@gmail.com', 'Sustain-U Security');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Your Password Reset OTP - Sustain-U';
    $mail->Body    = "Hello <strong>" . sanitize($user['name']) . "</strong>,<br><br>We received a request to reset your Sustain-U password.<br><br>Your Reset OTP is: <h2 style='color:#0A58CA;'>$otp</h2><br>This code will expire in 5 minutes.<br><br>If you did not request this, please ignore this email.<br><br>Best regards,<br>The Sustain-U Team.";
    $mail->AltBody = "Hello, your Sustain-U Password Reset OTP is: $otp. It expires in 5 minutes.";

    $mail->send();
    
    $response['success'] = true;
    $response['message'] = 'OTP sent to your email. Please verify.';
    echo json_encode($response);
} catch (Exception $e) {
    $conn->query("DELETE FROM otp_verification WHERE email = '$email' AND otp = '$otp'");
    $response['message'] = 'Failed to send OTP. Please try again later.';
    error_log("Forgot Password PHPMailer Error: {$mail->ErrorInfo}");
    echo json_encode($response);
}
