<?php
// Configuration settings for Coffee Management System

// Database configuration
define('DB_PATH', __DIR__ . '/../database/coffee_management.db');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('HASH_ALGO', PASSWORD_DEFAULT);
define('CSRF_TOKEN_LENGTH', 32);

// Application settings
define('APP_NAME', 'Coffee Management System');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'UTC');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Constants for user roles
define('ROLE_ADMIN', 'admin');
define('ROLE_SELLER', 'seller');

// Base URL (adjust as needed)
define('BASE_URL', '/coffee-management-system');

?>
