<?php
/**
 * ============================================
 * GET STUDENT ISSUES API
 * ============================================
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
$user_id = $_SESSION['user_id'];

// Use a flexible SELECT and map fields so this API works regardless of schema variations
$stmt = $conn->prepare("SELECT i.* FROM issues i WHERE i.user_id = ? ORDER BY i.created_at DESC");
if (!$stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    http_response_code(500);
    logError('Execute failed: ' . $stmt->error);
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$result = $stmt->get_result();
$issues = [];

function normalizeImagePath($p) {
    if (!$p) return '';
    if (strpos($p, '/Sustain-U/') === 0 || strpos($p, 'uploads/') === 0) return $p;
    return '/Sustain-U/uploads/' . ltrim($p, '/');
}

while ($row = $result->fetch_assoc()) {
    $location = '';
    if (!empty($row['location'])) {
        $location = $row['location'];
    } elseif (!empty($row['building']) || !empty($row['floor']) || !empty($row['room'])) {
        $parts = array_filter([$row['building'] ?? '', $row['floor'] ?? '', $row['room'] ?? '']);
        $location = implode(', ', $parts);
    }

    $description = $row['description'] ?? ($row['custom_description'] ?? '');
    if (!$description && !empty($row['room'])) {
        $description = 'Reported at ' . $location;
    }

    $image = $row['image_path'] ?? $row['image_before'] ?? $row['image'] ?? '';

    $issues[] = [
        'id' => $row['id'],
        'category' => $row['category'] ?? ($row['type'] ?? 'unspecified'),
        'description' => $description,
        'location' => $location,
        'building' => $row['building'] ?? null,
        'floor' => $row['floor'] ?? null,
        'room' => $row['room'] ?? null,
        'urgency' => $row['urgency'] ?? null,
        'image_path' => normalizeImagePath($image),
        'status' => $row['status'] ?? 'submitted',
        'created_at' => $row['created_at'] ?? null,
        'resolved_at' => $row['resolved_at'] ?? null,
        'report_path' => $row['report_path'] ?? null
    ];
}

$stmt->close();

http_response_code(200);
echo json_encode([
    'success' => true,
    'data' => $issues,
    'total' => count($issues)
]);
