<?php
// ============================================
// SUBMIT REPORT API ENDPOINT (PRODUCTION READY)
// ============================================

// Start output buffering to prevent accidental output from breaking JSON response
ob_start();

try {
    require_once 'db.php';
    session_start();

    // ============================================
    // PREVENT DIRECT BROWSER ACCESS
    // ============================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        ob_end_flush();
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
        ob_end_flush();
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $urgency_input = trim($_POST['urgency'] ?? 'medium');

    // Validation
    if (empty($category) || empty($description) || empty($location)) {
        http_response_code(400);
        throw new Exception('All fields are required');
    }

    // Map 'low', 'medium', 'high' to the database enums if needed
    $valid_urgencies = ['can_wait', 'needs_attention', 'emergency'];
    if (in_array($urgency_input, $valid_urgencies)) {
        $urgency = $urgency_input;
    } else {
        $mapping = ['low' => 'can_wait', 'medium' => 'needs_attention', 'high' => 'emergency'];
        $urgency = $mapping[$urgency_input] ?? 'can_wait';
    }

    $image_path = null;

    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if ($file['size'] > $max_size) {
            http_response_code(400);
            throw new Exception('Image size must not exceed 5MB');
        }
        
        if (!in_array($file['type'], $allowed_types)) {
            http_response_code(400);
            throw new Exception('Only image files are allowed (JPEG, PNG, GIF, WebP)');
        }
        
        // Check MIME type by file content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            http_response_code(400);
            throw new Exception('Invalid image file');
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
            http_response_code(500);
            throw new Exception('Failed to upload image to server');
        }
    }

    // Map location to building as a fallback for legacy requests
    $building = $location; 
    $floor = trim($_POST['floor'] ?? 'N/A');
    $room = trim($_POST['room'] ?? 'N/A');
    $type = trim($_POST['type'] ?? $category); 

    // Insert issue into database: Aligned with Sustain-U PROD schema
    $stmt = $conn->prepare(
        "INSERT INTO issues (user_id, category, type, description, custom_description, building, floor, room, urgency, image_path, status, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', NOW())"
    );

    $custom_description = $description;
    $full_description = $description;

    $stmt->bind_param("isssssssss", 
        $user_id, 
        $category, 
        $type, 
        $full_description, 
        $custom_description, 
        $building, 
        $floor, 
        $room, 
        $urgency, 
        $image_path
    );

    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    // Add 5 points to user
    $points_stmt = $conn->prepare("UPDATE users SET points = points + 5 WHERE id = ?");
    $points_stmt->bind_param("i", $user_id);
    $points_stmt->execute();

    // Success response
    if (ob_get_length()) ob_clean(); 
    echo json_encode([
        'success' => true,
        'message' => 'Report submitted successfully! You earned 5 points.'
    ]);

} catch (Throwable $e) {
    // Catch-all for any error
    if (ob_get_length()) ob_clean();
    
    if (http_response_code() === 200) {
        http_response_code(500);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage() ?: 'An internal server error occurred.'
    ]);
}

ob_end_flush();
