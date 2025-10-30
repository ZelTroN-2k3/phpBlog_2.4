<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$page_title = 'Comments Management';

$action = $_GET['action'] ?? 'list';
$comment_id = $_GET['id'] ?? null;

// Handle actions
if ($action === 'approve' && $comment_id) {
    $stmt = $db->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
    $stmt->execute([$comment_id]);
    setFlashMessage('Comment approved', 'success');
    redirect(SITE_URL . '/admin/comments.php');
} elseif ($action === 'spam' && $comment_id) {
    $stmt = $db->prepare("UPDATE comments SET status = 'spam' WHERE id = ?");
    $stmt->execute([$comment_id]);
    setFlashMessage('Comment marked as spam', 'success');
    redirect(SITE_URL . '/admin/comments.php');
} elseif ($action === 'delete' && $comment_id) {
    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    setFlashMessage('Comment deleted', 'success');
    redirect(SITE_URL . '/admin/comments.php');
}

// Get all comments
$stmt = $db->query("SELECT c.*, p.title as post_title 
                    FROM comments c 
                    LEFT JOIN posts p ON c.post_id = p.id 
                    ORDER BY c.created_at DESC");
$comments = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Comments</h1>
</div>

<table class="data-table">
    <thead>
        <tr>
            <th>Author</th>
            <th>Comment</th>
            <th>Post</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($comments as $comment): ?>
        <tr>
            <td>
                <strong><?php echo sanitize($comment['author_name']); ?></strong><br>
                <small><?php echo sanitize($comment['author_email']); ?></small>
            </td>
            <td><?php echo truncate(sanitize($comment['content']), 100); ?></td>
            <td><?php echo sanitize($comment['post_title']); ?></td>
            <td><span class="badge badge-<?php echo $comment['status']; ?>"><?php echo $comment['status']; ?></span></td>
            <td><?php echo formatDate($comment['created_at']); ?></td>
            <td class="actions">
                <?php if ($comment['status'] !== 'approved'): ?>
                    <a href="comments.php?action=approve&id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                <?php endif; ?>
                <a href="comments.php?action=spam&id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-warning">Spam</a>
                <a href="comments.php?action=delete&id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__ . '/includes/footer.php'; ?>
