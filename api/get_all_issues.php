<?php
/**
 * ============================================
 * GET ALL ISSUES API (ADMIN ONLY)
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

if (!isAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build query
$query = "
    SELECT i.id, u.name, u.email, i.category, i.urgency, i.building, i.floor, i.area, 
           i.image_before, i.image_after, i.status, i.created_at, i.resolved_at, i.report_path
    FROM issues i
    JOIN users u ON i.user_id = u.id
    WHERE 1=1
";

$params = [];
$types = '';

if (!empty($status_filter)) {
    $query .= " AND i.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($category_filter)) {
    $query .= " AND i.category = ?";
    $params[] = $category_filter;
    $types .= 's';
}

$query .= " ORDER BY i.created_at DESC";

$stmt = $conn->prepare($query);

if (!$stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    http_response_code(500);
    logError('Execute failed: ' . $stmt->error);
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$result = $stmt->get_result();
$issues = [];

while ($row = $result->fetch_assoc()) {
    $issues[] = [
        'id' => $row['id'],
        'student_name' => $row['name'],
        'student_email' => $row['email'],
        'category' => $row['category'],
        'urgency' => $row['urgency'],
        'building' => $row['building'],
        'floor' => $row['floor'],
        'area' => $row['area'],
        'image_before' => normalizeImagePath($row['image_before'] ?? $row['image_path'] ?? ''),
        'resolved_image' => normalizeImagePath($row['resolved_image_path'] ?? $row['image_after'] ?? ''),
echo json_encode([
    'success' => true,
    'issues' => $issues,
    'total' => count($issues)
]);
