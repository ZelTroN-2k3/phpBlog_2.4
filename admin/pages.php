<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$page_title = 'Pages Management';

$action = $_GET['action'] ?? 'list';
$page_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $title = sanitize($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $status = $_POST['status'] ?? 'draft';
        $slug = generateSlug($title);
        
        if (empty($title) || empty($content)) {
            setFlashMessage('Title and content are required', 'error');
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $db->prepare("INSERT INTO pages (title, slug, content, author_id, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $slug, $content, $_SESSION['user_id'], $status]);
                    setFlashMessage('Page created successfully', 'success');
                } else {
                    $stmt = $db->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, status = ? WHERE id = ?");
                    $stmt->execute([$title, $slug, $content, $status, $page_id]);
                    setFlashMessage('Page updated successfully', 'success');
                }
                redirect(SITE_URL . '/admin/pages.php');
            } catch (PDOException $e) {
                setFlashMessage('Error saving page', 'error');
            }
        }
    } elseif ($action === 'delete') {
        try {
            $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
            $stmt->execute([$page_id]);
            setFlashMessage('Page deleted successfully', 'success');
            redirect(SITE_URL . '/admin/pages.php');
        } catch (PDOException $e) {
            setFlashMessage('Error deleting page', 'error');
        }
    }
}

// Get page for editing
if ($action === 'edit' && $page_id) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$page_id]);
    $page = $stmt->fetch();
    
    if (!$page) {
        setFlashMessage('Page not found', 'error');
        redirect(SITE_URL . '/admin/pages.php');
    }
}

// Get all pages
if ($action === 'list') {
    $stmt = $db->query("SELECT p.*, u.full_name as author 
                        FROM pages p 
                        LEFT JOIN users u ON p.author_id = u.id 
                        ORDER BY p.created_at DESC");
    $pages = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="page-header">
        <h1>Pages</h1>
        <a href="pages.php?action=create" class="btn btn-primary">Add New Page</a>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $page): ?>
            <tr>
                <td><strong><?php echo sanitize($page['title']); ?></strong></td>
                <td><?php echo sanitize($page['author']); ?></td>
                <td><span class="badge badge-<?php echo $page['status']; ?>"><?php echo $page['status']; ?></span></td>
                <td><?php echo formatDate($page['created_at']); ?></td>
                <td class="actions">
                    <a href="pages.php?action=edit&id=<?php echo $page['id']; ?>" class="btn btn-sm">Edit</a>
                    <form method="POST" action="pages.php?action=delete&id=<?php echo $page['id']; ?>" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="page-header">
        <h1><?php echo $action === 'create' ? 'Create New Page' : 'Edit Page'; ?></h1>
        <a href="pages.php" class="btn">Back to Pages</a>
    </div>
    
    <form method="POST" action="" class="form">
        <div class="form-row">
            <div class="form-group col-9">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" value="<?php echo sanitize($page['title'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group col-3">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" <?php echo (isset($page) && $page['status'] === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo (isset($page) && $page['status'] === 'published') ? 'selected' : ''; ?>>Published</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="content">Content *</label>
            <textarea id="content" name="content" rows="15" required><?php echo $page['content'] ?? ''; ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $action === 'create' ? 'Create Page' : 'Update Page'; ?></button>
            <a href="pages.php" class="btn">Cancel</a>
        </div>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
