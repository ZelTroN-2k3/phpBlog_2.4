<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$page_title = 'Media Gallery';

$action = $_GET['action'] ?? 'list';
$media_id = $_GET['id'] ?? null;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $title = sanitize($_POST['title'] ?? '');
    $alt_text = sanitize($_POST['alt_text'] ?? '');
    $caption = sanitize($_POST['caption'] ?? '');
    
    $upload_result = uploadFile($_FILES['file']);
    
    if ($upload_result['success']) {
        try {
            $stmt = $db->prepare("INSERT INTO media (filename, original_name, file_type, file_size, title, alt_text, caption, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $upload_result['filename'],
                $_FILES['file']['name'],
                $_FILES['file']['type'],
                $_FILES['file']['size'],
                $title,
                $alt_text,
                $caption,
                $_SESSION['user_id']
            ]);
            setFlashMessage('File uploaded successfully', 'success');
            redirect(SITE_URL . '/admin/media.php');
        } catch (PDOException $e) {
            setFlashMessage('Error saving media file', 'error');
        }
    } else {
        setFlashMessage($upload_result['message'], 'error');
    }
}

// Handle delete
if ($action === 'delete' && $media_id) {
    $stmt = $db->prepare("SELECT filename FROM media WHERE id = ?");
    $stmt->execute([$media_id]);
    $media = $stmt->fetch();
    
    if ($media) {
        $file_path = UPLOAD_PATH . '/' . $media['filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $stmt = $db->prepare("DELETE FROM media WHERE id = ?");
        $stmt->execute([$media_id]);
        setFlashMessage('Media file deleted', 'success');
    }
    redirect(SITE_URL . '/admin/media.php');
}

// Get all media
$stmt = $db->query("SELECT m.*, u.full_name as uploaded_by_name 
                    FROM media m 
                    LEFT JOIN users u ON m.uploaded_by = u.id 
                    ORDER BY m.created_at DESC");
$media_files = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Media Gallery</h1>
</div>

<div class="upload-section">
    <h2>Upload New File</h2>
    <form method="POST" action="" enctype="multipart/form-data" class="form">
        <div class="form-row">
            <div class="form-group col-6">
                <label for="file">Select File *</label>
                <input type="file" id="file" name="file" accept="image/*" required>
            </div>
            
            <div class="form-group col-6">
                <label for="title">Title</label>
                <input type="text" id="title" name="title">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-6">
                <label for="alt_text">Alt Text</label>
                <input type="text" id="alt_text" name="alt_text">
            </div>
            
            <div class="form-group col-6">
                <label for="caption">Caption</label>
                <input type="text" id="caption" name="caption">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Upload File</button>
    </form>
</div>

<h2>Media Library</h2>
<div class="media-grid">
    <?php foreach ($media_files as $media): ?>
    <div class="media-item">
        <div class="media-preview">
            <img src="<?php echo SITE_URL; ?>/public/uploads/<?php echo $media['filename']; ?>" alt="<?php echo sanitize($media['alt_text']); ?>">
        </div>
        <div class="media-info">
            <strong><?php echo sanitize($media['title'] ?: $media['original_name']); ?></strong>
            <small>Uploaded by <?php echo sanitize($media['uploaded_by_name']); ?></small>
            <small><?php echo formatDate($media['created_at']); ?></small>
        </div>
        <div class="media-actions">
            <a href="media.php?action=delete&id=<?php echo $media['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
