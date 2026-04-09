<?php
/**
 * ============================================
 * COMPLETE PROFILE API
 * ============================================
 */

require_once '../config.php';
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

if (!isLoggedIn()) {
    http_response_code(401);
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

// Validate Input
$input = json_decode(file_get_contents('php://input'), true);

$register_number = trim($input['register_number'] ?? '');
$degree = trim($input['degree'] ?? '');
$department = trim($input['department'] ?? '');
$mobile_number = trim($input['mobile_number'] ?? '');
$section = trim($input['section'] ?? ''); // Optional/Hidden
$other_details = trim($input['other_details'] ?? '');

if (empty($register_number) || empty($degree) || empty($department) || empty($mobile_number)) {
    http_response_code(400);
    $response['message'] = 'All fields are mandatory';
    echo json_encode($response);
    exit;
}

// Phone: must be exactly 10 digits, no letters or symbols
if (!preg_match('/^[0-9]{10}$/', $mobile_number)) {
    http_response_code(400);
    $response['message'] = 'Phone number must be exactly 10 digits (numbers only).';
    echo json_encode($response);
    exit;
}

// Update Database
$stmt = $conn->prepare("UPDATE users SET register_number = ?, degree = ?, department = ?, phone = ?, section = ?, other_details = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

// Bind params: register_number(s), degree(s), department(s), phone(s), section(s), other_details(s), user_id(i)
$stmt->bind_param("ssssssi", $register_number, $degree, $department, $mobile_number, $section, $other_details, $user_id);

if ($stmt->execute()) {
    // Mark session as completed so user can access dashboard immediately
    $_SESSION['profile_complete'] = true;
    $response['success'] = true;
    $response['message'] = 'Profile updated successfully';
}
else {
    http_response_code(500);
    logError('Execute failed: ' . $stmt->error);
    $response['message'] = 'Failed to update profile';
    echo json_encode($response);
}

$stmt->close();
echo json_encode($response);
