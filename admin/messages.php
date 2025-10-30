<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = Database::getInstance()->getConnection();
$page_title = 'Messages';

$action = $_GET['action'] ?? 'list';
$message_id = $_GET['id'] ?? null;

// Handle delete
if ($action === 'delete' && $message_id) {
    $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$message_id]);
    setFlashMessage('Message deleted', 'success');
    redirect(SITE_URL . '/admin/messages.php');
}

// Handle mark as read
if ($action === 'read' && $message_id) {
    $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $stmt->execute([$message_id]);
    redirect(SITE_URL . '/admin/messages.php?action=view&id=' . $message_id);
}

// Get message for viewing
if ($action === 'view' && $message_id) {
    $stmt = $db->prepare("SELECT m.*, u1.full_name as from_name, u2.full_name as to_name 
                          FROM messages m 
                          LEFT JOIN users u1 ON m.from_user_id = u1.id 
                          LEFT JOIN users u2 ON m.to_user_id = u2.id 
                          WHERE m.id = ?");
    $stmt->execute([$message_id]);
    $message = $stmt->fetch();
    
    if (!$message) {
        setFlashMessage('Message not found', 'error');
        redirect(SITE_URL . '/admin/messages.php');
    }
}

// Get all messages
if ($action === 'list') {
    $stmt = $db->prepare("SELECT m.*, u1.full_name as from_name 
                          FROM messages m 
                          LEFT JOIN users u1 ON m.from_user_id = u1.id 
                          WHERE m.to_user_id = ? 
                          ORDER BY m.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $messages = $stmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <div class="page-header">
        <h1>Messages</h1>
    </div>
    
    <table class="data-table">
        <thead>
            <tr>
                <th>From</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr class="<?php echo $msg['is_read'] ? '' : 'unread'; ?>">
                <td><?php echo sanitize($msg['from_name'] ?? 'System'); ?></td>
                <td>
                    <a href="messages.php?action=view&id=<?php echo $msg['id']; ?>">
                        <strong><?php echo sanitize($msg['subject']); ?></strong>
                    </a>
                </td>
                <td><?php echo formatDate($msg['created_at']); ?></td>
                <td>
                    <?php if ($msg['is_read']): ?>
                        <span class="badge badge-success">Read</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Unread</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a href="messages.php?action=view&id=<?php echo $msg['id']; ?>" class="btn btn-sm">View</a>
                    <a href="messages.php?action=delete&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php elseif ($action === 'view'): ?>
    <div class="page-header">
        <h1>Message Details</h1>
        <a href="messages.php" class="btn">Back to Messages</a>
    </div>
    
    <div class="message-view">
        <div class="message-header">
            <h2><?php echo sanitize($message['subject']); ?></h2>
            <p>
                From: <strong><?php echo sanitize($message['from_name'] ?? 'System'); ?></strong> | 
                Date: <?php echo formatDate($message['created_at']); ?>
            </p>
        </div>
        
        <div class="message-content">
            <?php echo nl2br(sanitize($message['content'])); ?>
        </div>
        
        <div class="message-actions">
            <?php if (!$message['is_read']): ?>
                <a href="messages.php?action=read&id=<?php echo $message['id']; ?>" class="btn btn-primary">Mark as Read</a>
            <?php endif; ?>
            <a href="messages.php?action=delete&id=<?php echo $message['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
