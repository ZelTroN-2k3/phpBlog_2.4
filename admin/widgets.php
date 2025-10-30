<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$page_title = 'Widgets Management';

$action = $_GET['action'] ?? 'list';
$widget_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $name = sanitize($_POST['name'] ?? '');
        $type = sanitize($_POST['type'] ?? '');
        $position = sanitize($_POST['position'] ?? 'sidebar');
        $content = $_POST['content'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name) || empty($type)) {
            setFlashMessage('Name and type are required', 'error');
        } else {
            try {
                if ($action === 'create') {
                    $stmt = $db->prepare("INSERT INTO widgets (name, type, position, content, is_active) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $type, $position, $content, $is_active]);
                    setFlashMessage('Widget created successfully', 'success');
                } else {
                    $stmt = $db->prepare("UPDATE widgets SET name = ?, type = ?, position = ?, content = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$name, $type, $position, $content, $is_active, $widget_id]);
                    setFlashMessage('Widget updated successfully', 'success');
                }
                redirect(SITE_URL . '/admin/widgets.php');
            } catch (PDOException $e) {
                setFlashMessage('Error saving widget', 'error');
            }
        }
    } elseif ($action === 'delete') {
        try {
            $stmt = $db->prepare("DELETE FROM widgets WHERE id = ?");
            $stmt->execute([$widget_id]);
            setFlashMessage('Widget deleted successfully', 'success');
            redirect(SITE_URL . '/admin/widgets.php');
        } catch (PDOException $e) {
            setFlashMessage('Error deleting widget', 'error');
        }
    }
}

// Get widget for editing
if ($action === 'edit' && $widget_id) {
    $stmt = $db->prepare("SELECT * FROM widgets WHERE id = ?");
    $stmt->execute([$widget_id]);
    $widget = $stmt->fetch();
    
    if (!$widget) {
        setFlashMessage('Widget not found', 'error');
        redirect(SITE_URL . '/admin/widgets.php');
    }
}

// Get all widgets
if ($action === 'list') {
    $stmt = $db->query("SELECT * FROM widgets ORDER BY position, sort_order");
    $widgets = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="page-header">
        <h1>Widgets</h1>
        <a href="widgets.php?action=create" class="btn btn-primary">Add New Widget</a>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Position</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($widgets as $widget): ?>
            <tr>
                <td><strong><?php echo sanitize($widget['name']); ?></strong></td>
                <td><?php echo sanitize($widget['type']); ?></td>
                <td><?php echo sanitize($widget['position']); ?></td>
                <td><span class="badge badge-<?php echo $widget['is_active'] ? 'success' : 'inactive'; ?>"><?php echo $widget['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                <td class="actions">
                    <a href="widgets.php?action=edit&id=<?php echo $widget['id']; ?>" class="btn btn-sm">Edit</a>
                    <form method="POST" action="widgets.php?action=delete&id=<?php echo $widget['id']; ?>" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="page-header">
        <h1><?php echo $action === 'create' ? 'Create New Widget' : 'Edit Widget'; ?></h1>
        <a href="widgets.php" class="btn">Back to Widgets</a>
    </div>
    
    <form method="POST" action="" class="form">
        <div class="form-row">
            <div class="form-group col-6">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" value="<?php echo sanitize($widget['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group col-3">
                <label for="type">Type *</label>
                <select id="type" name="type" required>
                    <option value="text" <?php echo (isset($widget) && $widget['type'] === 'text') ? 'selected' : ''; ?>>Text</option>
                    <option value="html" <?php echo (isset($widget) && $widget['type'] === 'html') ? 'selected' : ''; ?>>HTML</option>
                    <option value="menu" <?php echo (isset($widget) && $widget['type'] === 'menu') ? 'selected' : ''; ?>>Menu</option>
                    <option value="recent_posts" <?php echo (isset($widget) && $widget['type'] === 'recent_posts') ? 'selected' : ''; ?>>Recent Posts</option>
                </select>
            </div>
            
            <div class="form-group col-3">
                <label for="position">Position</label>
                <select id="position" name="position">
                    <option value="sidebar" <?php echo (isset($widget) && $widget['position'] === 'sidebar') ? 'selected' : ''; ?>>Sidebar</option>
                    <option value="footer" <?php echo (isset($widget) && $widget['position'] === 'footer') ? 'selected' : ''; ?>>Footer</option>
                    <option value="header" <?php echo (isset($widget) && $widget['position'] === 'header') ? 'selected' : ''; ?>>Header</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" rows="8"><?php echo $widget['content'] ?? ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo (isset($widget) && $widget['is_active']) ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $action === 'create' ? 'Create Widget' : 'Update Widget'; ?></button>
            <a href="widgets.php" class="btn">Cancel</a>
        </div>
    </form>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
