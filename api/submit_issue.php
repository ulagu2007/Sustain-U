<?php
/**
 * ============================================
 * SUBMIT ISSUE API ENDPOINT
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

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];
$user_id = $_SESSION['user_id'];

// ============================================
// VALIDATE INPUT
// ============================================

// New student flow fields
$urgency = trim($_POST['urgency'] ?? '');
$building = trim($_POST['building'] ?? '');
$floor = trim($_POST['floor'] ?? '');
$room = trim($_POST['room'] ?? '');

if (empty($urgency) || empty($building) || empty($floor) || empty($room)) {
    $response['message'] = 'All fields are required';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Validate urgency
$valid_urgencies = ['can_wait', 'needs_attention', 'emergency'];
if (!in_array($urgency, $valid_urgencies)) {
    $response['message'] = 'Invalid urgency value';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// ============================================
// HANDLE FILE UPLOAD
// ============================================

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'Image upload failed';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$file = $_FILES['image'];

// Check file size
if ($file['size'] > MAX_UPLOAD_SIZE) {
    $response['message'] = 'File size exceeds limit (max 5MB)';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Check MIME type
if (!in_array($file['type'], ALLOWED_MIME_TYPES)) {
    $response['message'] = 'Invalid file type. Only images allowed';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Verify actual file content
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$actual_mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($actual_mime, ALLOWED_MIME_TYPES)) {
    $response['message'] = 'Invalid file content';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Generate unique filename
$filename = 'issue_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
$filepath = UPLOAD_DIR . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    http_response_code(500);
    logError('File move failed for user ' . $user_id);
    $response['message'] = 'File upload error';
    echo json_encode($response);
    exit;
}

// ============================================
// INSERT ISSUE INTO DATABASE
// ============================================

$status = 'submitted';
// Insert into issues table with required columns
$stmt = $conn->prepare("INSERT INTO issues (user_id, image_path, building, floor, room, urgency, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

// Correct parameter type string (one integer + six strings)
if (!$stmt->bind_param("issssss", $user_id, $filename, $building, $floor, $room, $urgency, $status)) {
    http_response_code(500);
    logError('Bind param failed', ['conn_error' => $conn->error]);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
} 

if (!$stmt->execute()) {
    http_response_code(500);
    logError('Execute failed: ' . $stmt->error);
    @unlink($filepath);
    $response['message'] = 'Failed to save issue';
    echo json_encode($response);
    exit;
}

$issue_id = $stmt->insert_id;
$stmt->close();

// === Persist optional fields (category / custom_description) if DB supports them ===
$category = trim($_POST['category'] ?? '');
$custom_description = trim($_POST['custom_description'] ?? '');

$updateParts = [];
$updateTypes = '';
$updateVals = [];

// check available columns
$colsRes = $conn->query("SHOW COLUMNS FROM issues");
$cols = [];
if ($colsRes) {
    while ($c = $colsRes->fetch_assoc()) {
        $cols[] = $c['Field'];
    }
}

if (!empty($category) && in_array('category', $cols)) {
    $updateParts[] = 'category = ?';
    $updateTypes .= 's';
    $updateVals[] = $category;
}
if (!empty($custom_description) && in_array('description', $cols)) {
    $updateParts[] = 'description = ?';
    $updateTypes .= 's';
    $updateVals[] = $custom_description;
}

if (!empty($updateParts)) {
    $updateSql = 'UPDATE issues SET ' . implode(', ', $updateParts) . ' WHERE id = ?';
    $updateStmt = $conn->prepare($updateSql);
    if ($updateStmt) {
        $updateTypes .= 'i';
        $updateVals[] = $issue_id;
        $updateStmt->bind_param($updateTypes, ...$updateVals);
        $updateStmt->execute();
        $updateStmt->close();
    }
}

// ============================================
// ADD POINTS TO USER
// ============================================

$points_stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
if ($points_stmt) {
    $points = POINTS_PER_ISSUE;
    $points_stmt->bind_param("ii", $points, $user_id);
    $points_stmt->execute();
    $points_stmt->close();
}

// ============================================
// RETURN SUCCESS
// ============================================

http_response_code(201);
$response['success'] = true;
$response['message'] = 'Issue submitted successfully';
$response['issue_id'] = $issue_id;
$response['points_awarded'] = POINTS_PER_ISSUE;

echo json_encode($response);
