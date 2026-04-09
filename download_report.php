<?php
/**
 * SUSTAIN-U - Download Report PDF Endpoint
 * Generates and downloads PDF report for an issue
 */
require_once 'config.php';
ob_start();

// Check if user is logged in
if (!isLoggedIn()) {
    ob_end_clean();
    http_response_code(403);
    die('Unauthorized');
}

$issue_id = intval($_GET['id'] ?? 0);
if (!$issue_id) {
    ob_end_clean();
    http_response_code(400);
    die('Invalid issue ID');
}

// Redirect to the hardened PDF generator (relative path for ngrok compatibility)
ob_end_clean();
header('Location: api/generate_pdf.php?id=' . $issue_id);
exit;