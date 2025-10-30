<?php
/**
 * Security Functions
 * Provides essential security features for phpBlog
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

/**
 * Generate CSRF Token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Sanitize Input - Prevent XSS
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Sanitize HTML Content
 */
function sanitize_html($content) {
    // Allow only safe HTML tags
    $allowed_tags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><blockquote><code><pre>';
    return strip_tags($content, $allowed_tags);
}

/**
 * Validate Email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Hash Password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify Password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate Secure Random String
 */
function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Prevent SQL Injection - Use with PDO prepared statements
 */
function escape_sql($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Set Security Headers
 */
function set_security_headers() {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
}

/**
 * Start Secure Session
 */
function start_secure_session() {
    $session_name = 'phpblog_session';
    $secure = false; // Set to true if using HTTPS
    $httponly = true;
    $samesite = 'Strict';
    
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]);
    } else {
        session_set_cookie_params(SESSION_TIMEOUT, '/; samesite=' . $samesite, '', $secure, $httponly);
    }
    
    session_name($session_name);
    session_start();
    session_regenerate_id(true);
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require Login
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Rate Limiting for Login Attempts
 */
function check_login_attempts($username) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $current_time = time();
    
    // Clean old attempts
    foreach ($_SESSION['login_attempts'] as $user => $data) {
        if ($current_time - $data['time'] > LOGIN_TIMEOUT) {
            unset($_SESSION['login_attempts'][$user]);
        }
    }
    
    // Check if user is locked out
    if (isset($_SESSION['login_attempts'][$username])) {
        $attempts = $_SESSION['login_attempts'][$username];
        if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS && 
            ($current_time - $attempts['time']) < LOGIN_TIMEOUT) {
            return false;
        }
    }
    
    return true;
}

/**
 * Record Failed Login Attempt
 */
function record_failed_login($username) {
    if (!isset($_SESSION['login_attempts'][$username])) {
        $_SESSION['login_attempts'][$username] = [
            'count' => 1,
            'time' => time()
        ];
    } else {
        $_SESSION['login_attempts'][$username]['count']++;
        $_SESSION['login_attempts'][$username]['time'] = time();
    }
}

/**
 * Clear Login Attempts
 */
function clear_login_attempts($username) {
    if (isset($_SESSION['login_attempts'][$username])) {
        unset($_SESSION['login_attempts'][$username]);
    }
}

/**
 * Validate File Upload
 */
function validate_file_upload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error occurred'];
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'File too large'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        return ['success' => false, 'message' => 'Invalid file extension'];
    }
    
    return ['success' => true];
}
