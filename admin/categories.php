<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$page_title = 'Categories Management';

$action = $_GET['action'] ?? 'list';
$category_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $slug = generateSlug($name);
        
        if (empty($name)) {
            setFlashMessage('Category name is required', 'error');
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $db->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
                    $stmt->execute([$name, $slug, $description]);
                    setFlashMessage('Category created successfully', 'success');
                } else {
                    $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $description, $category_id]);
                    setFlashMessage('Category updated successfully', 'success');
                }
                redirect(SITE_URL . '/admin/categories.php');
            } catch (PDOException $e) {
                setFlashMessage('Error saving category', 'error');
            }
        }
    } elseif ($action === 'delete') {
        try {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            setFlashMessage('Category deleted successfully', 'success');
            redirect(SITE_URL . '/admin/categories.php');
        } catch (PDOException $e) {
            setFlashMessage('Error deleting category', 'error');
        }
    }
}

// Get category for editing
if ($action === 'edit' && $category_id) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        setFlashMessage('Category not found', 'error');
        redirect(SITE_URL . '/admin/categories.php');
    }
}

// Get all categories
if ($action === 'list') {
    $stmt = $db->query("SELECT c.*, COUNT(p.id) as post_count 
                        FROM categories c 
                        LEFT JOIN posts p ON c.id = p.category_id 
                        GROUP BY c.id 
                        ORDER BY c.name");
    $categories = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="page-header">
        <h1>Categories</h1>
        <a href="categories.php?action=create" class="btn btn-primary">Add New Category</a>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Posts</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td><strong><?php echo sanitize($cat['name']); ?></strong></td>
                <td><?php echo sanitize($cat['slug']); ?></td>
                <td><?php echo truncate(sanitize($cat['description'] ?? ''), 100); ?></td>
                <td><?php echo $cat['post_count']; ?></td>
                <td class="actions">
                    <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm">Edit</a>
                    <form method="POST" action="categories.php?action=delete&id=<?php echo $cat['id']; ?>" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="page-header">
        <h1><?php echo $action === 'create' ? 'Create New Category' : 'Edit Category'; ?></h1>
        <a href="categories.php" class="btn">Back to Categories</a>
    </div>
    
    <form method="POST" action="" class="form">
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="<?php echo sanitize($category['name'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"><?php echo sanitize($category['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $action === 'create' ? 'Create Category' : 'Update Category'; ?></button>
            <a href="categories.php" class="btn">Cancel</a>
        </div>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
