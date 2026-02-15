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
// GEOCODING & LOCATION (geofencing removed)
// ============================================
// Geofencing feature removed — campus coordinates and geofence radius are no longer used.
// If you need location utilities in future, reintroduce constants here.

// External Geocoding API (for future use, not required now)
// define('GEOCODING_API_KEY', 'YOUR_API_KEY');

// ============================================
// FILE UPLOAD CONFIGURATION
// ============================================
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
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
define('APP_NAME', 'Campus Care');
define('APP_DOMAIN', 'srmist.edu.in');
// ============================================
// External API Keys (DO NOT EXPOSE TO CLIENT)
// Store keys here and keep this file server-side only
// Radar geofencing support removed (RADAR_API_KEY removed). If re-enabled, add provider key here.

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
    session_start();
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
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? null) === 'admin';
}

/**
 * Check if user is student
 */
function isStudent() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? null) === 'student';
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /Sustain-U/login.php');
        exit;
    }
}

/**
 * Redirect to admin dashboard if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        http_response_code(403);
        header('Location: /Sustain-U/admin_login.php');
        exit;
    }
}

/**
 * Sanitize output to prevent XSS
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email domain
 */
function isValidStudentEmail($email) {
    return preg_match('/@' . preg_quote(APP_DOMAIN, '/') . '$/', $email);
}

/**
 * Generate secure random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Location distance helper removed (geofencing disabled).

/**
 * Log error to file
 */
function logError($message, $context = []) {
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
