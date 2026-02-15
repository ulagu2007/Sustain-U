<?php
/**
 * ============================================
 * UPLOAD RESOLUTION IMAGE API (ADMIN ONLY)
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

if (!isAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// ============================================
// VALIDATE INPUT
// ============================================

$issue_id = isset($_POST['issue_id']) ? (int)$_POST['issue_id'] : 0;

if (empty($issue_id)) {
    $response['message'] = 'Issue ID is required';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['image_after']) || $_FILES['image_after']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Image upload failed';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$file = $_FILES['image_after'];

// Validate file size and type
if ($file['size'] > MAX_UPLOAD_SIZE) {
    $response['message'] = 'File size exceeds limit (max 5MB)';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

if (!in_array($file['type'], ALLOWED_MIME_TYPES)) {
    $response['message'] = 'Invalid file type';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Create uploads directory if needed
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Generate unique filename
$filename = 'issue_' . $issue_id . '_after_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
$filepath = UPLOAD_DIR . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    logError('File move failed for issue ' . $issue_id);
    $response['message'] = 'File upload error';
    echo json_encode($response);
    exit;
}

// ============================================
// UPDATE ISSUE WITH AFTER IMAGE
// ============================================

$stmt = $conn->prepare("UPDATE issues SET image_after = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    @unlink($filepath);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$stmt->bind_param("si", $filename, $issue_id);

if (!$stmt->execute()) {
    http_response_code(500);
    logError('Execute failed: ' . $stmt->error);
    @unlink($filepath);
    $stmt->close();
    $response['message'] = 'Failed to update issue';
    echo json_encode($response);
    exit;
}

$stmt->close();

http_response_code(200);
$response['success'] = true;
$response['message'] = 'Resolution image uploaded successfully';
echo json_encode($response);
