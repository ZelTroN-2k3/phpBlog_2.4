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

// Get category by slug
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit();
}

$stmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = $site_settings['posts_per_page'] ?? POSTS_PER_PAGE;
$offset = ($page - 1) * $posts_per_page;

// Get posts in this category
$stmt = $db->prepare("SELECT p.*, u.full_name as author 
                      FROM posts p 
                      LEFT JOIN users u ON p.author_id = u.id 
                      WHERE p.category_id = ? AND p.status = 'published' 
                      ORDER BY p.published_at DESC 
                      LIMIT ? OFFSET ?");
$stmt->execute([$category['id'], $posts_per_page, $offset]);
$posts = $stmt->fetchAll();

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE category_id = ? AND status = 'published'");
$stmt->execute([$category['id']]);
$total_posts = $stmt->fetch()['count'];
$total_pages = ceil($total_posts / $posts_per_page);

$page_title = $category['name'];

include __DIR__ . '/includes/header_public.php';
?>

<div class="container">
    <div class="category-header">
        <h1><?php echo sanitize($category['name']); ?></h1>
        <?php if ($category['description']): ?>
            <p><?php echo sanitize($category['description']); ?></p>
        <?php endif; ?>
        <p class="post-count"><?php echo $total_posts; ?> posts</p>
    </div>
    
    <div class="posts-grid">
        <?php if (empty($posts)): ?>
            <div class="no-posts">
                <p>No posts in this category yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <div class="post-content">
                        <h2 class="post-title">
                            <a href="post.php?slug=<?php echo $post['slug']; ?>">
                                <?php echo sanitize($post['title']); ?>
                            </a>
                        </h2>
                        
                        <div class="post-meta">
                            <span class="author">By <?php echo sanitize($post['author']); ?></span>
                            <span class="date"><?php echo formatDate($post['published_at']); ?></span>
                        </div>
                        
                        <div class="post-excerpt">
                            <?php echo sanitize($post['excerpt'] ?: truncate(strip_tags($post['content']), 150)); ?>
                        </div>
                        
                        <a href="post.php?slug=<?php echo $post['slug']; ?>" class="read-more">Read More →</a>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>" class="pagination-link">← Previous</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>" class="pagination-link">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer_public.php'; ?>
