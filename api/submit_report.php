<?php
// ============================================
// SUBMIT REPORT API ENDPOINT
// ============================================

require_once 'db.php';
session_start();

// ============================================
// PREVENT DIRECT BROWSER ACCESS
// ============================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// ============================================
// AUTHENTICATION CHECK
// ============================================

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$location = trim($_POST['location'] ?? '');
$urgency = trim($_POST['urgency'] ?? 'medium');

// Validation
if (empty($category) || empty($description) || empty($location)) {
    $response['message'] = 'All fields are required';
    echo json_encode($response);
    exit;
}

if (!in_array($urgency, ['low', 'medium', 'high'])) {
    $response['message'] = 'Invalid urgency level';
    echo json_encode($response);
    exit;
}

$image_path = null;

// Handle file upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $max_size = 5 * 1024 * 1024; // 5MB
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if ($file['size'] > $max_size) {
        $response['message'] = 'Image size must not exceed 5MB';
        echo json_encode($response);
        exit;
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        $response['message'] = 'Only image files are allowed (JPEG, PNG, GIF, WebP)';
        echo json_encode($response);
        exit;
    }
    
    // Check MIME type by file content
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        $response['message'] = 'Invalid image file';
        echo json_encode($response);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = 'issue_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $image_path = 'uploads/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
        $response['message'] = 'Failed to upload image';
        echo json_encode($response);
        exit;
    }
}

// Insert issue into database
$stmt = $conn->prepare("INSERT INTO issues (user_id, category, description, location, urgency, image_path) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $user_id, $category, $description, $location, $urgency, $image_path);

if (!$stmt->execute()) {
    $response['message'] = 'Failed to submit report';
    echo json_encode($response);
    exit;
}

// Add 5 points to user
$points_stmt = $conn->prepare("UPDATE users SET points = points + 5 WHERE id = ?");
$points_stmt->bind_param("i", $user_id);
$points_stmt->execute();

$response['success'] = true;
$response['message'] = 'Report submitted successfully! You earned 5 points.';

echo json_encode($response);
