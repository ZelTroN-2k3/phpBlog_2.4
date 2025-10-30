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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = $site_settings['posts_per_page'] ?? POSTS_PER_PAGE;
$offset = ($page - 1) * $posts_per_page;

// Get published posts
$stmt = $db->prepare("SELECT p.*, u.full_name as author, c.name as category, c.slug as category_slug 
                      FROM posts p 
                      LEFT JOIN users u ON p.author_id = u.id 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.status = 'published' 
                      ORDER BY p.published_at DESC 
                      LIMIT ? OFFSET ?");
$stmt->execute([$posts_per_page, $offset]);
$posts = $stmt->fetchAll();

// Get total count
$stmt = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 'published'");
$total_posts = $stmt->fetch()['count'];
$total_pages = ceil($total_posts / $posts_per_page);

// Get categories
$stmt = $db->query("SELECT c.*, COUNT(p.id) as post_count 
                    FROM categories c 
                    LEFT JOIN posts p ON c.id = p.category_id AND p.status = 'published'
                    GROUP BY c.id 
                    HAVING post_count > 0 
                    ORDER BY c.name");
$categories = $stmt->fetchAll();

// Get recent posts for sidebar
$stmt = $db->query("SELECT id, title, slug, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 5");
$recent_posts = $stmt->fetchAll();

include __DIR__ . '/includes/header_public.php';
?>

<div class="hero-section">
    <div class="container">
        <h1><?php echo sanitize($site_settings['site_name'] ?? SITE_NAME); ?></h1>
        <p><?php echo sanitize($site_settings['site_tagline'] ?? SITE_TAGLINE); ?></p>
    </div>
</div>

<div class="container main-container">
    <div class="content-area">
        <div class="posts-grid">
            <?php if (empty($posts)): ?>
                <div class="no-posts">
                    <p>No posts available yet. Check back soon!</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <article class="post-card">
                        <?php if ($post['featured_image']): ?>
                            <div class="post-image">
                                <img src="<?php echo SITE_URL; ?>/public/uploads/<?php echo $post['featured_image']; ?>" alt="<?php echo sanitize($post['title']); ?>">
                            </div>
                        <?php endif; ?>
                        
                        <div class="post-content">
                            <?php if ($post['category']): ?>
                                <span class="post-category">
                                    <a href="category.php?slug=<?php echo $post['category_slug']; ?>">
                                        <?php echo sanitize($post['category']); ?>
                                    </a>
                                </span>
                            <?php endif; ?>
                            
                            <h2 class="post-title">
                                <a href="post.php?slug=<?php echo $post['slug']; ?>">
                                    <?php echo sanitize($post['title']); ?>
                                </a>
                            </h2>
                            
                            <div class="post-meta">
                                <span class="author">By <?php echo sanitize($post['author']); ?></span>
                                <span class="date"><?php echo formatDate($post['published_at']); ?></span>
                                <span class="views"><?php echo $post['views']; ?> views</span>
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
                    <a href="?page=<?php echo $page - 1; ?>" class="pagination-link">← Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>" class="pagination-link">Next →</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <aside class="sidebar">
        <div class="widget">
            <h3>Categories</h3>
            <ul class="category-list">
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="category.php?slug=<?php echo $category['slug']; ?>">
                            <?php echo sanitize($category['name']); ?>
                            <span class="count">(<?php echo $category['post_count']; ?>)</span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="widget">
            <h3>Recent Posts</h3>
            <ul class="recent-posts-list">
                <?php foreach ($recent_posts as $recent): ?>
                    <li>
                        <a href="post.php?slug=<?php echo $recent['slug']; ?>">
                            <?php echo sanitize($recent['title']); ?>
                        </a>
                        <small><?php echo formatDate($recent['published_at']); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer_public.php'; ?>
