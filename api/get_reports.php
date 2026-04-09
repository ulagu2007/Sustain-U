<?php
// ============================================
// GET REPORTS API ENDPOINT
// ============================================

require_once 'db.php';
// session_start(); // Already started in config.php via db.php

header('Content-Type: application/json');
$response = ['success' => false, 'data' => [], 'message' => ''];

// ============================================
// PREVENT DIRECT BROWSER ACCESS
// ============================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    $response['message'] = 'Method Not Allowed';
    echo json_encode($response);
    exit;
}

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
$role = $_SESSION['user_role'] ?? '';
$type = $_GET['type'] ?? 'user'; // 'user' or 'all'

// Students can only view their own issues
if ($role !== 'admin' && $type === 'all') {
    $type = 'user';
}

// Flexible select: get all issue columns and map in PHP so API works with both schemas
if ($type === 'all') {
    $stmt = $conn->prepare("SELECT i.*, u.name, u.email FROM issues i JOIN users u ON i.user_id = u.id ORDER BY i.created_at DESC");
}
else {
    $stmt = $conn->prepare("SELECT i.*, u.name, u.email FROM issues i JOIN users u ON i.user_id = u.id WHERE i.user_id = ? ORDER BY i.created_at DESC");
    $stmt->bind_param("i", $user_id);
}

if (!$stmt) {
    http_response_code(500);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

$issues = [];
function normalizeImagePath($p)
{
    if (!$p) return '';
    if (strpos($p, 'http') === 0) return $p;

    // Remove leading slashes and base directory for portability
    $p = ltrim($p, '/');
    $p = preg_replace('/^Sustain-U\//', '', $p);
    
    if (strpos($p, 'uploads/') === 0) {
        return $p;
    }

    return 'uploads/' . $p;
}

while ($row = $result->fetch_assoc()) {
    $location = '';
    if (!empty($row['location'])) {
        $location = $row['location'];
    }
    elseif (!empty($row['building']) || !empty($row['floor']) || !empty($row['room'])) {
        $parts = array_filter([$row['building'] ?? '', $row['floor'] ?? '', $row['room'] ?? '']);
        $location = implode(', ', $parts);
    }

    $description = $row['description'] ?? ($row['custom_description'] ?? '');
    if (!$description && !empty($row['room'])) {
        $description = 'Reported at ' . $location;
    }

    $image = $row['image_path'] ?? $row['image_before'] ?? $row['image'] ?? '';

    $cat = $row['category'] ?? ($row['type'] ?? 'unspecified');
    if ($cat && strtolower($cat) === 'general') {
        if (!empty($row['type']))
            $cat = $row['type'];
        elseif (!empty($row['custom_description']))
            $cat = $row['custom_description'];
        else
            $cat = 'other';
    }

    $issues[] = [
        'id' => $row['id'],
        'user_id' => $row['user_id'],
        'name' => $row['name'] ?? 'Unknown User',
        'email' => $row['email'] ?? 'No Email',
        'category' => $cat,
        'description' => $description,
        'location' => $location,
        'urgency' => $row['urgency'] ?? null,
        'image_path' => normalizeImagePath($image),
        'resolved_image' => normalizeImagePath($row['resolved_image_path'] ?? $row['image_after'] ?? ''),
        'status' => $row['status'] ?? 'submitted',
        'created_at' => $row['created_at'] ?? null,
        'resolved_at' => $row['resolved_at'] ?? null,
        'report_path' => $row['report_path'] ?? null
    ];
}

$response['success'] = true;
$response['data'] = $issues;

echo json_encode($response);
