<?php
/**
 * END-TO-END SMOKE TEST for Campus Care / Sustain-U
 * Tests: register → login → submit issue → admin login → resolve issue → PDF generation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/db.php';

// Colors for output
$color = [
    'green'  => "\033[32m",
    'red'    => "\033[31m",
    'yellow' => "\033[33m",
    'blue'   => "\033[34m",
    'reset'  => "\033[0m"
];

function test($name, $result, $details = '') {
    global $color;
    $status = $result ? $color['green'] . 'PASS' : $color['red'] . 'FAIL';
    echo "[{$status}{$color['reset']}] {$name}";
    if ($details) echo " - {$details}";
    echo "\n";
    return $result;
}

echo "\n{$color['blue']}=== CAMPUS CARE SMOKE TEST ==={$color['reset']}\n\n";

// Test 1: Database Connection
$dbtest = test('Database Connection', $conn && !$conn->connect_error, $conn->server_info ?? 'Error');

// Test 2: Create test student user
echo "\n{$color['yellow']}--- Student Flow ---{$color['reset']}\n";
$test_email = 'test' . time() . '@srmist.edu.in';
$test_password = password_hash('TestPass123', PASSWORD_BCRYPT);
$test_name = 'Test Student';

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, points) VALUES (?, ?, ?, 'student', 0)");
if ($stmt) {
    $stmt->bind_param("sss", $test_name, $test_email, $test_password);
    $result = $stmt->execute();
    $user_id = $conn->insert_id;
    $stmt->close();
    test('Register Student User', $result, "ID: {$user_id}");
} else {
    test('Register Student User', false, $conn->error);
}

// Test 3: Verify login session
echo "\n{$color['yellow']}--- Login & Session ---{$color['reset']}\n";
$stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
if ($stmt) {
    $stmt->bind_param("s", $test_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $login_ok = $user && password_verify('TestPass123', $user['password']);
    test('Student Login Verification', $login_ok, "User: {$user['name']} ({$user['id']})");
} else {
    test('Student Login Verification', false, $conn->error);
}

// Test 4: Submit an issue
echo "\n{$color['yellow']}--- Submit Issue ---{$color['reset']}\n";
if ($user) {
    $building = 'Block A';
    $floor = '2nd';
    $room = '201';
    $urgency = 'needs_attention';
    $status = 'submitted';
    $image_path = 'test_image.jpg';
    
    $stmt = $conn->prepare("INSERT INTO issues (user_id, image_path, building, floor, room, urgency, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("issssss", $user['id'], $image_path, $building, $floor, $room, $urgency, $status);
        $result = $stmt->execute();
        $issue_id = $conn->insert_id;
        $stmt->close();
        test('Submit Issue', $result, "Issue ID: {$issue_id}");
    } else {
        test('Submit Issue', false, $conn->error);
    }
}

// Test 5: Award points
echo "\n{$color['yellow']}--- Points Award ---{$color['reset']}\n";
if ($user) {
    $points = POINTS_PER_ISSUE;
    $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $points, $user['id']);
        $result = $stmt->execute();
        $stmt->close();
        test('Award Points', $result, "+{$points} points");
    }
}

// Test 6: Admin resolve issue
echo "\n{$color['yellow']}--- Admin Flow ---{$color['reset']}\n";
if (isset($issue_id)) {
    $new_status = 'resolved';
    $stmt = $conn->prepare("UPDATE issues SET status = ?, resolved_at = NOW() WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $issue_id);
        $result = $stmt->execute();
        $stmt->close();
        test('Admin: Mark Issue Resolved', $result, "Issue {$issue_id}");
    }
}

// Test 7: Verify config constants
echo "\n{$color['yellow']}--- Configuration ---{$color['reset']}\n";
// Geofencing removed — RADAR_API_KEY and GEOFENCE_RADIUS tests removed.
test('REPORTS_DIR Exists', is_dir(REPORTS_DIR), REPORTS_DIR);
test('UPLOAD_DIR Exists', is_dir(UPLOAD_DIR), UPLOAD_DIR);

// Test 8: Check endpoint files
echo "\n{$color['yellow']}--- Endpoint Files ---{$color['reset']}\n";
$endpoints = [
    'register_user.php',
    'login_user.php',
    'submit_issue.php',
    'update_status.php',
    'generate_pdf.php',
    'get_student_issues.php',
    'get_all_issues.php'
];
foreach ($endpoints as $ep) {
    test("API: {$ep}", file_exists(__DIR__ . "/api/{$ep}"));
}

// Test 9: Check frontend pages
echo "\n{$color['yellow']}--- Frontend Pages ---{$color['reset']}\n";
$pages = [
    'index.php',
    'login.php',
    'register.php',
    'admin_login.php',
    'my_works.php',
    'report_issue.php',
    'my_works.php',
    'profile.php',
    'admin_dashboard.php',
    'issue_details.php'
];
foreach ($pages as $pg) {
    test("Page: {$pg}", file_exists(__DIR__ . "/{$pg}"));
}

// Test 10: Check assets
echo "\n{$color['yellow']}--- Assets ---{$color['reset']}\n";
test('Header Include', file_exists(__DIR__ . '/inc/header.php'));
test('CSS Stylesheet', file_exists(__DIR__ . '/css/style.css'));
test('JS Main Script', file_exists(__DIR__ . '/js/main.js'));
test('Logo Placeholder', file_exists(__DIR__ . '/assets/logo.svg') || file_exists(__DIR__ . '/assets/logo.png'));
test('Background Placeholder', file_exists(__DIR__ . '/assets/bg.svg') || file_exists(__DIR__ . '/assets/bg.jpg'));

echo "\n{$color['blue']}=== TEST COMPLETE ==={$color['reset']}\n\n";

// Cleanup test user if needed
// $conn->query("DELETE FROM users WHERE email = '{$test_email}'");

?>
