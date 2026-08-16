<?php
$pageTitle = 'Claims — CTU Lost & Found';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
requireLogin();

// Check if messaging is available
$msgTableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
$messagingAvailable = $msgTableCheck && $msgTableCheck->num_rows > 0;

// claims (I need to approve/reject)
$mine = $conn->prepare("
    SELECT c.*, p.title AS post_title, p.id AS post_id, u.name AS claimant_name
    FROM   claims c
    JOIN   posts  p ON p.id = c.post_id
    JOIN   users  u ON u.id = c.claimant_id
    WHERE  p.user_id = ?
    ORDER  BY c.created_at DESC
");
$mine->bind_param('i', $_SESSION['user_id']);
$mine->execute();
$receivedClaims = $mine->get_result()->fetch_all(MYSQLI_ASSOC);

// Claims I submitted
$sent = $conn->prepare("
    SELECT c.*, p.title AS post_title, p.id AS post_id, u.name AS poster_name
    FROM   claims c
    JOIN   posts  p ON p.id = c.post_id
    JOIN   users  u ON u.id = p.user_id
    WHERE  c.claimant_id = ?
    ORDER  BY c.created_at DESC
");
$sent->bind_param('i', $_SESSION['user_id']);
$sent->execute();
$sentClaims = $sent->get_result()->fetch_all(MYSQLI_ASSOC);

// Get unread message counts for claims
$claimMessageCounts = [];
if ($messagingAvailable) {
    foreach (array_merge($receivedClaims, $sentClaims) as $claim) {
        $msgCountStmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM messages 
            WHERE claim_id = ? AND is_read = 0 AND receiver_id = ?
        ");
        $msgCountStmt->bind_param('ii', $claim['id'], $_SESSION['user_id']);
        $msgCountStmt->execute();
        $countResult = $msgCountStmt->get_result()->fetch_assoc();
        $claimMessageCounts[$claim['id']] = $countResult['count'];
    }
}
?>

<div class="container-xl py-4">
    <h1 class="section-heading mb-1">Claims</h1>
    <p class="text-muted small mb-4">Manage incoming claim requests and track your own claims</p>

    <div class="row g-4">
        <!-- Received Claims -->
        <div class="col-lg-6">
            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-inbox-fill text-primary"></i>
                Received Claims
                <?php $pending = array_filter($receivedClaims, fn($c)=>$c['status']==='pending'); ?>
                <?php if ($pending): ?>
                    <span class="badge bg-warning text-dark rounded-pill"><?= count($pending) ?> pending</span>
                <?php endif; ?>
            </h6>
            <?php if (empty($receivedClaims)): ?>
                <div class="empty-state-modern" style="padding:2rem">
                    <div class="es-icon"><i class="bi bi-inbox"></i></div>
                    <p>No claims received yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($receivedClaims as $c): ?>
                <div class="claim-item">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <strong class="small"><?= e($c['claimant_name']) ?></strong>
                            <span class="text-muted ms-1 small">on</span>
                            <a href="item.php?id=<?= $c['post_id'] ?>" class="small fw-semibold">
                                <?= e($c['post_title']) ?>
                            </a>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="claim-status-pill <?= e($c['status']) ?>"><?= ucfirst($c['status']) ?></span>
                            <?php if ($messagingAvailable && ($c['status'] === 'approved' || $c['status'] === 'pending')): ?>
                                <?php if (isset($claimMessageCounts[$c['id']]) && $claimMessageCounts[$c['id']] > 0): ?>
                                <span class="badge bg-primary rounded-pill" style="font-size:.65rem">
                                    <i class="bi bi-chat-dots me-1"></i><?= $claimMessageCounts[$c['id']] ?> new
                                </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-muted small mb-2"><?= e($c['description']) ?></p>
                    <div class="text-muted" style="font-size:.7rem"><?= timeAgo($c['created_at']) ?></div>
                    <?php if ($c['status'] === 'pending'): ?>
                    <div class="d-flex gap-2 mt-2">
                        <form action="actions/claim_action.php" method="post" class="d-inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="bi bi-check me-1"></i>Approve
                            </button>
                        </form>
                        <form action="actions/claim_action.php" method="post" class="d-inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x me-1"></i>Reject
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Sent Claims -->
        <div class="col-lg-6">
            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-send-fill text-secondary"></i>
                My Submitted Claims
            </h6>
            <?php if (empty($sentClaims)): ?>
                <div class="empty-state-modern" style="padding:2rem">
                    <div class="es-icon"><i class="bi bi-send"></i></div>
                    <p>You haven't submitted any claims yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($sentClaims as $c): ?>
                <div class="claim-item">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <a href="item.php?id=<?= $c['post_id'] ?>" class="small fw-semibold">
                            <?= e($c['post_title']) ?>
                        </a>
                        <div class="d-flex align-items-center gap-2">
                            <span class="claim-status-pill <?= e($c['status']) ?>"><?= ucfirst($c['status']) ?></span>
                            <?php if ($messagingAvailable && ($c['status'] === 'approved' || $c['status'] === 'pending')): ?>
                                <?php if (isset($claimMessageCounts[$c['id']]) && $claimMessageCounts[$c['id']] > 0): ?>
                                <span class="badge bg-primary rounded-pill" style="font-size:.65rem">
                                    <i class="bi bi-chat-dots me-1"></i><?= $claimMessageCounts[$c['id']] ?> new
                                </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="text-muted small mb-1"><?= e($c['description']) ?></p>
                    <div class="text-muted" style="font-size:.7rem">
                        by <?= e($c['poster_name']) ?> · <?= timeAgo($c['created_at']) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
