<?php
/**
 * ============================================
 * UPDATE ISSUE STATUS API (ADMIN ONLY)
 * ============================================
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
$response = ['success' => false, 'message' => ''];

// ============================================
// VALIDATE INPUT
// ============================================

$issue_id = isset($_POST['issue_id']) ? (int)$_POST['issue_id'] : 0;
$status = trim($_POST['status'] ?? '');

if (empty($issue_id) || empty($status)) {
    $response['message'] = 'Issue ID and status are required';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$valid_statuses = ['submitted', 'in_progress', 'resolved'];
if (!in_array($status, $valid_statuses)) {
    $response['message'] = 'Invalid status';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// ============================================
// UPDATE STATUS
// ============================================

if ($status === 'resolved') {
    $stmt = $conn->prepare("UPDATE issues SET status = ?, resolved_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $issue_id);
} else {
    $stmt = $conn->prepare("UPDATE issues SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $issue_id);
}

if (!$stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

if (!$stmt->execute()) {
    http_response_code(500);
    logError('Execute failed: ' . $stmt->error);
    $stmt->close();
    $response['message'] = 'Failed to update status';
    echo json_encode($response);
    exit;
}

if ($stmt->affected_rows === 0) {
    $response['message'] = 'Issue not found';
    http_response_code(404);
    $stmt->close();
    echo json_encode($response);
    exit;
}

$stmt->close();
// If resolved, generate PDF report automatically
if ($status === 'resolved') {
    // Fetch issue with user info
    $q = $conn->prepare("SELECT i.*, u.name, u.email FROM issues i JOIN users u ON i.user_id = u.id WHERE i.id = ?");
    if ($q) {
        $q->bind_param('i', $issue_id);
        $q->execute();
        $res = $q->get_result();
        $issue = $res->fetch_assoc();
        $q->close();

        if ($issue) {
            if (!file_exists(REPORTS_DIR)) { mkdir(REPORTS_DIR, 0755, true); }

            // Try to include FPDF
            $fpdf_paths = [__DIR__ . '/../vendor/autoload.php', __DIR__ . '/../fpdf/fpdf.php'];
            $fpdf_available = false;
            foreach ($fpdf_paths as $path) {
                if (file_exists($path)) { require_once $path; $fpdf_available = true; break; }
            }

            if ($fpdf_available) {
                try {
                    $pdf = new FPDF();
                    $pdf->AddPage();
                    $pdf->SetFont('Arial', 'B', 16);
                    $pdf->Cell(0, 10, 'Issue Report #' . $issue['id'], 0, 1, 'C');
                    $pdf->SetFont('Arial', '', 11);
                    $pdf->Ln(5);

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 8, 'Student Information', 0, 1);
                    $pdf->SetFont('Arial', '', 11);
                    $pdf->Cell(40, 7, 'Name:'); $pdf->Cell(0,7, $issue['name'], 0,1);
                    $pdf->Cell(40, 7, 'Email:'); $pdf->Cell(0,7, $issue['email'], 0,1);
                    $pdf->Ln(5);

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 8, 'Issue Details', 0, 1);
                    $pdf->SetFont('Arial', '', 11);
                    $pdf->Cell(40, 7, 'Urgency:'); $pdf->Cell(0,7, ucfirst(str_replace('_',' ',$issue['urgency'])),0,1);
                    $pdf->Cell(40, 7, 'Building:'); $pdf->Cell(0,7, $issue['building'],0,1);
                    $pdf->Cell(40, 7, 'Floor:'); $pdf->Cell(0,7, $issue['floor'],0,1);
                    $pdf->Cell(40, 7, 'Room:'); $pdf->Cell(0,7, $issue['room'],0,1);
                    $pdf->Ln(5);

                    $pdf->SetFont('Arial', 'B', 12);
                    $pdf->Cell(0, 8, 'Timeline', 0, 1);
                    $pdf->SetFont('Arial', '', 11);
                    $pdf->Cell(40,7,'Submitted:'); $pdf->Cell(0,7, $issue['created_at'],0,1);
                    if (!empty($issue['resolved_at'])) { $pdf->Cell(40,7,'Resolved:'); $pdf->Cell(0,7, $issue['resolved_at'],0,1); }

                    $pdf_filename = 'issue_' . $issue['id'] . '_' . date('Y-m-d') . '.pdf';
                    $pdf_path = REPORTS_DIR . $pdf_filename;
                    $pdf->Output('F', $pdf_path);

                    // Update DB report_path
                    $u = $conn->prepare("UPDATE issues SET report_path = ? WHERE id = ?");
                    if ($u) {
                        $dbpath = 'reports/' . $pdf_filename;
                        $u->bind_param('si', $dbpath, $issue_id);
                        $u->execute();
                        $u->close();
                    }
                } catch (Exception $e) {
                    logError('Auto PDF generation failed: ' . $e->getMessage());
                }
            }
        }
    }
}

http_response_code(200);
$response['success'] = true;
$response['message'] = 'Status updated successfully';
echo json_encode($response);

$issue_id = intval($_POST['issue_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$action = trim($_POST['action'] ?? 'update'); // 'update' or 'delete'

if ($issue_id <= 0) {
    $response['message'] = 'Invalid issue ID';
    echo json_encode($response);
    exit;
}

if ($action === 'delete') {
    // Delete issue
    $stmt = $conn->prepare("DELETE FROM issues WHERE id = ?");
    $stmt->bind_param("i", $issue_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Issue deleted successfully';
    } else {
        $response['message'] = 'Failed to delete issue';
    }
} else {
    // Update status
    if (!in_array($status, ['submitted', 'in_progress', 'resolved'])) {
        $response['message'] = 'Invalid status';
        echo json_encode($response);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE issues SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $issue_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Status updated successfully';
    } else {
        $response['message'] = 'Failed to update status';
    }
}

echo json_encode($response);
