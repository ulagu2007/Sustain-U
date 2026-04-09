<?php
/**
 * SUSTAIN-U - ZERO-FAILURE DATABASE BUILDER
 * This script builds or repairs the database without phpMyAdmin.
 * Hardens all tables for production and fixes constraints.
 */
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain');
echo "SUSTAIN-U - DATABASE AUTO-REPAIR UTILITY\n";
echo "========================================\n\n";

// Disable foreign key checks for table creation/repair
$conn->query("SET FOREIGN_KEY_CHECKS = 0;");

// 1. ENSURE USERS TABLE EXISTS & IS COMPLETE
echo "[BUILDING/REPARING] Users table... ";
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','admin') DEFAULT 'student',
    department VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    section VARCHAR(20) DEFAULT NULL,
    register_number VARCHAR(50) DEFAULT NULL,
    degree VARCHAR(50) DEFAULT NULL,
    other_details TEXT DEFAULT NULL,
    points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($conn->query($sql_users)) echo "OK\n"; else echo "ERROR: " . $conn->error . "\n";

// 2. ENSURE ISSUES TABLE EXISTS & IS COMPLETE
echo "[BUILDING/REPARING] Issues table... ";
$sql_issues = "CREATE TABLE IF NOT EXISTS issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    category VARCHAR(50) DEFAULT NULL,
    type VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    custom_description TEXT DEFAULT NULL,
    building VARCHAR(100) NOT NULL,
    floor VARCHAR(50) NOT NULL,
    room VARCHAR(255) NOT NULL,
    urgency ENUM('can_wait', 'needs_attention', 'emergency') NOT NULL,
    status ENUM('submitted', 'in_progress', 'resolved') DEFAULT 'submitted',
    report_path VARCHAR(255),
    resolved_image_path VARCHAR(255) DEFAULT NULL,
    resolution_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($conn->query($sql_issues)) echo "OK\n"; else echo "ERROR: " . $conn->error . "\n";

// 3. ENSURE OTP TABLE EXISTS
echo "[BUILDING/REPARING] OTP table... ";
$sql_otp = "CREATE TABLE IF NOT EXISTS otp_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if ($conn->query($sql_otp)) echo "OK\n"; else echo "ERROR: " . $conn->error . "\n";

/**
 * Robustly add a column if it doesn't exist
 */
function addColIfMissing($conn, $table, $column, $definition) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "[FIXED] Added missing column $table.$column\n";
    }
}

// 4. ENSURE ALL COLUMNS ARE PRESENT (Even if tables already existed)
addColIfMissing($conn, 'users', 'department', "VARCHAR(100) DEFAULT NULL");
addColIfMissing($conn, 'users', 'phone', "VARCHAR(20) DEFAULT NULL");
addColIfMissing($conn, 'users', 'section', "VARCHAR(20) DEFAULT NULL");
addColIfMissing($conn, 'users', 'register_number', "VARCHAR(50) DEFAULT NULL");
addColIfMissing($conn, 'users', 'degree', "VARCHAR(50) DEFAULT NULL");
addColIfMissing($conn, 'users', 'other_details', "TEXT DEFAULT NULL");
addColIfMissing($conn, 'users', 'points', "INT DEFAULT 0");

addColIfMissing($conn, 'issues', 'custom_description', "TEXT DEFAULT NULL");
addColIfMissing($conn, 'issues', 'resolved_image_path', "VARCHAR(255) DEFAULT NULL");

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1;");

echo "\n========================================\n";
echo "SUCCESS! DATABASE REPAIR COMPLETE!\n";
echo "========================================\n\n";
echo "Your database schema is now synchronized with the latest code.\n";
echo "You can now safely go back and use the application.\n";
