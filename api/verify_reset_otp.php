<?php
/**
 * ============================================
 * VERIFY RESET OTP API ENDPOINT
 * ============================================
 * Verifies OTP before allowing password reset fields to show
 */

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

if (empty($email) || empty($otp)) {
    $response['message'] = 'Email and OTP are required.';
    echo json_encode($response);
    exit;
}

// 1. Verify OTP
try {
    $stmt = $conn->prepare("SELECT email FROM otp_verification WHERE email = ? AND otp = ? AND expires_at > NOW() LIMIT 1");
    if (!$stmt) {
        logError('Verify Reset OTP: Prep Failed', ['error' => $conn->error]);
        echo json_encode(['success' => false, 'message' => 'Database prepare error.']);
        exit;
    }
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $otp_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    logError('Verify Reset OTP: Exception caught', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => 'Database exception occurred.']);
    exit;
}

if ($otp_record) {
    $response['success'] = true;
    $response['message'] = 'OTP verified successfully. You can now reset your password.';
} else {
    $response['message'] = 'Invalid or expired OTP.';
}

echo json_encode($response);
