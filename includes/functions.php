<?php
// Common functions for Coffee Management System

require_once __DIR__ . '/../config/config.php';

// Security functions
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check if token is valid and not expired (1 hour)
    if (hash_equals($_SESSION['csrf_token'], $token) && 
        (time() - $_SESSION['csrf_token_time']) < 3600) {
        return true;
    }
    
    return false;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = strip_tags($data);
    return $data;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // Basic phone validation (adjust as needed)
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

function validateRequired($fields) {
    foreach ($fields as $field) {
        if (empty($field)) {
            return false;
        }
    }
    return true;
}

// Session management
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function destroySession() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

function logoutUser() {
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $_SESSION['user_phone'],
        $_SESSION['user_location'],
        $_SESSION['user_role'],
        $_SESSION['login_time']
    );
    regenerateSession();
}

function isSessionExpired() {
    if (!isset($_SESSION['login_time'])) {
        return false;
    }
    return (time() - (int)$_SESSION['login_time']) > SESSION_LIFETIME;
}

// Authentication functions
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }
    if (isSessionExpired()) {
        logoutUser();
        setFlashMessage('error', 'Your session has expired. Please login again.');
        return false;
    }
    return true;
}

function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page.');
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        setFlashMessage('error', 'Access denied. Insufficient permissions.');
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

// Flash messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlashMessage($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function displayFlashMessages() {
    $output = '';
    
    if (isset($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $type => $message) {
            $class = $type === 'error' ? 'alert-danger' : 'alert-success';
            $output .= '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
            $output .= htmlspecialchars($message);
            $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            $output .= '</div>';
        }
        unset($_SESSION['flash']);
    }
    
    return $output;
}

// Date formatting
function formatDate($date, $format = 'Y-m-d H:i:s') {
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

// Pagination
function getPagination($total, $page, $limit = 10) {
    $totalPages = ceil($total / $limit);
    $offset = ($page - 1) * $limit;
    
    return [
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

// Redirect function
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// Check if request is AJAX
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

?>
