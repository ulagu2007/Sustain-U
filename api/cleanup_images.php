<?php
/**
 * Sustain-U - DB Image Path Cleanup
 * Run this to fix 404 console spam for missing user images
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/db.php';

$results = $conn->query("SELECT id, image_path, resolved_image_path FROM issues");
$fixedCount = 0;

if ($results) {
    while ($row = $results->fetch_assoc()) {
        $id = $row['id'];
        $imagePath = $row['image_path'];
        $resolvedImagePath = $row['resolved_image_path'];
        
        $updates = [];
        
        // Check main image
        if ($imagePath && !file_exists(__DIR__ . '/../' . $imagePath)) {
            $updates[] = "image_path = 'assets/img/placeholder.png'";
            echo "[ID: $id] Main image missing: $imagePath (setting to placeholder)\n";
        }
        
        // Check resolved image
        if ($resolvedImagePath && !file_exists(__DIR__ . '/../' . $resolvedImagePath)) {
            $updates[] = "resolved_image_path = NULL"; // Or use another placeholder
            echo "[ID: $id] Resolved image missing: $resolvedImagePath (setting to NULL)\n";
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE issues SET " . implode(", ", $updates) . " WHERE id = $id";
            if ($conn->query($sql)) {
                $fixedCount++;
            }
        }
    }
}

echo "\n--- Done! Fixed $fixedCount records ---\n";
?>
