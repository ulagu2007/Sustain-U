<?php
/**
 * SUSTAIN-U - PDF REPORT GENERATOR
 */
require_once __DIR__ . '/../config.php';
ob_start(); // Prevent any accidental whitespace from corrupting the PDF
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';

// Auth check
if (!isLoggedIn()) {
    die("Unauthorized access");
}

$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($issue_id <= 0) {
    die("Invalid Issue ID");
}

// Fetch issue details
$stmt = $conn->prepare("SELECT i.*, u.name as user_name, u.email as user_email, u.register_number, u.section 
                        FROM issues i 
                        JOIN users u ON i.user_id = u.id 
                        WHERE i.id = ?");
$stmt->bind_param('i', $issue_id);
$stmt->execute();
$issue = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$issue) {
    die("Issue not found");
}

// Access check
if (!isAdmin() && $issue['user_id'] != $_SESSION['user_id']) {
    die("Forbidden");
}

// Status check: Only resolved issues can generate a report
if ($issue['status'] !== 'resolved') {
    die("Error: Report can only be generated for resolved issues.");
}

class IssueReportPDF extends FPDF {
    function Header() {
        // Logo in top right
        $logo = __DIR__ . '/../assets/bg-srmlogo.jpg.png';
        if (file_exists($logo)) {
            $this->Image($logo, 160, 10, 35);
        }

        // App Title
        $this->SetFont('Arial', 'B', 22);
        $this->SetTextColor(26, 115, 232);
        $this->Cell(0, 10, 'SUSTAIN-U', 0, 1, 'L');
        
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 8, 'Environmental Audit Report', 0, 1, 'L');
        
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 5, 'Official verification of campus environmental resolution.', 0, 1, 'L');
        
        $this->Ln(5);
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Sustain-U Environmental Audit | Page ' . $this->PageNo() . ' | Generated on ' . date('d-m-Y H:i'), 0, 0, 'C');
    }

    function SectionTitle($label) {
        $this->CheckPageBreak(30); // Ensure space for title + a few lines of content
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(255, 255, 255);
        $this->SetFillColor(26, 115, 232);
        $this->Cell(0, 8, '  ' . strtoupper($label), 0, 1, 'L', true);
        $this->Ln(3);
    }

    function CheckPageBreak($h) {
        // If height would exceed the page, add a new one
        if($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
            return true;
        }
        return false;
    }

    function LabelValue($label, $value) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(51, 51, 51);
        $this->Cell(50, 7, $label . ':', 0, 0);
        
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(85, 85, 85);
        $this->Cell(0, 7, $value, 0, 1);
    }

    public function GetPageBreakTrigger() {
        return $this->PageBreakTrigger;
    }
}

// Create PDF
$pdf = new IssueReportPDF();
$pdf->SetTitle('Audit Report #' . $issue['id']);
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25);

// 1. Core Issue Info
$pdf->SectionTitle('Issue Reference & Status');
$pdf->LabelValue('Report ID', '#' . $issue['id']);
$pdf->LabelValue('Issue', ucfirst($issue['category']));
if (!empty($issue['type'])) {
    $pdf->LabelValue('Issue Category', ucfirst($issue['type']));
}
$pdf->LabelValue('Urgency Level', strtoupper(str_replace('_', ' ', $issue['urgency'])));
$pdf->LabelValue('Final Status', 'RESOLVED');
$pdf->LabelValue('Reported At', date('d M Y, h:i A', strtotime($issue['created_at'])));
$pdf->LabelValue('Resolved At', date('d M Y, h:i A', strtotime($issue['resolved_at'])));
$pdf->Ln(5);

// 2. Location Details
$pdf->SectionTitle('Site Location Details');
$pdf->LabelValue('Building', $issue['building'] ?: 'N/A');
$pdf->LabelValue('Floor', $issue['floor'] ?: 'N/A');
$pdf->LabelValue('Room / Specific Area', $issue['room'] ?: ($issue['location'] ?: 'N/A'));
$pdf->Ln(5);

// 3. Issue Description
$pdf->SectionTitle('Issue Description & Observations');
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(51, 51, 51);
$pdf->MultiCell(0, 6, $issue['description'] ?: 'No detailed description provided at time of report.');
$pdf->Ln(5);

// 4. Resolution Analysis
$pdf->SectionTitle('Resolution Outcome');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(40, 167, 69); // Success Green
$pdf->Cell(0, 7, 'Action Taken:', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(51, 51, 51);
$pdf->MultiCell(0, 6, $issue['resolution_notes'] ?: 'The reported issue has been verified and successfully resolved by the campus maintenance team. All standard environmental protocols were followed during the remediation process.');
$pdf->Ln(5);

// 5. Visual Evidence
$find_image = function($p) {
    if (empty($p)) return null;
    $root = __DIR__ . '/../';
    $p = ltrim($p, '/');
    $p = preg_replace('/^Sustain-U\//', '', $p); // Clean prefix if present
    
    // Possibility 1: Full path from root (e.g. uploads/resolutions/xxx.jpg)
    if (file_exists($root . $p)) return $root . $p;
    // Possibility 2: Just filename in uploads (e.g. xxx.jpg)
    if (file_exists($root . 'uploads/' . $p)) return $root . 'uploads/' . $p;
    
    return null;
};

$img_orig = $find_image($issue['image_path'] ?? $issue['image_before'] ?? '');
$img_res = $find_image($issue['resolved_image_path'] ?? $issue['image_after'] ?? '');

if ($img_orig || $img_res) {
    // Check if images fit (roughly 100mm needed for title + images)
    // If it doesn't fit, jump to next page BEFORE drawing title
    if ($pdf->GetY() + 100 > $pdf->GetPageBreakTrigger()) {
        $pdf->AddPage();
    }
    
    $pdf->SectionTitle('Visual Audit Evidence');
    $current_y = $pdf->GetY();
    
    // Capture Y for both potential images to move Y properly afterwards
    $end_y = $current_y + 10;

    // Original (Left side)
    if ($img_orig) {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(102, 102, 102);
        $pdf->Text(35, $current_y + 5, 'BEFORE (SUBMISSION)');
        try {
            // Validate image before attempting to embed (prevents FPDF crashes on bad formats)
            $img_info = @getimagesize($img_orig);
            if ($img_info && in_array($img_info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                $pdf->Image($img_orig, 15, $current_y + 8, 80);
                $end_y = max($end_y, $pdf->GetY() + 85);
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->Text(15, $current_y + 15, '[Image format unsupported/corrupted]');
            }
        } catch (Exception $e) {}
    }
    
    // Resolved (Right side)
    if ($img_res) {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetTextColor(102, 102, 102);
        $pdf->Text(125, $current_y + 5, 'AFTER (RESOLUTION)');
        try {
            $img_info = @getimagesize($img_res);
            if ($img_info && in_array($img_info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                $pdf->Image($img_res, 115, $current_y + 8, 80);
                $end_y = max($end_y, $pdf->GetY() + 85);
            } else {
                $pdf->SetTextColor(200, 0, 0);
                $pdf->Text(115, $current_y + 15, '[Resolution image unsupported]');
            }
        } catch (Exception $e) {}
    }
    
    // Adjust Y position after images
    $pdf->SetY($end_y);
    $pdf->Ln(5);
}

// 6. Reporter & Certification
// KEEP TOGETHER: Ensure the entire reporter section + signatures fit on the current page
// Roughly 60mm needed for title + 4 labels + signatures
if ($pdf->GetY() + 65 > $pdf->GetPageBreakTrigger()) {
    $pdf->AddPage();
}

$pdf->SectionTitle('Reporter & Certification');
$pdf->LabelValue('Reported by', $issue['user_name']);
$pdf->LabelValue('Email ID', $issue['user_email']);
$pdf->LabelValue('Register Number', $issue['register_number'] ?: 'N/A');
$pdf->LabelValue('Section/Department', ($issue['department'] ?? 'N/A') . ' (' . ($issue['section'] ?: 'N/A') . ')');

$pdf->Ln(12);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 7, '__________________________', 0, 0, 'C');
$pdf->Cell(95, 7, '__________________________', 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(95, 5, 'Campus Department Signature', 0, 0, 'C');
$pdf->Cell(95, 5, 'Audit Verification Seal', 0, 1, 'C');

// Output PDF
// Output PDF
$filename = 'Audit_Report_' . $issue['id'] . '_' . date('Ymd') . '.pdf';

// Robust Mobile/Ngrok Delivery: Capture buffer and send explicit headers
$pdf_data = $pdf->Output('S'); // Output as string
$pdf_size = strlen($pdf_data);

ob_end_clean(); // Clean any accidental whitespace

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $pdf_size);
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdf_data;
exit;
