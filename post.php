<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

session_start();

$db = Database::getInstance()->getConnection();

// Get settings
$stmt = $db->query("SELECT * FROM settings");
$settings = $stmt->fetchAll();
$site_settings = [];
foreach ($settings as $setting) {
    $site_settings[$setting['setting_key']] = $setting['setting_value'];
}

// Get post by slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit();
}

$stmt = $db->prepare("SELECT p.*, u.full_name as author, u.email as author_email, c.name as category, c.slug as category_slug 
                      FROM posts p 
                      LEFT JOIN users u ON p.author_id = u.id 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.slug = ? AND p.status = 'published'");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: index.php');
    exit();
}

// Update view count
$stmt = $db->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
$stmt->execute([$post['id']]);

// Get comments
$stmt = $db->prepare("SELECT * FROM comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at DESC");
$stmt->execute([$post['id']]);
$comments = $stmt->fetchAll();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $author_name = sanitize($_POST['author_name'] ?? '');
    $author_email = sanitize($_POST['author_email'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    
    if (!empty($author_name) && !empty($author_email) && !empty($content)) {
        $stmt = $db->prepare("INSERT INTO comments (post_id, author_name, author_email, content, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$post['id'], $author_name, $author_email, $content]);
        setFlashMessage('Your comment has been submitted and is awaiting moderation', 'success');
        redirect(SITE_URL . '/post.php?slug=' . $slug);
    }
}

$page_title = $post['title'];

include __DIR__ . '/includes/header_public.php';
?>

<div class="container single-post-container">
    <article class="single-post">
        <?php if ($post['featured_image']): ?>
            <div class="post-featured-image">
                <img src="<?php echo SITE_URL; ?>/public/uploads/<?php echo $post['featured_image']; ?>" alt="<?php echo sanitize($post['title']); ?>">
            </div>
        <?php endif; ?>
        
        <header class="post-header">
            <?php if ($post['category']): ?>
                <span class="post-category">
                    <a href="category.php?slug=<?php echo $post['category_slug']; ?>">
                        <?php echo sanitize($post['category']); ?>
                    </a>
                </span>
            <?php endif; ?>
            
            <h1><?php echo sanitize($post['title']); ?></h1>
            
            <div class="post-meta">
                <span class="author">By <?php echo sanitize($post['author']); ?></span>
                <span class="date"><?php echo formatDate($post['published_at']); ?></span>
                <span class="views"><?php echo $post['views']; ?> views</span>
            </div>
        </header>
        
        <div class="post-content-full">
            <?php echo nl2br($post['content']); ?>
        </div>
    </article>
    
    <div class="comments-section">
        <h2>Comments (<?php echo count($comments); ?>)</h2>
        
        <?php 
        $flash = getFlashMessage();
        if ($flash): 
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($site_settings['comments_enabled'] ?? true): ?>
            <div class="comment-form">
                <h3>Leave a Comment</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="text" name="author_name" placeholder="Your Name *" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="author_email" placeholder="Your Email *" required>
                    </div>
                    <div class="form-group">
                        <textarea name="content" rows="5" placeholder="Your Comment *" required></textarea>
                    </div>
                    <button type="submit" name="submit_comment" class="btn btn-primary">Submit Comment</button>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="comments-list">
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <div class="comment-header">
                        <strong><?php echo sanitize($comment['author_name']); ?></strong>
                        <span class="comment-date"><?php echo formatDate($comment['created_at']); ?></span>
                    </div>
                    <div class="comment-content">
                        <?php echo nl2br(sanitize($comment['content'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer_public.php'; ?>
