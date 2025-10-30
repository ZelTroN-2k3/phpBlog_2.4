<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$page_title = 'Posts Management';

$action = $_GET['action'] ?? 'list';
$post_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $title = sanitize($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $excerpt = sanitize($_POST['excerpt'] ?? '');
        $category_id = $_POST['category_id'] ?? null;
        $status = $_POST['status'] ?? 'draft';
        $slug = generateSlug($title);
        
        if (empty($title) || empty($content)) {
            setFlashMessage('Title and content are required', 'error');
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $db->prepare("INSERT INTO posts (title, slug, content, excerpt, category_id, author_id, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $published_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
                    $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $_SESSION['user_id'], $status, $published_at]);
                    setFlashMessage('Post created successfully', 'success');
                } else {
                    $stmt = $db->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, category_id = ?, status = ?, published_at = ? WHERE id = ?");
                    $published_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
                    $stmt->execute([$title, $slug, $content, $excerpt, $category_id, $status, $published_at, $post_id]);
                    setFlashMessage('Post updated successfully', 'success');
                }
                redirect(SITE_URL . '/admin/posts.php');
            } catch (PDOException $e) {
                setFlashMessage('Error saving post', 'error');
            }
        }
    } elseif ($action === 'delete') {
        try {
            $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            setFlashMessage('Post deleted successfully', 'success');
            redirect(SITE_URL . '/admin/posts.php');
        } catch (PDOException $e) {
            setFlashMessage('Error deleting post', 'error');
        }
    }
}

// Get post for editing
if ($action === 'edit' && $post_id) {
    $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        setFlashMessage('Post not found', 'error');
        redirect(SITE_URL . '/admin/posts.php');
    }
}

// Get all posts for listing
if ($action === 'list') {
    $stmt = $db->query("SELECT p.*, u.full_name as author, c.name as category 
                        FROM posts p 
                        LEFT JOIN users u ON p.author_id = u.id 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        ORDER BY p.created_at DESC");
    $posts = $stmt->fetchAll();
}

// Get categories for dropdown
$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="page-header">
        <h1>Posts</h1>
        <a href="posts.php?action=create" class="btn btn-primary">Add New Post</a>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Status</th>
                <th>Views</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
            <tr>
                <td><strong><?php echo sanitize($post['title']); ?></strong></td>
                <td><?php echo sanitize($post['author']); ?></td>
                <td><?php echo sanitize($post['category'] ?? 'Uncategorized'); ?></td>
                <td><span class="badge badge-<?php echo $post['status']; ?>"><?php echo $post['status']; ?></span></td>
                <td><?php echo $post['views']; ?></td>
                <td><?php echo formatDate($post['created_at']); ?></td>
                <td class="actions">
                    <a href="posts.php?action=edit&id=<?php echo $post['id']; ?>" class="btn btn-sm">Edit</a>
                    <form method="POST" action="posts.php?action=delete&id=<?php echo $post['id']; ?>" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="page-header">
        <h1><?php echo $action === 'create' ? 'Create New Post' : 'Edit Post'; ?></h1>
        <a href="posts.php" class="btn">Back to Posts</a>
    </div>
    
    <form method="POST" action="" class="form">
        <div class="form-row">
            <div class="form-group col-9">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" value="<?php echo sanitize($post['title'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group col-3">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" <?php echo (isset($post) && $post['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo (isset($post) && $post['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                    <option value="archived" <?php echo (isset($post) && $post['status'] === 'archived') ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="excerpt">Excerpt</label>
            <textarea id="excerpt" name="excerpt" rows="3"><?php echo sanitize($post['excerpt'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="content">Content *</label>
            <textarea id="content" name="content" rows="15" required><?php echo $post['content'] ?? ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($post) && $post['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo sanitize($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $action === 'create' ? 'Create Post' : 'Update Post'; ?></button>
            <a href="posts.php" class="btn">Cancel</a>
        </div>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
