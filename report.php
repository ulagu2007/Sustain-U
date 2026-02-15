<?php
// Redirect legacy `report.php` to the new `report_issue.php` (single source of truth for reporting)
header('Location: /Sustain-U/report_issue.php', true, 302);
exit;
?>
