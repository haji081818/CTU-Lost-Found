<?php
$pageTitle = 'Item Detail — CTU Lost & Found';
require_once __DIR__ . '/includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { setFlash('error','Item not found.'); redirect(BASE_URL); }

$stmt = $conn->prepare("
    SELECT p.*, u.name AS poster_name
    FROM   posts p
    JOIN   users u ON u.id = p.user_id
    WHERE  p.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) { setFlash('error','Item not found.'); redirect(BASE_URL); }

$pageTitle = e($post['title']) . ' — CTU Lost & Found';
$isOwner   = $loggedIn && $_SESSION['user_id'] == $post['user_id'];
$imgSrc    = $post['image'] ? UPLOAD_URL . e($post['image']) : null;

// Claims for this post
$claimsStmt = $conn->prepare("
    SELECT c.*, u.name AS claimant_name
    FROM   claims c
    JOIN   users u ON u.id = c.claimant_id
    WHERE  c.post_id = ?
    ORDER  BY c.created_at DESC
");
$claimsStmt->bind_param('i', $id);
$claimsStmt->execute();
$claims = $claimsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// NAKIT AN / NAULI NABA SA USER?
$userClaim = null;
if ($loggedIn && !$isOwner) {
    foreach ($claims as $c) {
        if ($c['claimant_id'] == $_SESSION['user_id']) { $userClaim = $c; break; }
    }
}

// Similar items (same category, same type)
$simStmt = $conn->prepare("
    SELECT p.*, u.name AS poster_name
    FROM   posts p JOIN users u ON u.id = p.user_id
    WHERE  p.category = ? AND p.type = ? AND p.id <> ? AND p.status = 'active'
    ORDER  BY p.created_at DESC LIMIT 4
");
$simStmt->bind_param('ssi', $post['category'], $post['type'], $id);
$simStmt->execute();
$similar = $simStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container-xl py-4">
    <a href="<?= BASE_URL ?>" class="d-inline-flex align-items-center gap-2 text-muted mb-3 small fw-semibold"
       style="text-decoration:none">
        <i class="bi bi-arrow-left"></i> Back to Feed
    </a>

    <div class="row g-4">
        <!-- Image + Detail -->
        <div class="col-lg-7">
            <?php if ($imgSrc): ?>
                <img src="<?= $imgSrc ?>" alt="<?= e($post['title']) ?>" class="item-detail-img mb-3">
            <?php else: ?>
                <div class="item-detail-img mb-3 d-flex align-items-center justify-content-center"
                     style="background:#F0F4F8;border-radius:14px;height:320px">
                    <i class="bi bi-image text-muted" style="font-size:4rem;opacity:.25"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-5">
            <div class="detail-card">
                <!-- Type badge -->
                <div class="detail-type-badge <?= e($post['type']) ?>">
                    <?= $post['type']==='lost'
                        ? '<i class="bi bi-exclamation-circle-fill me-1"></i>Lost Item'
                        : '<i class="bi bi-hand-thumbs-up-fill me-1"></i>Found Item' ?>
                </div>

                <!-- Status -->
                <?php if ($post['status'] !== 'active'): ?>
                <div class="mb-2">
                    <span class="badge <?= $post['status']==='claimed' ? 'bg-purple' : 'bg-primary' ?>"
                          style="<?= $post['status']==='claimed' ? 'background:#6B46C1' : '' ?>; border-radius:50px; padding:.35rem .8rem">
                        <?= $post['status'] === 'claimed' ? '✓ Claimed' : '✓ Returned' ?>
                    </span>
                </div>
                <?php endif; ?>

                <h1 class="detail-title"><?= e($post['title']) ?></h1>

                <div class="detail-meta-item">
                    <i class="bi bi-tag-fill icon"></i>
                    <span><?= e($post['category']) ?></span>
                </div>
                <div class="detail-meta-item">
                    <i class="bi bi-geo-alt-fill icon"></i>
                    <span><?= e($post['location']) ?></span>
                </div>
                <div class="detail-meta-item">
                    <i class="bi bi-clock-fill icon"></i>
                    <span><?= timeAgo($post['created_at']) ?></span>
                </div>
                <div class="detail-meta-item">
                    <i class="bi bi-person-fill icon"></i>
                    <span>Posted by <strong><?= e($post['poster_name']) ?></strong></span>
                </div>
                <?php if (!empty($post['contact_number'])): ?>
                <div class="detail-meta-item">
                    <i class="bi bi-telephone-fill icon"></i>
                    <span>Contact: <a href="tel:<?= e($post['contact_number']) ?>"><strong><?= e($post['contact_number']) ?></strong></a></span>
                </div>
                <?php else: ?>
                <div class="detail-meta-item">
                    <i class="bi bi-telephone-fill icon"></i>
                    <span class="text-muted">No contact number provided</span>
                </div>
                <?php endif; ?>

                <hr>
                <p class="text-muted" style="font-size:.9rem;line-height:1.7"><?= nl2br(e($post['description'])) ?></p>

                <!-- Owner actions -->
                <?php if ($isOwner && $post['status'] === 'active'): ?>
                <hr>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="actions/update_status.php?id=<?= $post['id'] ?>&status=returned"
                       class="btn btn-sm btn-outline-primary"
                       onclick="return confirm('Mark this item as returned?')">
                        <i class="bi bi-check-circle me-1"></i>Mark Returned
                    </a>
                    <a href="actions/delete_post.php?id=<?= $post['id'] ?>"
                       class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Delete this post?')">
                        <i class="bi bi-trash me-1"></i>Delete
                    </a>
                </div>
                <?php endif; ?>

                <!-- Claim button (non-owner, found item, active) -->
                <?php if ($loggedIn && !$isOwner && $post['type']==='found' && $post['status']==='active'): ?>
                <hr>
                <?php if ($userClaim): ?>
                    <div class="alert alert-info py-2 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        You submitted a claim — status: <strong><?= e($userClaim['status']) ?></strong>
                    </div>
                <?php else: ?>
                    <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#claimModal">
                        <i class="bi bi-hand-index me-2"></i>Claim This Item
                    </button>
                <?php endif; ?>
                <?php elseif (!$loggedIn && $post['type']==='found' && $post['status']==='active'): ?>
                <hr>
                <a href="<?= BASE_URL ?>login.php" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log in to Claim
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Claims (owner view) -->
    <?php if ($isOwner && count($claims) > 0): ?>
    <div class="row mt-4">
        <div class="col-lg-7">
            <h5 class="fw-bold mb-3"><i class="bi bi-people me-2 text-primary"></i>Claims (<?= count($claims) ?>)</h5>
            <?php foreach ($claims as $claim): ?>
            <div class="claim-item">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <strong class="small"><?= e($claim['claimant_name']) ?></strong>
                    <span class="claim-status-pill <?= e($claim['status']) ?>"><?= ucfirst($claim['status']) ?></span>
                </div>
                <p class="text-muted small mb-2"><?= e($claim['description']) ?></p>
                <?php if ($claim['status'] === 'pending' && $post['status'] === 'active'): ?>
                <div class="d-flex gap-2">
                    <a href="actions/claim_action.php?id=<?= $claim['id'] ?>&action=approve"
                       class="btn btn-sm btn-success">
                        <i class="bi bi-check me-1"></i>Approve
                    </a>
                    <a href="actions/claim_action.php?id=<?= $claim['id'] ?>&action=reject"
                       class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-x me-1"></i>Reject
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Similar Items -->
    <?php if ($similar): ?>
    <div class="row mt-4">
        <div class="col-12">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-grid me-2 text-primary"></i>Similar <?= ucfirst(e($post['type'])) ?> Items
            </h5>
            <div class="row g-2">
                <?php foreach ($similar as $sim): ?>
                <div class="col-sm-6 col-md-3">
                    <a href="item.php?id=<?= $sim['id'] ?>" class="similar-card d-flex text-decoration-none">
                        <?php if ($sim['image']): ?>
                            <img src="<?= UPLOAD_URL . e($sim['image']) ?>" class="similar-thumb" alt="">
                        <?php else: ?>
                            <div class="similar-thumb d-flex align-items-center justify-content-center"
                                 style="background:#F0F4F8;font-size:1.4rem;color:#CBD5E0">
                                <i class="bi bi-image"></i>
                            </div>
                        <?php endif; ?>
                        <div style="min-width:0">
                            <div class="fw-semibold small text-truncate"><?= e($sim['title']) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><?= e($sim['location']) ?></div>
                            <div class="text-muted" style="font-size:.7rem"><?= timeAgo($sim['created_at']) ?></div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Claim Modal -->
<?php if ($loggedIn && !$isOwner && $post['type']==='found' && $post['status']==='active' && !$userClaim): ?>
<div class="modal fade" id="claimModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-hand-index me-2 text-primary"></i>Submit a Claim
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Describe the item to prove ownership. The poster will review your claim.
                </p>
                <form action="actions/submit_claim.php" method="post">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Your Description</label>
                        <textarea name="description" class="form-control" rows="4"
                                  placeholder="e.g. My bag has a white patch on the front pocket, inside has my name written…"
                                  required></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Submit Claim
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>