<?php
define('BASE_PATH', __DIR__);
require_once 'config/config.php';
require_once 'includes/security.php';
require_once 'includes/database.php';

// Set security headers
set_security_headers();

$db = Database::getInstance();

// Get published posts
$posts = $db->fetchAll(
    "SELECT p.*, u.full_name as author_name, c.name as category_name 
     FROM posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.status = 'published' 
     ORDER BY p.published_at DESC 
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Secure Blog CMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1><?php echo SITE_NAME; ?></h1>
            <p>A secure, fast, and user-friendly blog CMS</p>
            <nav>
                <a href="index.php">Home</a>
                <a href="login.php">Login</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <div class="content">
            <h2>Latest Posts</h2>
            
            <?php if ($posts && count($posts) > 0): ?>
                <div class="posts-grid">
                    <?php foreach ($posts as $post): ?>
                        <article class="post-card">
                            <h3><?php echo sanitize_input($post['title']); ?></h3>
                            
                            <div class="post-meta">
                                <span>By <?php echo sanitize_input($post['author_name']); ?></span>
                                <?php if ($post['category_name']): ?>
                                    <span>in <?php echo sanitize_input($post['category_name']); ?></span>
                                <?php endif; ?>
                                <span><?php echo date('M d, Y', strtotime($post['published_at'])); ?></span>
                            </div>
                            
                            <?php if ($post['excerpt']): ?>
                                <p><?php echo sanitize_input($post['excerpt']); ?></p>
                            <?php endif; ?>
                            
                            <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>" class="read-more">Read More →</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-posts">
                    <p>No posts published yet. Please check back later!</p>
                    <p><a href="login.php">Login</a> to create your first post.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <aside class="sidebar">
            <div class="widget">
                <h3>About phpBlog</h3>
                <p>phpBlog is a secure, fast, and easy-to-use CMS built with procedural PHP. It features:</p>
                <ul>
                    <li>Strong security measures</li>
                    <li>Clean, responsive design</li>
                    <li>User-friendly interface</li>
                    <li>Easy customization</li>
                </ul>
            </div>
        </aside>
    </main>
    
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Built with security in mind.</p>
        </div>
    </footer>
</body>
</html>
