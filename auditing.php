<?php
/**
 * SUSTAIN-U - Auditing System
 * Admin-only page for generating Excel reports
 */
require_once 'config.php';
require_once 'api/db.php';
requireAdmin();

// Fetch years from database for the dropdown
$years = [];
$yearResult = $conn->query("SELECT DISTINCT YEAR(created_at) as year FROM issues ORDER BY year DESC");
if ($yearResult) {
    while ($row = $yearResult->fetch_assoc()) {
        $years[] = $row['year'];
    }
}
// Ensure current year is always available as an option even if no reports yet
$currentYear = (int)date('Y');
if (!in_array($currentYear, $years)) {
    $years[] = $currentYear;
    rsort($years);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditing System - Sustain-U</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .audit-card {
            background: #fff;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 2rem auto;
        }
        .audit-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .audit-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .filter-item label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #444;
        }
        .filter-item select {
            width: 100%;
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .filter-item select:focus {
            border-color: var(--primary-color);
        }
        .download-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
        }
        .download-btn:hover {
            background: #1557b0;
            transform: translateY(-2px);
        }
        .download-btn:active {
            transform: translateY(0);
        }
        .download-btn i {
            font-size: 1.4rem;
        }
        .info-box {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8fbff;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            font-size: 0.9rem;
            color: #555;
        }
    </style>
</head>
<body class="app">
    <?php include __DIR__ . '/inc/header.php'; ?>

    <div class="container">
        <div class="audit-card">
            <div class="audit-header">
                <h1>Auditing System</h1>
                <p>Generate detailed environmental reports in Excel format</p>
            </div>

            <form id="auditForm" action="api/generate_audit_report.php" method="GET">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label for="status">Select Status</label>
                        <select id="status" name="status">
                            <option value="all">All Status</option>
                            <option value="submitted">Submitted</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="year">Select Year</label>
                        <select id="year" name="year">
                            <option value="all">All Years</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-grid" style="margin-top: -1rem; margin-bottom: 2rem;">
                    <div class="filter-item">
                        <label for="from_date">From Date</label>
                        <input type="date" id="from_date" name="from_date" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #ddd;">
                    </div>
                    <div class="filter-item">
                        <label for="to_date">To Date</label>
                        <input type="date" id="to_date" name="to_date" style="width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #ddd;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <button type="submit" onclick="setFormat('excel')" class="download-btn">
                         Download Excel Report
                    </button>
                    <button type="submit" onclick="setFormat('pdf')" class="download-btn" style="background: #e91e63;">
                         Download PDF Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/inc/footer.php'; ?>
    
    <script>
        function setFormat(format) {
            const form = document.getElementById('auditForm');
            if (format === 'pdf') {
                form.action = 'api/generate_audit_pdf.php';
            } else {
                form.action = 'api/generate_audit_report.php';
            }
        }

        document.getElementById('auditForm').addEventListener('submit', function() {
            const btn = document.activeElement;
            if (!btn || !btn.classList.contains('download-btn')) return;

            const originalText = btn.innerHTML;
            btn.innerHTML = 'Preparing Report...';
            btn.style.opacity = '0.7';
            btn.style.pointerEvents = 'none';
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            }, 3000);
        });
    </script>
</body>
</html>
