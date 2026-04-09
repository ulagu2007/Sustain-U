<?php
/**
 * DYNAMIC AUDIT REPORT GENERATOR (EXCEL)
 * Generates a two-sheet .xlsx report based on status and year filters.
 * PRODUCTION-SAFE VERSION
 */

// 4. OUTPUT BUFFER FIX (DOWNLOAD SAFETY) - Start buffering to prevent accidental whitespace
ob_start();

// 6. SESSION / AUTH SAFETY (Handled safely by config.php)

// 2. SAFE FILE PATH HANDLING
$config_path = __DIR__ . '/../config.php';
$db_path = __DIR__ . '/db.php';
$xlsx_path = __DIR__ . '/../libs/SimpleXLSXGen.php';

if (!file_exists($config_path) || !file_exists($db_path) || !file_exists($xlsx_path)) {
    if (ob_get_length()) ob_end_clean();
    header('HTTP/1.1 500 Internal Server Error');
    die("Error: Missing required dependency files.");
}

require_once $config_path;
require_once $db_path;
require_once $xlsx_path;

use Shuchkin\SimpleXLSXGen;

// Fallback session/auth check
if (!function_exists('isAdmin') || !isAdmin()) {
    if (ob_get_length()) ob_end_clean();
    header('HTTP/1.1 403 Forbidden');
    die("Unauthorized access.");
}

// 3. ERROR HANDLING - Wrap the entire export logic
try {
    // 7. PDF + EXCEL GENERATION STABILITY (Extension Validation)
    if (!extension_loaded('zip')) {
        throw new Exception("Missing dependency: 'zip' PHP extension is required for Excel generation.");
    }
    
    // 1. Get Filters
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $year_filter = isset($_GET['year']) ? $_GET['year'] : 'all';
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

    // 2. Build Query
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

    // 8. DATABASE ERROR SAFETY
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

    // 1. MYSQLI COMPATIBILITY FIX - Avoiding get_result() for strict mysqlnd independence
    $meta = $stmt->result_metadata();
    if (!$meta) {
        throw new Exception("Database error: Could not fetch result metadata.");
    }
    
    $fields = [];
    $row_data = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = &$row_data[$field->name];
    }
    // Bind results dynamically
    call_user_func_array([$stmt, 'bind_result'], $fields);

    $data_rows = [];
    $data_rows[] = [
        '<b style="color: #ffffff; background: #1a73e8">Issue</b>',
        '<b style="color: #ffffff; background: #1a73e8">Issue Category</b>',
        '<b style="color: #ffffff; background: #1a73e8">Status</b>',
        '<b style="color: #ffffff; background: #1a73e8">Location details</b>',
        '<b style="color: #ffffff; background: #1a73e8">Reported by</b>',
        '<b style="color: #ffffff; background: #1a73e8">Register Number</b>',
        '<b style="color: #ffffff; background: #1a73e8">Section/Department</b>',
        '<b style="color: #ffffff; background: #1a73e8">Reported Date</b>',
        '<b style="color: #ffffff; background: #1a73e8">Resolved Date</b>'
    ];

    $status_counts = ['SUBMITTED' => 0, 'IN_PROGRESS' => 0, 'RESOLVED' => 0];
    $main_counts = ['Air' => 0, 'Water' => 0, 'Waste' => 0, 'Others' => 0];
    $total_count = 0;

    // Fetch using bind_result & fetch
    while ($stmt->fetch()) {
        // Create a safe copy of the current row (references will change on next fetch)
        $row = [];
        foreach ($row_data as $key => $val) {
            $row[$key] = $val;
        }

        $total_count++;
        
        // 5. NULL SAFE DATA HANDLING
        $curr_status_raw = $row['status'] ?? 'UNKNOWN';
        $curr_status = strtoupper($curr_status_raw);
        if (isset($status_counts[$curr_status])) {
            $status_counts[$curr_status]++;
        }

        $main_raw = strtolower($row['category'] ?? 'others');
        $cat_label = 'Others';
        if ($main_raw === 'air') $cat_label = 'Air';
        elseif ($main_raw === 'water') $cat_label = 'Water';
        elseif ($main_raw === 'waste') $cat_label = 'Waste';
        
        if (isset($main_counts[$cat_label])) {
            $main_counts[$cat_label]++;
        }
        
        $issue_label = $row['description'] ?? 'No description';
        if (!empty($row['custom_description'])) {
            $issue_label .= " (" . $row['custom_description'] . ")";
        }
        
        $bldg = $row['building'] ?? '';
        $flr = $row['floor'] ?? '';
        $rm = $row['room'] ?? '';
        $loc = trim(implode(', ', array_filter([$bldg, $flr, $rm])));
        $loc = empty($loc) ? 'N/A' : $loc;
        
        $created_at = $row['created_at'] ?? null;
        $rep_date = $created_at ? date('d-m-Y', strtotime($created_at)) : 'N/A';
        
        $resolved_at = $row['resolved_at'] ?? null;
        $res_date = $resolved_at ? date('d-m-Y', strtotime($resolved_at)) : 'N/A';
        
        $user_name = $row['user_name'] ?? 'N/A';
        $reg_num = $row['register_number'] ?? 'N/A';
        $degree = $row['degree'] ?? '';
        $other_details = $row['other_details'] ?? '';
        $section = $row['section'] ?? '';
        $dept = $row['department'] ?? '';
        
        $sec_dept = array_filter([$section, $dept]);
        $sec_dept_str = empty($sec_dept) ? 'N/A' : implode(' / ', $sec_dept);
        
        // Match old logic: If degree is Others, you could use other details here (optional, kept from original logic)
        $rep_extra = (strcasecmp($degree, 'Others') === 0) ? $other_details : 'Student';

        $data_rows[] = [
            $cat_label,
            $issue_label,
            $curr_status,
            $loc,
            $user_name,
            $reg_num,
            $sec_dept_str,
            $rep_date,
            $res_date
        ];
    }
    
    $stmt->close();

    // 3. Prepare Detailed Summary Sheet
    $summary_rows = [];
    $summary_rows[] = ['<b style="color: #ffffff; background: #1a73e8">Metric / Category</b>', '<b style="color: #ffffff; background: #1a73e8">Total Count</b>'];
    $summary_rows[] = ['<b>OVERALL TOTAL REPORTS</b>', $total_count];
    $summary_rows[] = ['', ''];

    $summary_rows[] = ['<b style="color: #1a73e8">STATUS BREAKDOWN</b>', ''];
    foreach ($status_counts as $status => $count) {
        $summary_rows[] = [$status, $count];
    }
    $summary_rows[] = ['', ''];

    $summary_rows[] = ['<b style="color: #1a73e8">MAIN CATEGORY BREAKDOWN</b>', ''];
    foreach ($main_counts as $cat => $count) {
        if ($cat === 'Others' && $count == 0) continue;
        $summary_rows[] = [$cat, $count];
    }
    $summary_rows[] = ['', ''];

    // 4. Generate and Download
    $xlsx = SimpleXLSXGen::fromArray($data_rows, ucfirst($status_filter) . ' Reports');
    if (!$xlsx) {
        throw new Exception("Excel generation failed to initialize.");
    }
    $xlsx->addSheet($summary_rows, 'Summary');

    $timestamp = date('Ymd_His');
    $filename = "Environmental_" . ucfirst($status_filter) . "_Report_" . $timestamp . ".xlsx";

    // 4. OUTPUT BUFFER FIX & 9. SERVER-SAFE RESPONSE
    if (ob_get_length()) ob_end_clean();
    
    // Explicit headers for excel (Often SimpleXLSXGen does it but good to be certain of no prior output)
    $xlsx->downloadAs($filename);
    exit;

} catch (Exception $e) {
    // 3. Meaningful error messages and clean HTTP 500 handling
    if (ob_get_length()) ob_end_clean();
    
    // Attempt logging using the config's function if imported properly
    if (function_exists('logError')) {
        logError("Audit Report Export Error: " . $e->getMessage());
    } else {
        error_log("Audit Report Export Error: " . $e->getMessage());
    }
    
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true, 
        'message' => 'Export failed: ' . $e->getMessage()
    ]);
    exit;
}
