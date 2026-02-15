<?php
/**
 * Test the login API endpoint
 */
require_once 'config.php';

echo "=== Login API Test ===\n\n";

// Test 1: Admin login
echo "Test 1: Admin Login\n";
$json_data = json_encode([
    'email' => 'vt9575@srmist.edu.in',
    'password' => 'OmSairam@2'
]);

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Mock the input
$input_backup = fopen('php://memory', 'r+');
fwrite($input_backup, $json_data);
rewind($input_backup);

// Set the php input stream
$GLOBALS['_input'] = $json_data;

// Test if config loaded properly
echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? "YES" : "NO") . "\n";
echo "Geofencing: disabled\n";

// Now test if we can connect to DB
require_once 'api/db.php';

if ($conn) {
    echo "Database connection: OK\n";
    
    // Try to query a user
    $stmt = $conn->prepare("SELECT COUNT(*) as user_count FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    echo "User count in database: " . $row['user_count'] . "\n";
    $stmt->close();
} else {
    echo "Database connection: FAILED\n";
}

echo "\n=== Test Complete ===\n";
?>
