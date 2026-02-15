<?php
/**
 * ============================================
 * PDF GENERATION API
 * ============================================
 * Uses FPDF to generate issue reports
 * FPDF must be installed via Composer or included manually
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

// Accept either 'issue_id' or legacy 'id' parameter
$issue_id = isset($_GET['issue_id']) ? (int)$_GET['issue_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if (empty($issue_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Issue ID required']);
    exit;
}

// ============================================
// FETCH ISSUE DATA
// ============================================

$stmt = $conn->prepare("
    SELECT i.*, u.name, u.email
    FROM issues i
    JOIN users u ON i.user_id = u.id
    WHERE i.id = ?
");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("i", $issue_id);
$stmt->execute();
$result = $stmt->get_result();
$issue = $result->fetch_assoc();
$stmt->close();

if (!$issue) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Issue not found']);
    exit;
}

// Authorization check - student can only access own issues, admin can access any
if (!isAdmin() && $issue['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

// ============================================
// CHECK IF FPDF IS AVAILABLE
// ============================================

if (!file_exists(REPORTS_DIR)) {
    mkdir(REPORTS_DIR, 0755, true);
}

// Try to include FPDF (install via: composer require fpdf/fpdf)
$fpdf_paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../fpdf/fpdf.php'
];

$fpdf_available = false;
foreach ($fpdf_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $fpdf_available = true;
        break;
    }
}

if (!$fpdf_available) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PDF library not installed. Install FPDF: composer require fpdf/fpdf'
    ]);
    exit;
}

// ============================================
// GENERATE PDF USING FPDF
// ============================================

try {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Issue Report #' . $issue['id'], 0, 1, 'C');
    $pdf->SetFont('Arial', '', 11);
    $pdf->Ln(5);

    // Student Information
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Student Information', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(40, 7, 'Name:');
    $pdf->Cell(0, 7, $issue['name'], 0, 1);
    $pdf->Cell(40, 7, 'Email:');
    $pdf->Cell(0, 7, $issue['email'], 0, 1);
    $pdf->Ln(5);

    // Issue Details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Issue Details', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(40, 7, 'Urgency:');
    $pdf->Cell(0, 7, ucfirst(str_replace('_', ' ', $issue['urgency'])), 0, 1);
    $pdf->Cell(40, 7, 'Building:');
    $pdf->Cell(0, 7, $issue['building'], 0, 1);
    $pdf->Cell(40, 7, 'Floor:');
    $pdf->Cell(0, 7, $issue['floor'], 0, 1);
    $pdf->Cell(40, 7, 'Room:');
    $pdf->Cell(0, 7, $issue['room'], 0, 1);
    $pdf->Ln(5);

    // Timeline
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Timeline', 0, 1);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(40, 7, 'Submitted:');
    $pdf->Cell(0, 7, $issue['created_at'], 0, 1);
    if ($issue['resolved_at']) {
        $pdf->Cell(40, 7, 'Resolved:');
        $pdf->Cell(0, 7, $issue['resolved_at'], 0, 1);
    }
    $pdf->Ln(5);

    // Status
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'Status: ' . ucfirst($issue['status']), 0, 1);

    // Filename for PDF
    $pdf_filename = 'issue_' . $issue['id'] . '_' . date('Y-m-d') . '.pdf';
    $pdf_path = REPORTS_DIR . $pdf_filename;

    // Save PDF
    $pdf->Output('F', $pdf_path);

    // Update database with PDF path
    $update_stmt = $conn->prepare("UPDATE issues SET report_path = ? WHERE id = ?");
    if ($update_stmt) {
        $pdf_filename_db = 'reports/' . $pdf_filename;
        $update_stmt->bind_param("si", $pdf_filename_db, $issue_id);
        $update_stmt->execute();
        $update_stmt->close();
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'PDF generated successfully',
        'pdf_path' => 'reports/' . $pdf_filename
    ]);

} catch (Exception $e) {
    http_response_code(500);
    logError('PDF generation failed: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'PDF generation failed: ' . $e->getMessage()
    ]);
}
