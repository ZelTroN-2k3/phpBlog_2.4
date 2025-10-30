<?php
// Security Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'phpblog');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security Settings
define('SITE_KEY', ''); // Will be generated during installation
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes lockout

// Path Configuration
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Site Configuration
define('SITE_NAME', 'phpBlog');
define('SITE_URL', 'http://localhost/phpBlog');
