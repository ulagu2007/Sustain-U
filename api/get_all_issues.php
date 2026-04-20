<?php
/**
 * GET ALL ISSUES API (ADMIN ONLY)
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

$status_filter   = isset($_GET['status'])   ? trim($_GET['status'])   : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

$query = "
    SELECT i.id, u.name, u.email, i.category, i.type, i.urgency,
           i.building, i.floor, i.room,
           i.image_path, i.resolved_image_path,
           i.description, i.custom_description,
           i.status, i.created_at, i.resolved_at, i.report_path
    FROM issues i
    JOIN users u ON i.user_id = u.id
    WHERE 1=1
";

$params = [];
$types  = '';

if (!empty($status_filter)) {
    $query   .= " AND i.status = ?";
    $params[] = $status_filter;
    $types   .= 's';
}

if (!empty($category_filter)) {
    $query   .= " AND i.category = ?";
    $params[] = $category_filter;
    $types   .= 's';
}

$query .= " ORDER BY FIELD(i.urgency,'emergency','needs_attention','can_wait'), i.created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    logError('get_all_issues prepare failed: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    http_response_code(500);
    logError('get_all_issues execute failed: ' . $stmt->error);
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$result = $stmt->get_result();
$issues = [];

function normalizeImagePath($p) {
    if (!$p) return '';
    if (strpos($p, 'http') === 0) return $p;
    $p = ltrim($p, '/');
    $p = preg_replace('/^Sustain-U\//', '', $p);
    if (strpos($p, 'uploads/') === 0) return $p;
    return 'uploads/' . $p;
}

while ($row = $result->fetch_assoc()) {
    $parts    = array_filter([$row['building'] ?? '', $row['floor'] ?? '', $row['room'] ?? '']);
    $location = implode(', ', $parts) ?: 'Unspecified';
    $desc     = $row['description'] ?? $row['custom_description'] ?? '';

    $issues[] = [
        'id'            => $row['id'],
        'name'          => $row['name'],
        'student_name'  => $row['name'],
        'student_email' => $row['email'],
        'email'         => $row['email'],
        'category'      => $row['category'],
        'urgency'       => $row['urgency'],
        'building'      => $row['building'],
        'floor'         => $row['floor'],
        'room'          => $row['room'],
        'location'      => $location,
        'description'   => $desc,
        'image_path'    => normalizeImagePath($row['image_path'] ?? ''),
        'resolved_image'=> normalizeImagePath($row['resolved_image_path'] ?? ''),
        'status'        => $row['status'],
        'created_at'    => $row['created_at'],
        'resolved_at'   => $row['resolved_at'],
        'report_path'   => $row['report_path'],
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'issues'  => $issues,
    'data'    => $issues,
    'total'   => count($issues),
]);
