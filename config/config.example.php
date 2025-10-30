<?php
/**
 * phpBlog Configuration Template
 * 
 * Copy this file to config.php and update with your settings
 */

// Database Configuration
define('DB_HOST', 'localhost');          // Database host (usually localhost)
define('DB_NAME', 'phpblog');           // Database name
define('DB_USER', 'root');              // Database username
define('DB_PASS', '');                  // Database password

// Site Configuration
define('SITE_NAME', 'phpBlog');                        // Your site name
define('SITE_TAGLINE', 'News, Blog & Magazine CMS');  // Your site tagline
define('SITE_URL', 'http://localhost/phpBlog_2.4');   // Your site URL (no trailing slash)
define('ADMIN_EMAIL', 'admin@phpblog.com');           // Admin email address

// Path Configuration (Usually no need to change these)
define('ROOT_PATH', dirname(__DIR__));
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('THEME_PATH', ROOT_PATH . '/assets/themes');

// Security Settings
define('SESSION_LIFETIME', 3600);              // Session lifetime in seconds (1 hour)
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT); // Password hashing algorithm

// Pagination Settings
define('POSTS_PER_PAGE', 10);     // Posts per page on frontend
define('COMMENTS_PER_PAGE', 20);  // Comments per page in admin

// Debug Mode (Set to false in production)
define('DEBUG_MODE', false);  // Set to true to show detailed error messages
