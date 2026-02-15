<?php
/**
 * SUSTAIN-U - Download Report PDF Endpoint
 * Generates and downloads PDF report for an issue
 */
require_once 'config.php';

// Check if user is admin
if (!isAdmin()) {
    http_response_code(403);
    die('Unauthorized');
}

$issue_id = intval($_GET['id'] ?? 0);
if (!$issue_id) {
    http_response_code(400);
    die('Invalid issue ID');
}

// This endpoint integrates with api/generate_pdf.php
// It provides a simpler wrapper for the PDF generation
header('Location: /Sustain-U/api/generate_pdf.php?id=' . $issue_id);
exit;
