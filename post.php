<?php
define('BASE_PATH', __DIR__);
require_once 'config/config.php';
require_once 'includes/security.php';
require_once 'includes/database.php';

// Set security headers
set_security_headers();

$db = Database::getInstance();

// Get post slug from URL
$slug = isset($_GET['slug']) ? sanitize_input($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: index.php');
    exit();
}

// Get post with author and category info
$post = $db->fetchOne(
    "SELECT p.*, u.full_name as author_name, c.name as category_name 
     FROM posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     LEFT JOIN categories c ON p.category_id = c.id 
     WHERE p.slug = :slug AND p.status = 'published' 
     LIMIT 1",
    ['slug' => $slug]
);

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    echo '<!DOCTYPE html>
    <html><head><title>Post Not Found</title></head>
    <body><h1>Post Not Found</h1><p><a href="index.php">Back to Home</a></p></body>
    </html>';
    exit();
}

// Increment view count
$db->query("UPDATE posts SET views = views + 1 WHERE id = :id", ['id' => $post['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize_input($post['title']); ?> - <?php echo SITE_NAME; ?></title>
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
        <article class="content post-single">
            <h1><?php echo sanitize_input($post['title']); ?></h1>
            
            <div class="post-meta">
                <span>By <?php echo sanitize_input($post['author_name']); ?></span>
                <?php if ($post['category_name']): ?>
                    <span>in <?php echo sanitize_input($post['category_name']); ?></span>
                <?php endif; ?>
                <span><?php echo date('F d, Y', strtotime($post['published_at'])); ?></span>
                <span><?php echo $post['views']; ?> views</span>
            </div>
            
            <div class="post-content">
                <?php echo sanitize_html($post['content']); ?>
            </div>
            
            <div class="post-footer">
                <a href="index.php">← Back to Posts</a>
            </div>
        </article>
        
        <aside class="sidebar">
            <div class="widget">
                <h3>About This Post</h3>
                <p><strong>Author:</strong> <?php echo sanitize_input($post['author_name']); ?></p>
                <?php if ($post['category_name']): ?>
                    <p><strong>Category:</strong> <?php echo sanitize_input($post['category_name']); ?></p>
                <?php endif; ?>
                <p><strong>Published:</strong> <?php echo date('M d, Y', strtotime($post['published_at'])); ?></p>
                <p><strong>Views:</strong> <?php echo $post['views']; ?></p>
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
