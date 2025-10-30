<?php
/**
 * Authentication System
 * Secure user authentication and session management
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    die('Direct access not permitted');
}

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        // Check rate limiting
        if (!check_login_attempts($username)) {
            return [
                'success' => false,
                'message' => 'Too many failed login attempts. Please try again later.'
            ];
        }
        
        // Fetch user from database
        $sql = "SELECT * FROM users WHERE username = :username AND status = 'active' LIMIT 1";
        $user = $this->db->fetchOne($sql, ['username' => $username]);
        
        if (!$user) {
            record_failed_login($username);
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        // Verify password
        if (!verify_password($password, $user['password'])) {
            record_failed_login($username);
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
        
        // Clear failed login attempts
        clear_login_attempts($username);
        
        // Update last login
        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        // Validate input
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return [
                'success' => false,
                'message' => 'All fields are required'
            ];
        }
        
        // Validate email
        if (!validate_email($data['email'])) {
            return [
                'success' => false,
                'message' => 'Invalid email address'
            ];
        }
        
        // Check if username exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = :username",
            ['username' => $data['username']]
        );
        
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Username already exists'
            ];
        }
        
        // Check if email exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = :email",
            ['email' => $data['email']]
        );
        
        if ($existing) {
            return [
                'success' => false,
                'message' => 'Email already exists'
            ];
        }
        
        // Hash password
        $hashed_password = hash_password($data['password']);
        
        // Insert user
        $user_data = [
            'username' => sanitize_input($data['username']),
            'email' => sanitize_input($data['email']),
            'password' => $hashed_password,
            'full_name' => sanitize_input($data['full_name'] ?? $data['username']),
            'role' => 'author',
            'status' => 'active'
        ];
        
        $user_id = $this->db->insert('users', $user_data);
        
        if ($user_id) {
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $user_id
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Registration failed'
        ];
    }
    
    /**
     * Check if user has permission
     */
    public function hasPermission($required_role) {
        if (!is_logged_in()) {
            return false;
        }
        
        $roles = ['author', 'editor', 'admin'];
        $user_role_index = array_search($_SESSION['role'], $roles);
        $required_role_index = array_search($required_role, $roles);
        
        return $user_role_index >= $required_role_index;
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!is_logged_in()) {
            return null;
        }
        
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        return $this->db->fetchOne($sql, ['id' => $_SESSION['user_id']]);
    }
}
