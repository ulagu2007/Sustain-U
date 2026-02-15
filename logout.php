<?php
/**
 * SUSTAIN-U - Logout Handler
 * Destroys session and redirects to home page
 */
require_once 'config.php';

// Destroy session
session_destroy();

// Clear any session cookies
setcookie('PHPSESSID', '', time() - 3600, '/');

// Redirect to home with logout message
header('Location: /Sustain-U/index.php?logout=1');
exit; 
