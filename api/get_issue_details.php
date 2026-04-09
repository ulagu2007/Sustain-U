<?php
/**
 * GET ISSUE DETAILS (single issue)
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
$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($issue_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid issue id']);
    exit;
}

// Build flexible select
$stmt = $conn->prepare("SELECT i.*, u.name as user_name, u.email as user_email, u.name, u.email, u.register_number, u.section FROM issues i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $issue_id);
$stmt->execute();
$res = $stmt->get_result();
$issue = $res->fetch_assoc();
$stmt->close();

if (!$issue) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Issue not found']);
    exit;
}

// Students may only view their own issues
if (!isAdmin() && $issue['user_id'] != ($_SESSION['user_id'] ?? 0)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

function normalizeImagePathLocal($p)
{
    if (!$p) return '';
    if (strpos($p, 'http') === 0) return $p;
    $p = ltrim($p, '/');
    $p = preg_replace('/^Sustain-U\//', '', $p);
    if (strpos($p, 'uploads/') === 0) return $p;
    return 'uploads/' . $p;
}

$data = [
    'id' => (int)$issue['id'],
    'category' => $issue['category'] ?? ($issue['type'] ?? 'unspecified'),
    'description' => $issue['description'] ?? ($issue['custom_description'] ?? ''),
    'location' => trim(implode(', ', array_filter([$issue['building'] ?? '', $issue['floor'] ?? '', $issue['room'] ?? '']))),
    'urgency' => $issue['urgency'] ?? null,
    'image_path' => normalizeImagePathLocal($issue['image_path'] ?? $issue['image_before'] ?? ''),
    'resolved_image' => normalizeImagePathLocal($issue['resolved_image_path'] ?? $issue['image_after'] ?? ''),
    'status' => $issue['status'] ?? 'submitted',
    'created_at' => $issue['created_at'] ?? null,
    'resolved_at' => $issue['resolved_at'] ?? null,
    'user_name' => $issue['user_name'] ?? null,
    'user_email' => $issue['user_email'] ?? null,
    // include `email` for frontend compatibility
    'email' => $issue['user_email'] ?? $issue['email'] ?? null,
    'resolution_notes' => $issue['resolution_notes'] ?? null,
    'register_number' => $issue['register_number'] ?? 'N/A',
    'section' => $issue['section'] ?? ''
];

echo json_encode(['success' => true, 'data' => $data]);
