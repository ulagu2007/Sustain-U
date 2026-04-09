<?php
/**
 * ============================================
 * LOGIN API ENDPOINT
 * ============================================
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
$response = ['success' => false, 'message' => '', 'redirect' => ''];

// ============================================
// COLLECT & VALIDATE INPUT
// ============================================

// Handle both JSON and form data
$email = '';
$password = '';
$is_admin = false;

// Accept content types like "application/json; charset=UTF-8" as JSON
if (!empty($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $is_admin = !empty($input['is_admin']);
}
else {
    // support form-encoded or multipart form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_admin = !empty($_POST['is_admin']);
}


if (empty($email) || empty($password)) {
    $response['message'] = 'Email and password are required';
    echo json_encode($response);
    exit;
}

// Hardcoded admin credentials per project requirement
$admin_creds = [
    'srmhod001@gmail.com' => 'OmSairam@2'
];

if ($is_admin && isset($admin_creds[$email]) && $admin_creds[$email] === $password) {
    // Create an admin session (user id 0 reserved for hardcoded admin)
    $_SESSION['user_id'] = 0;
    $_SESSION['user_name'] = 'Sustain-U Admin';
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['login_time'] = time();
    // Mitigate session fixation and ensure cookie/state consistency
    session_regenerate_id(true);
    $response['success'] = true;
    $response['message'] = 'Admin login successful';
    $response['redirect'] = 'admin_dashboard.php';
    echo json_encode($response);
    exit;
}

// Reject admin credentials on student login
if (!$is_admin && isset($admin_creds[$email])) {
    $response['message'] = 'Invalid email or password';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// ============================================
// FETCH USER FROM DATABASE
// ============================================

$stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    http_response_code(500);
    logError('Execute failed: ' . $stmt->error);
    $stmt->close();
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $response['message'] = 'Invalid email or password';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// Enforce role-based login separation
if ($is_admin) {
    if (($user['role'] ?? '') !== 'admin') {
        $response['message'] = 'Invalid email or password';
        http_response_code(401);
        echo json_encode($response);
        exit;
    }
} else {
    // Student login: Reject anyone with administrator role
    if (($user['role'] ?? '') === 'admin') {
        $response['message'] = 'Invalid email or password';
        http_response_code(401);
        echo json_encode($response);
        exit;
    }
}

// ============================================
// VERIFY PASSWORD
// ============================================

if (!password_verify($password, $user['password'])) {
    $response['message'] = 'Invalid email or password';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// ============================================
// GENERATE & SEND OTP
// ============================================

$otp = (string)random_int(100000, 999999);
$expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

// FALLBACK LOGGING: Log OTP to server error log for local troubleshooting if email fails
error_log("BACKUP OTP for $email: $otp");

$stmt = $conn->prepare("INSERT INTO otp_verification (email, otp, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $otp, $expires_at);

if (!$stmt->execute()) {
    $response['message'] = 'System error generating OTP.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}
$stmt->close();

// Send OTP via Gmail SMTP using PHPMailer
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; 
    $mail->SMTPAuth   = true;
    
    // ==========================================
    // REQUIRED: REPLACE THESE WITH YOUR OWN DETAILS
    // ==========================================
    $mail->Username   = 'sustainu.environment@gmail.com'; 
    $mail->Password   = 'wjpctxittbftmggt'; 
    // ==========================================

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Bypass SSL verification for local environments where CA certificates are often missing
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Recipients
    $mail->setFrom('sustainu.environment@gmail.com', 'Sustain-U Security');
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Your Sustain-U Login OTP';
    $mail->Body    = "Hello,<br><br>Here is your One-Time Password to securely log in to Sustain-U:<br><br><h2 style='color:#0A58CA;'>$otp</h2><br>This code will expire in 5 minutes.<br><br>Best regards,<br>The Sustain-U Team.";
    $mail->AltBody = "Hello, your Sustain-U One-Time Password is: $otp. It expires in 5 minutes.";

    $mail->send();
    
    $response['success'] = true;
    $response['require_otp'] = true;
    $response['message'] = 'OTP sent to your email. Please verify.';
    http_response_code(200);
    echo json_encode($response);
    exit;
} catch (Exception $e) {
    $conn->query("DELETE FROM otp_verification WHERE email = '$email' AND otp = '$otp'");
    $response['message'] = 'Failed to send OTP email. Please report this error.';
    http_response_code(500);
    error_log("PHPMailer Error: {$mail->ErrorInfo}");
    echo json_encode($response);
    exit;
}
?>
