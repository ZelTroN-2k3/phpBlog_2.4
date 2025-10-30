<?php
/**
 * Admin Authentication Handler
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

/**
 * Login user
 */
function login($username, $password) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("SELECT id, username, email, password, role, full_name FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            return true;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    redirect(SITE_URL . '/admin/login.php');
}

/**
 * Check admin access
 */
function requireAdmin() {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/admin/login.php');
    }
}
