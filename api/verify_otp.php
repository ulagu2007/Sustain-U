<?php
/**
 * ============================================
 * VERIFY OTP API ENDPOINT
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

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$email = trim($input['email'] ?? $_POST['email'] ?? '');
$otp = trim($input['otp'] ?? $_POST['otp'] ?? '');

if (empty($email) || empty($otp)) {
    $response['message'] = 'Email and OTP are required.';
    echo json_encode($response);
    exit;
}

// Ensure user still exists
$stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $response['message'] = 'User no longer exists.';
    echo json_encode($response);
    exit;
}

// 1. Verify OTP
$stmt = $conn->prepare("SELECT * FROM otp_verification WHERE email=? AND otp=? ORDER BY expires_at DESC LIMIT 1");
$stmt->bind_param("ss", $email, $otp);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$record) {
    $response['message'] = 'Invalid OTP.';
    echo json_encode($response);
    exit;
}

$now = date("Y-m-d H:i:s");
if ($now > $record['expires_at']) {
    $response['message'] = 'OTP has expired. Please log in again.';
    echo json_encode($response);
    exit;
}

// Delete used OTP
$conn->query("DELETE FROM otp_verification WHERE email='$email' AND otp='$otp'");

// 2. CREATE SESSION
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['login_time'] = time();
session_regenerate_id(true);

$profileComplete = check_profile_completion($conn, $user['id']);
$_SESSION['profile_complete'] = $profileComplete;


$response['success'] = true;
$response['message'] = 'Login successful!';
if ($user['role'] === 'admin') {
    $response['redirect'] = 'admin_dashboard.php';
} else {
    // Robust check for profile completion
    $isComplete = ($profileComplete === true);
    $response['redirect'] = $isComplete ? 'index.php' : 'complete_profile.php';
}

echo json_encode($response);
?>
