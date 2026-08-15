<?php
$pageTitle = 'My Posts — CTU Lost & Found';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
requireLogin();

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
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#postModal">
            <i class="bi bi-plus-lg me-1"></i>New Post
        </button>
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
<?php require_once __DIR__ . '/includes/footer.php'; ?>
