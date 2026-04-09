<?php
/**
 * ============================================
 * UPDATE ISSUE STATUS API (ADMIN ONLY)
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

// ============================================
// AUTHENTICATION CHECK
// ============================================

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Admins only']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// ============================================
// VALIDATE INPUT
// ============================================

$issue_id = isset($_POST['issue_id']) ? (int)$_POST['issue_id'] : 0;
$action = $_POST['action'] ?? 'update'; // 'update' or 'delete'

if ($issue_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid issue ID']);
    exit;
}

// ============================================
// ACTION: DELETE
// ============================================

if ($action === 'delete') {
    $stmt = $conn->prepare("DELETE FROM issues WHERE id = ?");
    $stmt->bind_param("i", $issue_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Issue deleted successfully']);
    }
    else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete issue']);
    }
    $stmt->close();
    exit;
}

// ============================================
// ACTION: UPDATE STATUS
// ============================================

// ============================================
// ACTION: UPDATE STATUS
// ============================================

$status = trim($_POST['status'] ?? '');
$valid_statuses = ['submitted', 'in_progress', 'resolved'];

// Log debug info
// file_put_contents(__DIR__ . '/../logs/status_debug.log', date('Y-m-d H:i:s') . " - Updating issue $issue_id to status: $status by user " . $_SESSION['user_id'] . "\n", FILE_APPEND);

if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Check authorization (admins only for now)
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// Handle Image Upload for "Resolved" status
$resolved_image_path = null;

if ($status === 'resolved') {
    // Check if new image provided
    if ((!isset($_FILES['resolved_image']) || $_FILES['resolved_image']['error'] !== UPLOAD_ERR_OK) && (!isset($_FILES['resolution_image']) || $_FILES['resolution_image']['error'] !== UPLOAD_ERR_OK)) {
        // If no new image, check if one already exists
        $check = $conn->query("SELECT resolved_image_path, image_after FROM issues WHERE id = $issue_id");
        $has_existing = false;
        if ($check) {
            $existing = $check->fetch_assoc();
            if (!empty($existing['resolved_image_path']) || !empty($existing['image_after'])) {
                $has_existing = true;
            }
        }

        if (!$has_existing) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Resolution image is required for resolved status']);
            exit;
        }
    }
    else {
        // Process new Upload
        $file = isset($_FILES['resolved_image']) ? $_FILES['resolved_image'] : $_FILES['resolution_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
            exit;
        }

        $uploadDir = __DIR__ . '/../uploads/resolutions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'resolved_' . $issue_id . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $resolved_image_path = 'uploads/resolutions/' . $filename;
        }
        else {
            logError("Failed to upload resolution image for issue $issue_id");
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to upload resolution image']);
            exit;
        }
    }
}

// Execute Update Logic
try {
    if ($status === 'resolved') {
        // Update to resolved
        if ($resolved_image_path) {
            // Update with image
            // Try to detect column name safely
            $colsRes = $conn->query("SHOW COLUMNS FROM issues");
            $cols = [];
            while ($c = $colsRes->fetch_assoc())
                $cols[] = $c['Field'];

            if (in_array('resolved_image_path', $cols)) {
                $stmt = $conn->prepare("UPDATE issues SET status = ?, resolved_at = NOW(), resolved_image_path = ? WHERE id = ?");
                $stmt->bind_param("ssi", $status, $resolved_image_path, $issue_id);
            }
            elseif (in_array('image_after', $cols)) {
                $stmt = $conn->prepare("UPDATE issues SET status = ?, resolved_at = NOW(), image_after = ? WHERE id = ?");
                $stmt->bind_param("ssi", $status, $resolved_image_path, $issue_id);
            }
            else {
                // No image column but file uploaded? Just update status
                $stmt = $conn->prepare("UPDATE issues SET status = ?, resolved_at = NOW() WHERE id = ?");
                $stmt->bind_param("si", $status, $issue_id);
            }
        }
        else {
            // Just status update (existing image check passed)
            $stmt = $conn->prepare("UPDATE issues SET status = ?, resolved_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $status, $issue_id);
        }
    }
    else {
        // Update to submitted/in_progress
        // Explicitly set resolved_at to NULL if reverting? (Optional, skipping for simplicity)
        $stmt = $conn->prepare("UPDATE issues SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $issue_id);
    }

    if ($stmt->execute()) {
        // Handle resolution notes separately if provided
        if ($status === 'resolved' && isset($_POST['resolution_notes'])) {
            $notes = trim($_POST['resolution_notes']);
            if ($notes !== '') {
                $colsRes = $conn->query("SHOW COLUMNS FROM issues");
                $cols = [];
                while ($c = $colsRes->fetch_assoc())
                    $cols[] = $c['Field'];

                if (in_array('resolution_notes', $cols)) {
                    $nstmt = $conn->prepare("UPDATE issues SET resolution_notes = ? WHERE id = ?");
                    $nstmt->bind_param('si', $notes, $issue_id);
                    $nstmt->execute();
                    $nstmt->close();
                }
            }
        }

        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    }
    else {
        throw new Exception($stmt->error);
    }
    $stmt->close();

}
catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
