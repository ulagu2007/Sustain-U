<?php
/**
 * ============================================
 * RESET PASSWORD API ENDPOINT
 * ============================================
 * Verifies OTP and updates password
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
$otp = trim($input['otp'] ?? '');
$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

// 1. Basic Validation
if (empty($email) || empty($otp) || empty($new_password) || empty($confirm_password)) {
    $response['message'] = 'All fields are required.';
    echo json_encode($response);
    exit;
}

if ($new_password !== $confirm_password) {
    $response['message'] = 'Passwords do not match.';
    echo json_encode($response);
    exit;
}

if (strlen($new_password) < 6) {
    $response['message'] = 'Password must be at least 6 characters long.';
    echo json_encode($response);
    exit;
}

// 1. Verify OTP one last time
try {
    $stmt = $conn->prepare("SELECT email FROM otp_verification WHERE email = ? AND otp = ? AND expires_at > NOW() LIMIT 1");
    if (!$stmt) {
        logError('Reset Password: Prep Failed (Verify OTP)', ['error' => $conn->error]);
        echo json_encode(['success' => false, 'message' => 'Database error (prep-verify).']);
        exit;
    }
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $otp_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    logError('Reset Password: Exception (Verify OTP)', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Database exception (verify).']);
    exit;
}

if (!$otp_record) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP.']);
    exit;
}

// 2. Update Password
try {
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    if (!$stmt) {
        logError('Reset Password: Prep Failed (Update Pass)', ['error' => $conn->error]);
        echo json_encode(['success' => false, 'message' => 'Database error (prep-update).']);
        exit;
    }
    $stmt->bind_param("ss", $hashed_password, $email);

    if ($stmt->execute()) {
        $stmt->close();
        // 3. Delete used OTP
        $conn->query("DELETE FROM otp_verification WHERE email = '" . $conn->real_escape_string($email) . "'");
        
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully! You can now login with your new password.'
        ]);
    } else {
        logError('Reset Password: Execute Failed (Update Pass)', ['error' => $stmt->error]);
        echo json_encode(['success' => false, 'message' => 'Database error (execute-update).']);
        $stmt->close();
    }
} catch (Exception $e) {
    logError('Reset Password: Exception (Update Pass)', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Database exception (update).']);
    exit;
}
