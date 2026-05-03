<?php
$pageTitle   = 'Claims — Admin';
$topbarTitle = 'All Claims';
$topbarIcon  = 'bell-fill';
require_once __DIR__ . '/includes/header.php';

$statusFilter = $_GET['status'] ?? 'all';
$where  = ['1=1'];
$params = []; $types = '';

if (in_array($statusFilter, ['pending','approved','rejected'])) {
    $where[]  = 'c.status = ?';
    $params[] = $statusFilter;
    $types   .= 's';
}

$sql  = "
    SELECT c.*, p.title AS post_title, p.id AS post_id,
           u.name AS claimant_name, o.name AS owner_name
    FROM   claims c
    JOIN   posts  p ON p.id = c.post_id
    JOIN   users  u ON u.id = c.claimant_id
    JOIN   users  o ON o.id = p.user_id
    WHERE  " . implode(' AND ', $where) . "
    ORDER  BY c.created_at DESC
";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$claims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h6 class="admin-card-title"><i class="bi bi-bell me-2"></i>Claims <span class="text-muted fw-normal">(<?= count($claims) ?>)</span></h6>
        <div class="d-flex gap-1">
            <?php foreach(['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $k=>$v): ?>
            <a href="claims.php?status=<?= $k ?>"
               class="btn btn-sm <?= $statusFilter===$k?'btn-primary':'btn-outline-secondary' ?>">
                <?= $v ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($claims)): ?>
        <div class="text-center py-5 text-muted"><i class="bi bi-bell-slash" style="font-size:2.5rem;opacity:.3"></i><p class="mt-2 small">No claims found.</p></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>#</th><th>Item</th><th>Claimant</th><th>Owner</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($claims as $i => $c): ?>
            <tr>
                <td class="text-muted small"><?= $i+1 ?></td>
                <td>
                    <a href="<?= BASE_URL ?>item.php?id=<?= $c['post_id'] ?>" target="_blank" class="fw-semibold small text-decoration-none">
                        <?= e(mb_strimwidth($c['post_title'],0,35,'…')) ?>
                    </a>
                </td>
                <td class="small"><?= e($c['claimant_name']) ?></td>
                <td class="small text-muted"><?= e($c['owner_name']) ?></td>
                <td>
                    <span class="badge rounded-pill <?= $c['status']==='approved'?'bg-success':($c['status']==='rejected'?'bg-danger':'bg-warning text-dark') ?>" style="font-size:.65rem">
                        <?= ucfirst($c['status']) ?>
                    </span>
                </td>
                <td class="text-muted small"><?= timeAgo($c['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
