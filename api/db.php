<?php
/**
 * ============================================
 * DATABASE CONNECTION
 * ============================================
 */

// Load configuration
require_once __DIR__ . '/../config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    logError('Database Connection Failed', ['error' => $conn->connect_error]);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error'
    ]);
    exit;
}

// Set charset
if (!$conn->set_charset(DB_CHARSET)) {
    http_response_code(500);
    header('Content-Type: application/json');
    logError('Charset Setup Failed', ['error' => $conn->error]);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
    exit;
}

// Enable strict mode for better error handling
$conn->query("SET sql_mode='STRICT_TRANS_TABLES'");

