<?php
$pageTitle   = 'All Posts — Admin';
$topbarTitle = 'All Posts';
$topbarIcon  = 'collection-fill';
require_once __DIR__ . '/includes/header.php';

$search     = trim($_GET['q']    ?? '');
$typeFilter =      $_GET['type'] ?? 'all';

$where  = ['1=1'];
$params = [];
$types  = '';

if ($typeFilter === 'lost' || $typeFilter === 'found') {
    $where[]  = 'p.type = ?';
    $params[] = $typeFilter;
    $types   .= 's';
}
if ($search) {
    $where[]  = '(p.title LIKE ? OR p.location LIKE ? OR u.name LIKE ?)';
    $like      = "%$search%";
    $params[]  = $like; $params[] = $like; $params[] = $like;
    $types    .= 'sss';
}

$sql  = "SELECT p.*, u.name AS poster_name FROM posts p JOIN users u ON u.id=p.user_id WHERE " . implode(' AND ', $where) . " ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-card">
    <div class="admin-card-header flex-wrap gap-2">
        <h6 class="admin-card-title"><i class="bi bi-collection me-2"></i>Posts <span class="text-muted fw-normal">(<?= count($posts) ?>)</span></h6>
        <form method="get" class="d-flex gap-2 flex-wrap">
            <div class="d-flex gap-1">
                <a href="posts.php?type=all&q=<?= e($search) ?>" class="btn btn-sm <?= $typeFilter==='all'?'btn-primary':'btn-outline-secondary' ?>">All</a>
                <a href="posts.php?type=lost&q=<?= e($search) ?>" class="btn btn-sm <?= $typeFilter==='lost'?'btn-danger':'btn-outline-danger' ?>">Lost</a>
                <a href="posts.php?type=found&q=<?= e($search) ?>" class="btn btn-sm <?= $typeFilter==='found'?'btn-success':'btn-outline-success' ?>">Found</a>
            </div>
            <input type="hidden" name="type" value="<?= e($typeFilter) ?>">
            <div class="admin-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search title, location, user…"
                       value="<?= e($search) ?>" style="width:220px">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Search</button>
            <?php if ($search): ?>
                <a href="posts.php?type=<?= e($typeFilter) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($posts)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox" style="font-size:2.5rem;opacity:.3"></i>
            <p class="mt-2 small">No posts found.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>#</th><th>Item</th><th>Posted By</th><th>Type</th><th>Status</th><th>Location</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($posts as $i => $p): ?>
            <tr>
                <td class="text-muted small"><?= $i+1 ?></td>
                <td>
                    <a href="<?= BASE_URL ?>item.php?id=<?= $p['id'] ?>" target="_blank"
                       class="fw-semibold small text-decoration-none">
                        <?= e(mb_strimwidth($p['title'],0,35,'…')) ?>
                    </a>
                </td>
                <td class="small text-muted"><?= e($p['poster_name']) ?></td>
                <td><span class="badge rounded-pill <?= $p['type']==='lost'?'bg-danger':'bg-success' ?>" style="font-size:.65rem"><?= ucfirst($p['type']) ?></span></td>
                <td><span class="badge rounded-pill bg-secondary" style="font-size:.65rem"><?= ucfirst($p['status']) ?></span></td>
                <td class="small text-muted"><?= e($p['location']) ?></td>
                <td class="small text-muted"><?= timeAgo($p['created_at']) ?></td>
                <td>
                    <a href="actions/delete_post.php?id=<?= $p['id'] ?>"
                       class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.75rem"
                       onclick="return confirm('Delete this post?')">
                        <i class="bi bi-trash"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
