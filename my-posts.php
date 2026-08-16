<?php
$pageTitle = 'My Posts — CTU Lost & Found';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
requireLogin();

require_once __DIR__ . '/includes/smart_matching.php';

$stmt = $conn->prepare("
    SELECT p.*,
           (SELECT COUNT(*) FROM claims c WHERE c.post_id = p.id AND c.status='pending') AS pending_claims
    FROM   posts p
    WHERE  p.user_id = ?
    ORDER  BY p.created_at DESC
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$myPosts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get smart matches for this user
$userMatches = getUserMatches($_SESSION['user_id']);

// Get unread message count
$unreadMessageCount = 0;
$msgTableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
if ($msgTableCheck && $msgTableCheck->num_rows > 0) {
    $msgStmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM messages m
        JOIN claims c ON m.claim_id = c.id
        JOIN posts p ON c.post_id = p.id
        WHERE p.user_id = ? AND m.receiver_id = ? AND m.is_read = 0
    ");
    $msgStmt->bind_param('ii', $_SESSION['user_id'], $_SESSION['user_id']);
    $msgStmt->execute();
    $msgResult = $msgStmt->get_result()->fetch_assoc();
    $unreadMessageCount = $msgResult['count'];
}

$catIcons = [
    'Electronics'=>'bi-phone','Bags & Accessories'=>'bi-bag','Books & Documents'=>'bi-book',
    'Clothing'=>'bi-gender-ambiguous','Keys & Cards'=>'bi-key','Jewelry'=>'bi-gem',
    'Sports Equipment'=>'bi-dribbble','Others'=>'bi-box',
];
?>
<div class="container-xl py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="section-heading">My Posts</h1>
            <p class="text-muted small"><?= count($myPosts) ?> post<?= count($myPosts)!==1?'s':'' ?> total</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($unreadMessageCount > 0): ?>
            <a href="<?= BASE_URL ?>notifications.php" class="btn btn-outline-primary">
                <i class="bi bi-chat-dots me-1"></i>
                <?= $unreadMessageCount ?> New Message<?= $unreadMessageCount > 1 ? 's' : '' ?>
            </a>
            <?php endif; ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#postModal">
                <i class="bi bi-plus-lg me-1"></i>New Post
            </button>
        </div>
    </div>

    <?php if (empty($myPosts)): ?>
    <div class="empty-state-modern">
        <div class="es-icon"><i class="bi bi-collection"></i></div>
        <h4>No posts yet</h4>
        <p>You haven't posted any lost or found items.</p>
        <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#postModal">
            <i class="bi bi-plus-lg me-2"></i>Post Your First Item
        </button>
    </div>
    <?php else: ?>
    <div class="row g-3">
    <?php foreach ($myPosts as $p):
        $icon = $catIcons[$p['category']] ?? 'bi-box';
        $imgSrc = $p['image'] ? UPLOAD_URL . e($p['image']) : null;
    ?>
        <div class="col-12">
            <div class="my-post-row">
                <?php if ($imgSrc): ?>
                    <img src="<?= $imgSrc ?>" class="my-post-thumb" alt="">
                <?php else: ?>
                    <div class="my-post-thumb d-flex align-items-center justify-content-center"
                         style="background:#F0F4F8;border-radius:8px;font-size:1.4rem;color:#CBD5E0">
                        <i class="bi <?= $icon ?>"></i>
                    </div>
                <?php endif; ?>

                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="type-badge <?= e($p['type']) ?>"><?= ucfirst($p['type']) ?></span>
                        <strong class="small"><?= e($p['title']) ?></strong>
                        <?php if ($p['status'] !== 'active'): ?>
                            <span class="badge bg-secondary rounded-pill" style="font-size:.65rem"><?= ucfirst($p['status']) ?></span>
                        <?php endif; ?>
                        <?php if ($p['pending_claims'] > 0): ?>
                            <span class="badge bg-warning text-dark rounded-pill" style="font-size:.65rem">
                                <?= $p['pending_claims'] ?> pending claim<?= $p['pending_claims']>1?'s':'' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted mt-1" style="font-size:.75rem">
                        <i class="bi bi-geo-alt me-1"></i><?= e($p['location']) ?>
                        &nbsp;·&nbsp;
                        <i class="bi bi-clock me-1"></i><?= timeAgo($p['created_at']) ?>
                    </div>
                </div>

                <div class="d-flex gap-2 ms-3">
                    <a href="item.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                    <form action="actions/delete_post.php" method="post" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Delete this post?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Smart Matches Section -->
<?php if (!empty($userMatches)): ?>
<div class="container-xl py-4">
    <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="match-badge-icon">
                    <i class="bi bi-lightning-charge-fill"></i>
                </div>
                <div>
                    <h5 class="text-white mb-0 fw-bold">Potential Matches Found</h5>
                    <p class="text-white-50 small mb-0">We found <?= count($userMatches) ?> possible match(es) for your items</p>
                </div>
            </div>
            
            <div class="row g-3">
                <?php foreach ($userMatches as $match): ?>
                <div class="col-md-6">
                    <div class="match-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="match-score"><?= number_format($match['match_score'], 0) ?>% Match</span>
                            <div class="match-actions">
                                <button class="btn btn-sm btn-success confirm-match-btn" 
                                        data-match-id="<?= $match['id'] ?>">
                                    <i class="bi bi-check-lg"></i> Confirm
                                </button>
                                <button class="btn btn-sm btn-outline-secondary dismiss-match-btn"
                                        data-match-id="<?= $match['id'] ?>">
                                    <i class="bi bi-x-lg"></i> Dismiss
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-3">
                            <!-- Lost Item -->
                            <div class="flex-grow-1">
                                <div class="match-item-label text-danger small fw-bold">LOST ITEM</div>
                                <div class="match-item-details">
                                    <?php if ($match['lost_image']): ?>
                                        <img src="<?= UPLOAD_URL . e($match['lost_image']) ?>" class="match-thumb">
                                    <?php else: ?>
                                        <div class="match-thumb-placeholder">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="fw-semibold small text-truncate"><?= e($match['lost_title']) ?></div>
                                    <div class="text-muted" style="font-size:.7rem"><?= e($match['lost_category']) ?></div>
                                    <div class="text-muted" style="font-size:.7rem"><?= e($match['lost_location']) ?></div>
                                </div>
                            </div>
                            
                            <div class="match-divider">
                                <i class="bi bi-arrow-left-right"></i>
                            </div>
                            
                            <!-- Found Item -->
                            <div class="flex-grow-1">
                                <div class="match-item-label text-success small fw-bold">FOUND ITEM</div>
                                <div class="match-item-details">
                                    <?php if ($match['found_image']): ?>
                                        <img src="<?= UPLOAD_URL . e($match['found_image']) ?>" class="match-thumb">
                                    <?php else: ?>
                                        <div class="match-thumb-placeholder">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="fw-semibold small text-truncate"><?= e($match['found_title']) ?></div>
                                    <div class="text-muted" style="font-size:.7rem"><?= e($match['found_category']) ?></div>
                                    <div class="text-muted" style="font-size:.7rem"><?= e($match['found_location']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Confirm match
    document.querySelectorAll('.confirm-match-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const matchId = this.dataset.matchId;
            if (confirm('Are you sure this is a match? This will help connect the items.')) {
                fetch('actions/match_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'match_id=' + matchId + '&action=confirm&csrf_token=<?= generateCsrfToken() ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });
    
    // Dismiss match
    document.querySelectorAll('.dismiss-match-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const matchId = this.dataset.matchId;
            if (confirm('Dismiss this match?')) {
                fetch('actions/match_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'match_id=' + matchId + '&action=dismiss&csrf_token=<?= generateCsrfToken() ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
