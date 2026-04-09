<?php
/**
 * ============================================
 * GENERATE OTP API ENDPOINT
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
$response = ['success' => false, 'message' => ''];

// Parse input
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($input['email'] ?? $_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Valid email is required.';
    echo json_encode($response);
    exit;
}

// 1. Check if user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user_exists = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$user_exists) {
    $response['message'] = 'Email not registered. Please create an account first.';
    echo json_encode($response);
    exit;
}

// 2. Rate Limiting Check
// Max 3 requests per minute, 30s cooldown
$time_1min_ago = date('Y-m-d H:i:s', time() - 60);
$stmt = $conn->prepare("SELECT COUNT(*) as count, MAX(created_at) as last_request FROM otp_requests WHERE email = ? AND created_at > ?");
$stmt->bind_param("ss", $email, $time_1min_ago);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

$request_count = (int)($res['count'] ?? 0);
$last_request_time = $res['last_request'] ? strtotime($res['last_request']) : 0;
$time_since_last = time() - $last_request_time;

if ($request_count >= 3) {
    $response['message'] = 'Too many requests. Please try again later.';
    http_response_code(429);
    echo json_encode($response);
    exit;
}

if ($time_since_last < 30) {
    $response['message'] = 'Please wait ' . (30 - $time_since_last) . ' seconds before requesting a new OTP.';
    http_response_code(429);
    echo json_encode($response);
    exit;
}

// 3. Generate OTP and save to DB
$otp = (string)random_int(100000, 999999);
$expires_at = date('Y-m-d H:i:s', time() + 300); // 5 minutes expiry

$stmt = $conn->prepare("INSERT INTO otp_requests (email, otp, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $otp, $expires_at);

if (!$stmt->execute()) {
    $response['message'] = 'System error. Please try again.';
    echo json_encode($response);
    exit;
}
$stmt->close();

// 4. Trigger Background Email Worker (True Fire-and-Forget)
$php_path = 'php'; 
$worker_path = __DIR__ . '/send_mail_worker.php';
$subject = 'Your Login OTP - Sustain-U';

// Using COM to run the process silently and without waiting (Windows only)
if (class_exists('COM')) {
    $shell = new COM("WScript.Shell");
    $command = "$php_path \"$worker_path\" \"$email\" \"$otp\" \"$subject\"";
    $shell->Run($command, 0, false);
} else {
    // Fallback to pclose/popen if COM is disabled
    $command = "start /B $php_path \"$worker_path\" \"$email\" \"$otp\" \"$subject\" > nul 2>&1";
    pclose(popen($command, "r"));
}

// Respond immediately
$response['success'] = true;
$response['message'] = 'OTP sent successfully to your email.';
echo json_encode($response);
exit;
?>
