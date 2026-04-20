<?php
/**
 * ============================================
 * REGISTRATION API ENDPOINT
 * ============================================
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

// ============================================
// COLLECT INPUT
// ============================================

// Handle both JSON and form data
$name = '';
$email = '';
$password = '';
$confirm_password = '';
$department = '';
$phone = '';

if (!empty($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($input['full_name'] ?? '');
    $email = trim($input['email'] ?? '');
    // Sustain-U: Trim passwords to match frontend validation
    $password = trim($input['password'] ?? '');
    $confirm_password = trim($input['confirm_password'] ?? '');
    $department = trim($input['department'] ?? '');
    $phone = trim($input['phone'] ?? '');
}
else {
    $name = trim($_POST['full_name'] ?? trim($_POST['name'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
}

// ============================================
// VALIDATION
// ============================================

if (empty($name) || empty($email) || empty($password)) {
    $response['message'] = 'All fields are required';
    echo json_encode($response);
    exit;
}

if (strlen($name) < 3) {
    $response['message'] = 'Name must be at least 3 characters';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format';
    echo json_encode($response);
    exit;
}

if (!isValidStudentEmail($email)) {
    $response['message'] = 'Only @' . APP_DOMAIN . ' emails allowed';
    echo json_encode($response);
    exit;
}

if ($password !== $confirm_password) {
    $response['message'] = 'Passwords do not match';
    echo json_encode($response);
    exit;
}

if (strlen($password) < 6) {
    $response['message'] = 'Password must be at least 6 characters';
    echo json_encode($response);
    exit;
}

// Phone validation: exactly 10 digits, numbers only (optional field)
if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
    $response['message'] = 'Phone number must be exactly 10 digits (numbers only).';
    echo json_encode($response);
    exit;
}

// ============================================
// CHECK DUPLICATE EMAIL
// ============================================

$check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$check_stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$check_stmt->bind_param("s", $email);
if (!$check_stmt->execute()) {
    http_response_code(500);
    logError('Execute failed: ' . $check_stmt->error);
    $check_stmt->close();
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$check_stmt->store_result();
if ($check_stmt->num_rows > 0) {
    $response['message'] = 'Email already registered';
    $check_stmt->close();
    echo json_encode($response);
    exit;
}
$check_stmt->close();

// ============================================
// INSERT NEW USER
// ============================================

$hashed_password = password_hash($password, PASSWORD_BCRYPT);
$role = 'student';

$insert_stmt = $conn->prepare("INSERT INTO users (name, email, password, role, points, department, section, phone) VALUES (?, ?, ?, ?, 0, ?, ?, ?)");
if (!$insert_stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    $response['success'] = false;
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$section = ''; // Default empty section
$insert_stmt->bind_param("sssssss", $name, $email, $hashed_password, $role, $department, $section, $phone);

if ($insert_stmt->execute()) {
    http_response_code(201);
    $response['success'] = true;
    $response['message'] = 'Registration successful. Redirecting to login...';
}
else {
    http_response_code(500);
    logError('Insert failed: ' . $insert_stmt->error);
    $response['message'] = 'Registration failed. Please try again.';
}

$insert_stmt->close();
echo json_encode($response);
