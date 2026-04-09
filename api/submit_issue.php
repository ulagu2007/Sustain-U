<?php
/**
 * ============================================
 * SUBMIT ISSUE API ENDPOINT (PRODUCTION READY)
 * ============================================
 */

// Start output buffering to prevent accidental output from breaking JSON response
ob_start();

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/db.php';

    // We don't want any non-JSON output
    header('Content-Type: application/json');

    // ============================================
    // METHOD & AUTH CHECK
    // ============================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        ob_end_flush();
        exit;
    }

    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        ob_end_flush();
        exit;
    }

    $user_id = $_SESSION['user_id'];

    if (!check_profile_completion($conn, $user_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Incomplete Profile. Please complete your profile first.']);
        ob_end_flush();
        exit;
    }

    // ============================================
    // VALIDATE INPUT FIELDS
    // ============================================
    $urgency_input = trim($_POST['urgency'] ?? 'can_wait');
    $building = trim($_POST['building'] ?? '') ?: 'Unspecified';
    $floor = trim($_POST['floor'] ?? '') ?: 'Unspecified';
    $room = trim($_POST['room'] ?? '') ?: 'Unspecified';
    $type = trim($_POST['type'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $custom_description = trim($_POST['custom_description'] ?? '');

    // Map urgency enums for database safety
    $valid_urgencies = ['can_wait', 'needs_attention', 'emergency'];
    if (in_array($urgency_input, $valid_urgencies)) {
        $urgency = $urgency_input;
    } else {
        $mapping = ['low' => 'can_wait', 'medium' => 'needs_attention', 'high' => 'emergency'];
        $urgency = $mapping[$urgency_input] ?? 'can_wait';
    }

    // ============================================
    // HANDLE FILE UPLOAD
    // ============================================
    if (!isset($_FILES['image'])) {
        http_response_code(400);
        throw new Exception('No image provided.');
    }

    $file = $_FILES['image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        throw new Exception('Upload error (code ' . $file['error'] . ').');
    }

    if ($file['size'] > (5 * 1024 * 1024)) { // 5MB limit
        http_response_code(400);
        throw new Exception('File too large. Maximum allowed size is 5MB.');
    }

    // SAFE MIME TYPE CHECK (Will not crash if extension is missing)
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $actual_mime = $file['type']; 
    if (function_exists('finfo_open')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $actual_mime = @finfo_file($finfo, $file['tmp_name']);
            @finfo_close($finfo);
        }
    }

    if (!in_array($actual_mime, $allowed_mimes)) {
        http_response_code(400);
        throw new Exception('Invalid file type. Only JPEG, PNG, and WebP images are allowed.');
    }

    // Ensure uploads directory exists
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Server error: cannot create upload directory.');
        }
    }

    // Generate secure unique filename
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'issue_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . ($ext ?: 'jpg');
    $filepath = $upload_dir . $filename;

    if (!@move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        throw new Exception('Could not save the uploaded file. Please check folder permissions.');
    }

    // ============================================
    // DATABASE TRANSACTION
    // ============================================
    $conn->begin_transaction();

    try {
        // Prepare description
        $full_description = $custom_description;
        if ($type) {
            $full_description = "[" . strtoupper($type) . "] " . $full_description;
        }

        // Insert issue: Aligned with production schema (description + custom_description)
        $stmt = $conn->prepare(
            "INSERT INTO issues (user_id, image_path, category, type, description, custom_description, building, floor, room, urgency, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', NOW())"
        );
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("isssssssss", $user_id, $filename, $category, $type, $full_description, $custom_description, $building, $floor, $room, $urgency);
        
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        
        $issue_id = $stmt->insert_id;
        $stmt->close();

        // Award points (5 points per issue)
        $pts_stmt = $conn->prepare("UPDATE users SET points = points + 5 WHERE id = ?");
        if ($pts_stmt) {
            $pts_stmt->bind_param("i", $user_id);
            $pts_stmt->execute();
            $pts_stmt->close();
        }

        $conn->commit();

        ob_clean(); // Clear any accidental output before sending JSON
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Issue submitted successfully!',
            'issue_id' => $issue_id,
        ]);

    } catch (Exception $db_e) {
        $conn->rollback();
        if (file_exists($filepath)) @unlink($filepath);
        throw $db_e;
    }

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    if (http_response_code() === 200) http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage() ?: 'An internal server error occurred.'
    ]);
}

ob_end_flush();
