<?php
$pageTitle   = 'Users — Admin';
$topbarTitle = 'Manage Users';
$topbarIcon  = 'people-fill';
require_once __DIR__ . '/includes/header.php';

$search = trim($_GET['q'] ?? '');
$where  = 'WHERE is_admin = 0';
$params = [];
$types  = '';

if ($search) {
    $where   .= ' AND (name LIKE ? OR email LIKE ? OR student_id LIKE ?)';
    $like     = "%$search%";
    $params   = [$like, $like, $like];
    $types    = 'sss';
}

$sql  = "SELECT * FROM users $where ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-card">
    <div class="admin-card-header">
        <h6 class="admin-card-title"><i class="bi bi-people me-2"></i>All Users <span class="text-muted fw-normal">(<?= count($users) ?>)</span></h6>
        <form method="get" class="d-flex gap-2">
            <div class="admin-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name, email, ID…"
                       value="<?= e($search) ?>" style="width:240px">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Search</button>
            <?php if ($search): ?>
                <a href="users.php" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($users)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-search" style="font-size:2.5rem;opacity:.3"></i>
            <p class="mt-2">No users found<?= $search ? ' for "'.e($search).'"' : '' ?>.</p>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Student ID</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $i => $u): ?>
            <tr>
                <td class="text-muted small"><?= $i + 1 ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <?php if ($u['avatar']): ?>
                            <img src="<?= UPLOAD_URL . e($u['avatar']) ?>" class="rounded-circle" style="width:34px;height:34px;object-fit:cover">
                        <?php else: ?>
                            <div class="user-avatar-sm"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="fw-semibold small"><?= e($u['name']) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><?= e($u['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td class="small"><?= e($u['student_id'] ?? '—') ?></td>
                <td class="small"><?= e($u['course'] ?? '—') ?></td>
                <td class="small"><?= e($u['year_level'] ?? '—') ?></td>
                <td class="small"><?= e($u['phone'] ?? '—') ?></td>
                <td class="text-muted small"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="view_user.php?id=<?= $u['id'] ?>" class="btn btn-xs btn-outline-primary" style="padding:.2rem .5rem;font-size:.75rem">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-xs btn-outline-secondary" style="padding:.2rem .5rem;font-size:.75rem">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="actions/delete_user.php?id=<?= $u['id'] ?>"
                           class="btn btn-xs btn-outline-danger" style="padding:.2rem .5rem;font-size:.75rem"
                           onclick="return confirm('Delete <?= e($u['name']) ?>? This will also delete all their posts.')">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
