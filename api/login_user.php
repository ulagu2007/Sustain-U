<?php
/**
 * ============================================
 * LOGIN API ENDPOINT
 * ============================================
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

header('Content-Type: application/json');
$response = ['success' => false, 'message' => '', 'redirect' => ''];

// ============================================
// COLLECT & VALIDATE INPUT
// ============================================

// Handle both JSON and form data
$email = '';
$password = '';

if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
} else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
}

if (empty($email) || empty($password)) {
    $response['message'] = 'Email and password are required';
    echo json_encode($response);
    exit;
}

// Hardcoded admin credentials per project requirement
if ($email === 'vt9575@srmist.edu.in' && $password === 'OmSairam@2') {
    // Create an admin session (user id 0 reserved for hardcoded admin)
    $_SESSION['user_id'] = 0;
    $_SESSION['user_name'] = 'Campus Admin';
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['login_time'] = time();
    $response['success'] = true;
    $response['message'] = 'Admin login successful';
    $response['redirect'] = '/Sustain-U/admin_dashboard.php';
    echo json_encode($response);
    exit;
}

// ============================================
// FETCH USER FROM DATABASE
// ============================================

$stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
if (!$stmt) {
    http_response_code(500);
    logError('Prepare failed: ' . $conn->error);
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    http_response_code(500);
    logError('Execute failed: ' . $stmt->error);
    $stmt->close();
    $response['message'] = 'Database error';
    echo json_encode($response);
    exit;
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $response['message'] = 'Invalid email or password';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// ============================================
// VERIFY PASSWORD
// ============================================

if (!password_verify($password, $user['password'])) {
    $response['message'] = 'Invalid email or password';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// ============================================
// CREATE SESSION
// ============================================

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];
$_SESSION['login_time'] = time();

// ============================================
// RETURN RESPONSE WITH REDIRECT
// ============================================

$response['success'] = true;
$response['message'] = 'Login successful';
$response['redirect'] = ($user['role'] === 'admin') ? '/Sustain-U/admin_dashboard.php' : '/Sustain-U/my_works.php';

http_response_code(200);
echo json_encode($response);

?>
