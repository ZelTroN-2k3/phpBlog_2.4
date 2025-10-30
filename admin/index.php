<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance()->getConnection();

// Get statistics
$stats = [];

try {
    // Total posts
    $stmt = $db->query("SELECT COUNT(*) as count FROM posts");
    $stats['total_posts'] = $stmt->fetch()['count'];
    
    // Published posts
    $stmt = $db->query("SELECT COUNT(*) as count FROM posts WHERE status = 'published'");
    $stats['published_posts'] = $stmt->fetch()['count'];
    
    // Total comments
    $stmt = $db->query("SELECT COUNT(*) as count FROM comments");
    $stats['total_comments'] = $stmt->fetch()['count'];
    
    // Pending comments
    $stmt = $db->query("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'");
    $stats['pending_comments'] = $stmt->fetch()['count'];
    
    // Total pages
    $stmt = $db->query("SELECT COUNT(*) as count FROM pages");
    $stats['total_pages'] = $stmt->fetch()['count'];
    
    // Total media
    $stmt = $db->query("SELECT COUNT(*) as count FROM media");
    $stats['total_media'] = $stmt->fetch()['count'];
    
    // Recent posts
    $stmt = $db->query("SELECT p.*, u.full_name as author, c.name as category 
                        FROM posts p 
                        LEFT JOIN users u ON p.author_id = u.id 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        ORDER BY p.created_at DESC LIMIT 5");
    $recent_posts = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = "Error fetching statistics";
}

include __DIR__ . '/includes/header.php';
?>

<div class="dashboard">
    <h1>Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_posts']; ?></h3>
                <p>Total Posts</p>
                <small><?php echo $stats['published_posts']; ?> published</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_comments']; ?></h3>
                <p>Comments</p>
                <small><?php echo $stats['pending_comments']; ?> pending</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_pages']; ?></h3>
                <p>Pages</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🖼️</div>
            <div class="stat-info">
                <h3><?php echo $stats['total_media']; ?></h3>
                <p>Media Files</p>
            </div>
        </div>
    </div>
    
    <div class="recent-section">
        <h2>Recent Posts</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_posts as $post): ?>
                <tr>
                    <td><a href="posts.php?action=edit&id=<?php echo $post['id']; ?>"><?php echo sanitize($post['title']); ?></a></td>
                    <td><?php echo sanitize($post['author']); ?></td>
                    <td><?php echo sanitize($post['category']); ?></td>
                    <td><span class="badge badge-<?php echo $post['status']; ?>"><?php echo $post['status']; ?></span></td>
                    <td><?php echo formatDate($post['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
