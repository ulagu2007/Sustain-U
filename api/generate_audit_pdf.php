<?php
/**
 * DYNAMIC AUDIT REPORT GENERATOR (PDF)
 * Generates a professional branded PDF report with data and summary.
 * PRODUCTION-SAFE VERSION
 */
ob_start(); // Prevent any accidental whitespace from corrupting the PDF

$config_path = __DIR__ . '/../config.php';
$db_path = __DIR__ . '/db.php';
$fpdf_path = __DIR__ . '/../libs/fpdf/fpdf.php';

if (!file_exists($config_path) || !file_exists($db_path) || !file_exists($fpdf_path)) {
    if (ob_get_length()) ob_end_clean();
    header('HTTP/1.1 500 Internal Server Error');
    die("Error: Missing required dependency files.");
}

require_once $config_path;
require_once $db_path;
require_once $fpdf_path;

if (!function_exists('isAdmin') || !isAdmin()) {
    if (ob_get_length()) ob_end_clean();
    header('HTTP/1.1 403 Forbidden');
    die("Unauthorized access.");
}

try {
    // 1. Get Filters
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $year_filter = isset($_GET['year']) ? $_GET['year'] : 'all';
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

    // 2. Fetch Data (Same logic as Excel)
    $query = "SELECT i.*, u.name as user_name, u.email as user_email, u.phone as user_phone, 
              u.register_number, u.section, u.department, u.degree
              FROM issues i 
              JOIN users u ON i.user_id = u.id 
              WHERE 1=1";

    $params = [];
    $types = "";

    if ($status_filter !== 'all') {
        $query .= " AND i.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }

    if ($year_filter !== 'all') {
        $query .= " AND YEAR(i.created_at) = ?";
        $params[] = (int)$year_filter;
        $types .= "i";
    }

    if (!empty($from_date)) {
        $query .= " AND DATE(i.created_at) >= ?";
        $params[] = $from_date;
        $types .= "s";
    }

    if (!empty($to_date)) {
        $query .= " AND DATE(i.created_at) <= ?";
        $params[] = $to_date;
        $types .= "s";
    }

    $query .= " ORDER BY i.created_at DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }
    
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Database execution failed: " . $stmt->error);
    }

    $meta = $stmt->result_metadata();
    if (!$meta) {
        throw new Exception("Database error: Could not fetch result metadata.");
    }
    
    $fields = [];
    $row_data = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = &$row_data[$field->name];
    }
    call_user_func_array([$stmt, 'bind_result'], $fields);

    // 3. Mapping and Tracking Setup
    $status_counts = ['SUBMITTED' => 0, 'IN_PROGRESS' => 0, 'RESOLVED' => 0];
    $cat_counts = ['Air' => 0, 'Water' => 0, 'Waste' => 0, 'Others' => 0];

    $all_data = [];
    while ($stmt->fetch()) {
        $row = [];
        foreach ($row_data as $key => $val) {
            $row[$key] = $val;
        }

        $curr_status = strtoupper($row['status'] ?? 'UNKNOWN');
        if (isset($status_counts[$curr_status])) $status_counts[$curr_status]++;

        $main_raw = strtolower($row['category'] ?? 'others');
        $cat_label = 'Others';
        if ($main_raw === 'air') $cat_label = 'Air';
        elseif ($main_raw === 'water') $cat_label = 'Water';
        elseif ($main_raw === 'waste') $cat_label = 'Waste';
        if (isset($cat_counts[$cat_label])) $cat_counts[$cat_label]++;

        $issue_label = $row['description'] ?? 'No description';
        if (!empty($row['custom_description'])) {
            $issue_label .= " (" . $row['custom_description'] . ")";
        }

        $bldg = $row['building'] ?? '';
        $flr = $row['floor'] ?? '';
        $rm = $row['room'] ?? '';
        $loc = trim(implode(', ', array_filter([$bldg, $flr, $rm])));

        $all_data[] = [
            'issue' => $cat_label,
            'cat' => $issue_label,
            'status' => $curr_status,
            'loc' => empty($loc) ? 'N/A' : $loc,
            'user' => $row['user_name'] ?? 'N/A',
            'reg' => $row['register_number'] ?? 'N/A',
            'dept_sec' => trim(($row['section'] ?? '') . ' / ' . ($row['department'] ?? ''), ' /'),
            'rep_date' => empty($row['created_at']) ? 'N/A' : date('d-m-Y', strtotime($row['created_at'])),
            'res_date' => empty($row['resolved_at']) ? 'N/A' : date('d-m-Y', strtotime($row['resolved_at']))
        ];
    }
    $stmt->close();

// 4. PDF Generation with Robust Table Support
class AuditPDF extends FPDF {
    protected $widths;
    protected $aligns;
    protected $title_text;
    protected $status_filt;

    function __construct($title, $status_label) {
        parent::__construct('L', 'mm', 'A4');
        $this->title_text = $title;
        $this->status_filt = $status_label;
    }

    function SetWidths($w) {
        $this->widths = $w;
    }

    function SetAligns($a) {
        $this->aligns = $a;
    }

    function Header() {
        // Logo
        $logo = __DIR__ . '/../assets/bg-srmlogo.jpg.png';
        if (file_exists($logo)) {
            $this->Image($logo, 250, 10, 35);
        }

        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(26, 115, 232);
        $this->Cell(0, 10, $this->title_text, 0, 1, 'L');
        
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 6, 'Filter: ' . strtoupper($this->status_filt) . ' Reports', 0, 1, 'L');
        $this->Cell(0, 6, 'Date Generated: ' . date('d-m-Y H:i'), 0, 1, 'L');
        $this->Ln(8);

        // Header Table - Repeated on every page
        if ($this->widths) {
            $this->SetFont('Arial', 'B', 9);
            $this->SetFillColor(26, 115, 232);
            $this->SetTextColor(255);
            $this->SetLineWidth(0.3);

            $headers = [
                'Issue', 'Issue Category', 'Status', 'Location details', 
                'Reported by', 'Register Number', 'Section/Department', 
                'Reported Date', 'Resolved Date'
            ];

            $i = 0;
            foreach ($this->widths as $w) {
                $this->Cell($w, 8, $headers[$i], 1, 0, 'C', true);
                $i++;
            }
            $this->Ln();
        }
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150);
        $this->Cell(0, 10, 'Sustain-U Environmental Management System | Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    // Custom Row function to handle multi-line and uniform height
    function Row($data, $fill = false) {
        $nb = 0;
        for ($i = 0; $i < count($data); $i++) {
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        }
        $h = 5 * $nb;
        
        // Issue a page break if needed
        $this->CheckPageBreak($h);
        
        // Draw the cells of the row
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            
            $x = $this->GetX();
            $y = $this->GetY();
            
            if ($fill) {
                $this->SetFillColor(245, 248, 255);
                $this->Rect($x, $y, $w, $h, 'F');
            }
            $this->Rect($x, $y, $w, $h);
            
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            $this->SetXY($x + $w, $y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h) {
        if ($this->GetY() + $h > $this->PageBreakTrigger) {
            $this->AddPage($this->CurOrientation);
        }
    }

    function NbLines($w, $txt) {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n") $nb--;
        $sep = -1;
        $i = 0; $j = 0; $l = 0; $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++; $sep = -1; $j = $i; $l = 0; $nl++;
                continue;
            }
            if ($c == ' ') $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) $i++;
                } else $i = $sep + 1;
                $sep = -1; $j = $i; $l = 0; $nl++;
            } else $i++;
        }
        return $nl;
    }
}

$pdf = new AuditPDF('Detailed Environmental Audit Report', $status_filter);
$pdf->AliasNbPages();

// Set Column Widths (Full width: 277mm for A4 Landscape with 10mm margins)
// Issue(25), Issue Category(55), Status(20), Loc(45), User(30), Reg(25), DeptSec(35), Rep Date(20), Res Date(22)
$pdf->SetWidths([25, 55, 20, 45, 30, 25, 35, 20, 22]);
$pdf->SetAligns(['C', 'L', 'C', 'L', 'L', 'C', 'L', 'C', 'C']);

$pdf->AddPage();
$pdf->SetTextColor(40, 40, 40);
$pdf->SetFont('Arial', '', 8);

$fill = false;
foreach ($all_data as $row) {
    $pdf->Row([
        $row['issue'],
        $row['cat'],
        $row['status'],
        $row['loc'],
        $row['user'],
        $row['reg'],
        $row['dept_sec'],
        $row['rep_date'],
        $row['res_date']
    ], $fill);
    $fill = !$fill; // Toggle zebra striping
}

// Summary Page - Big and Efficient
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 24); // Much bigger title
$pdf->SetTextColor(26, 115, 232);
$pdf->Cell(0, 15, 'Executive Summary Breakdown', 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 287, $pdf->GetY());
$pdf->Ln(10);

$pdf->SetTextColor(0);
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetFillColor(230, 240, 255);
// Wider columns for summary
$pdf->Cell(120, 12, 'Metric Group', 1, 0, 'L', true);
$pdf->Cell(60, 12, 'Total Count', 1, 1, 'C', true);

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(120, 12, 'OVERALL TOTAL REPORTS', 1, 0, 'L');
$pdf->Cell(60, 12, count($all_data), 1, 1, 'C');
$pdf->Ln(10);

$sections = [
    'STATUS OVERVIEW' => $status_counts,
    'PRIMARY CATEGORIES' => $cat_counts
];

foreach ($sections as $title => $data) {
    if ($pdf->GetY() > 150) $pdf->AddPage();
    
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell(180, 10, $title, 1, 1, 'L', true);
    
    $pdf->SetFont('Arial', '', 12);
    foreach ($data as $label => $val) {
        // Skip "Others" if count is zero as requested
        if ($label === 'Others' && $val == 0) continue;
        
        $pdf->Cell(120, 10, '   ' . $label, 1, 0, 'L');
        $pdf->Cell(60, 10, $val, 1, 1, 'C');
    }
    $pdf->Ln(8);
}

// Output
    $filename = "Environmental_Audit_Report_" . date('Ymd_His') . ".pdf";

    // Robust Mobile/Ngrok Delivery: Capture buffer and send explicit headers
    $pdf_data = $pdf->Output('S'); // Output as string
    $pdf_size = strlen($pdf_data);

    if (ob_get_length()) ob_end_clean(); // Clean any accidental whitespace

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . $pdf_size);
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdf_data;
    exit;

} catch (Exception $e) {
    if (ob_get_length()) ob_end_clean();
    if (function_exists('logError')) {
        logError("Audit Report PDF Error: " . $e->getMessage());
    } else {
        error_log("Audit Report PDF Error: " . $e->getMessage());
    }
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['error' => true, 'message' => 'PDF Export failed: ' . $e->getMessage()]);
    exit;
}
