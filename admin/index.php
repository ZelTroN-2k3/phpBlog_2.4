<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/includes/security.php';
require_once BASE_PATH . '/includes/database.php';
require_once BASE_PATH . '/includes/auth.php';

// Set security headers
set_security_headers();

// Start secure session
start_secure_session();

// Require login
require_login();

$auth = new Auth();
$user = $auth->getCurrentUser();
$db = Database::getInstance();

// Get statistics
$total_posts = $db->count('posts');
$published_posts = $db->count('posts', 'status = :status', ['status' => 'published']);
$total_categories = $db->count('categories');
$total_comments = $db->count('comments');

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    if (isset($_GET['csrf_token']) && verify_csrf_token($_GET['csrf_token'])) {
        $auth->logout();
        header('Location: ../login.php');
        exit();
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h2><?php echo SITE_NAME; ?></h2>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="posts.php">Posts</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="comments.php">Comments</a></li>
                <?php if ($user['role'] === 'admin'): ?>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="users.php">Users</a></li>
                <?php endif; ?>
            </ul>
            <div class="sidebar-footer">
                <a href="?action=logout&csrf_token=<?php echo $csrf_token; ?>" class="btn-logout">Logout</a>
            </div>
        </nav>
        
        <main class="admin-content">
            <header class="admin-header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    Welcome, <strong><?php echo sanitize_input($user['full_name']); ?></strong>
                    <span class="user-role">(<?php echo ucfirst($user['role']); ?>)</span>
                </div>
            </header>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Posts</h3>
                    <p class="stat-number"><?php echo $total_posts; ?></p>
                    <a href="posts.php">View Posts</a>
                </div>
                
                <div class="stat-card">
                    <h3>Published</h3>
                    <p class="stat-number"><?php echo $published_posts; ?></p>
                    <a href="posts.php?status=published">View Published</a>
                </div>
                
                <div class="stat-card">
                    <h3>Categories</h3>
                    <p class="stat-number"><?php echo $total_categories; ?></p>
                    <a href="categories.php">Manage Categories</a>
                </div>
                
                <div class="stat-card">
                    <h3>Comments</h3>
                    <p class="stat-number"><?php echo $total_comments; ?></p>
                    <a href="comments.php">Manage Comments</a>
                </div>
            </div>
            
            <div class="dashboard-info">
                <div class="info-box">
                    <h3>Security Status</h3>
                    <ul>
                        <li>✓ CSRF Protection Enabled</li>
                        <li>✓ XSS Prevention Active</li>
                        <li>✓ SQL Injection Protection (PDO)</li>
                        <li>✓ Secure Password Hashing (Bcrypt)</li>
                        <li>✓ Session Security Enabled</li>
                        <li>✓ Rate Limiting Active</li>
                    </ul>
                </div>
                
                <div class="info-box">
                    <h3>Quick Actions</h3>
                    <ul>
                        <li><a href="posts.php?action=new">Create New Post</a></li>
                        <li><a href="categories.php?action=new">Add Category</a></li>
                        <li><a href="../index.php" target="_blank">View Site</a></li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
