<?php
/**
 * ============================================
 * GET USER PROFILE API
 * ============================================
 */

require_once '../config.php';
require_once 'db.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT name as full_name, email, department, phone, section, register_number, degree, points, created_at FROM users WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    http_response_code(404);
    $response['message'] = 'User not found';
    echo json_encode($response);
    exit;
}

// Fetch stats
$stats_stmt = $conn->prepare("SELECT 
    COUNT(*) as total_issues,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_issues
    FROM issues WHERE user_id = ?");
$stats_stmt->bind_param("i", $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$user['total_issues'] = $stats['total_issues'];
$user['resolved_issues'] = $stats['resolved_issues'];

$response['success'] = true;
$response['data'] = $user;
echo json_encode($response);
