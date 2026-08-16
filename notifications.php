<?php
$pageTitle = 'Notifications — CTU Lost & Found';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
requireLogin();

// Check if notifications table exists
$notifTableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
$notificationsAvailable = $notifTableCheck && $notifTableCheck->num_rows > 0;

if ($notificationsAvailable) {
    // Get notifications for current user
    $stmt = $conn->prepare("
        SELECT n.*, 
               p.title as post_title,
               p.type as post_type,
               p.id as post_id
        FROM notifications n
        LEFT JOIN claims c ON n.related_id = c.id AND n.type = 'message'
        LEFT JOIN posts p ON c.post_id = p.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Mark all as read
    $markReadStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $markReadStmt->bind_param('i', $_SESSION['user_id']);
    $markReadStmt->execute();
} else {
    $notifications = [];
}
?>

<div class="container-xl py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="section-heading">Notifications</h1>
            <p class="text-muted small">
                <?php if ($notificationsAvailable): ?>
                    <?= count($notifications) ?> notification<?= count($notifications) !== 1 ? 's' : '' ?>
                <?php else: ?>
                    Notifications not available
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= BASE_URL ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-house me-1"></i>Back to Feed
        </a>
    </div>

    <?php if (!$notificationsAvailable): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Notifications System Not Available</strong>
        <p class="mb-0 mt-2">The notifications feature requires the database to be updated. Run the updated <code>database.sql</code> file to enable notifications.</p>
    </div>
    <?php elseif (empty($notifications)): ?>
    <div class="empty-state-modern">
        <div class="es-icon"><i class="bi bi-bell"></i></div>
        <h4>No notifications</h4>
        <p>You're all caught up! New notifications will appear here.</p>
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($notifications as $notif): ?>
        <div class="col-12">
            <div class="notification-item <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                <div class="d-flex gap-3">
                    <div class="notif-icon">
                        <?php if ($notif['type'] === 'message'): ?>
                            <i class="bi bi-chat-dots-fill text-primary"></i>
                        <?php elseif ($notif['type'] === 'id_match'): ?>
                            <i class="bi bi-person-badge-fill text-success"></i>
                        <?php elseif ($notif['type'] === 'new_match'): ?>
                            <i class="bi bi-lightning-charge-fill text-warning"></i>
                        <?php else: ?>
                            <i class="bi bi-info-circle-fill text-info"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="notif-message"><?= e($notif['message']) ?></div>
                        <div class="notif-time">
                            <i class="bi bi-clock me-1"></i><?= timeAgo($notif['created_at']) ?>
                        </div>
                    </div>
                    <?php if ($notif['type'] === 'message' && !empty($notif['post_id'])): ?>
                    <a href="<?= BASE_URL ?>item.php?id=<?= $notif['post_id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
