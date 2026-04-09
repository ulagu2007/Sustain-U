<?php
/**
 * ============================================
 * SUSTAIN-U CONFIGURATION
 * ============================================
 * 
 * IMPORTANT: DO NOT expose this file publicly.
 * Add to .gitignore and .htaccess
 */

// ============================================
// SECURITY: Prevent direct access
// ============================================
define('SUSTAIN_U_LOADED', true);

// ============================================
// DATABASE CONFIGURATION
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sustain_u');
define('DB_CHARSET', 'utf8mb4');


// ============================================
// FILE UPLOAD CONFIGURATION
// ============================================
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // Increased to 20MB for mobile photos
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('REPORTS_DIR', __DIR__ . '/reports/');
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp'
]);

// ============================================
// APPLICATION SETTINGS
// ============================================
define('POINTS_PER_ISSUE', 5);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('APP_NAME', 'Sustain-U');
define('APP_DOMAIN', 'srmist.edu.in');

// ============================================
// ERROR HANDLING
// ============================================
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// ============================================
// SESSION CONFIGURATION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    // Set a writable session save path (required on IIS/Windows)
    $session_dir = __DIR__ . '/sessions';
    if (!is_dir($session_dir)) {
        mkdir($session_dir, 0755, true);
    }
    session_save_path($session_dir);

    // Explicit cookie params to improve session reliability across pages/browsers
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/', // Consider changing this if app is not at domain root
        'secure' => false, // set to true when serving over HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
    
    // Slide timeout on every activity
    if (isset($_SESSION['user_id'])) {
        $last = $_SESSION['last_activity'] ?? $_SESSION['login_time'] ?? time();
        if ((time() - $last) > SESSION_TIMEOUT) {
            $_SESSION = [];
            session_unset();
            session_destroy();
            session_start();
        } else {
            $_SESSION['last_activity'] = time();
        }
    }
}

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Asia/Kolkata');

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Check if user is logged in
 * NOTE: Auth is now database-backed.
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin()
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? null) === 'admin';
}

/**
 * Check if user is student
 */
function isStudent()
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? null) === 'student';
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php'); // Note: This assumes script is in root. For API scripts, use specific redirects.
        exit;
    }
}

/**
 * Redirect to admin dashboard if not admin
 */
function requireAdmin()
{
    if (!isAdmin()) {
        http_response_code(403);
        header('Location: admin_login.php');
        exit;
    }
}

/**
 * Sanitize output to prevent XSS
 */
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email domain
 */
function isValidStudentEmail($email)
{
    return preg_match('/@' . preg_quote(APP_DOMAIN, '/') . '$/', $email);
}

/**
 * Generate secure random token
 */
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}


/**
 * Log error to file
 */
function logError($message, $context = [])
{
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message";
    if (!empty($context)) {
        $log_message .= " | " . json_encode($context);
    }
    error_log($log_message . "\n", 3, $log_dir . '/error.log');
}

/**
 * Robustly check if a student's profile is complete
 * Centralized source of truth.
 */
function check_profile_completion($conn, $userId) {
    if (!$userId) return false;
    
    $stmt = $conn->prepare("SELECT section, register_number, phone, degree, department FROM users WHERE id = ?");
    if (!$stmt) return false;
    
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user) return false;
    
    // Check all mandatory fields
    $required = ['section', 'register_number', 'phone', 'degree', 'department'];
    foreach ($required as $field) {
        if (empty(trim($user[$field] ?? ''))) {
            return false;
        }
    }
    
    return true;
}

