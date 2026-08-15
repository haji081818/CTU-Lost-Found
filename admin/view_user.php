<?php
$pageTitle   = 'View User — Admin';
$topbarTitle = 'User Details';
$topbarIcon  = 'person-fill';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/header.php';

$id   = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 0");
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) { setFlash('error','User not found.'); redirect(BASE_URL.'admin/users.php'); }

// User's posts
$posts = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$posts->bind_param('i', $id);
$posts->execute();
$userPosts = $posts->get_result()->fetch_all(MYSQLI_ASSOC);

// User's claims
$claims = $conn->prepare("
    SELECT c.*, p.title AS post_title FROM claims c
    JOIN posts p ON p.id = c.post_id
    WHERE c.claimant_id = ? ORDER BY c.created_at DESC
");
$claims->bind_param('i', $id);
$claims->execute();
$userClaims = $claims->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="users.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i>Edit User</a>
    <form action="actions/delete_user.php" method="post" class="d-inline ms-auto">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $user['id'] ?>">
        <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Delete this user and all their posts?')">
            <i class="bi bi-trash me-1"></i>Delete User
        </button>
    </form>
</div>

<div class="row g-3">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="admin-card p-3 text-center">
            <?php if ($user['avatar']): ?>
                <img src="<?= UPLOAD_URL . e($user['avatar']) ?>" class="rounded-circle mb-2"
                     style="width:80px;height:80px;object-fit:cover;border:3px solid #E2E8F0">
            <?php else: ?>
                <div class="rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center fw-bold"
                     style="width:80px;height:80px;background:linear-gradient(135deg,#0F2D5C,#2356B0);color:#fff;font-size:2rem;font-family:'Outfit',sans-serif">
                    <?= strtoupper(substr($user['name'],0,1)) ?>
                </div>
            <?php endif; ?>
            <h5 class="mb-0 fw-bold"><?= e($user['name']) ?></h5>
            <div class="text-muted small mb-3"><?= e($user['email']) ?></div>
            <div class="d-flex justify-content-center gap-3">
                <div class="text-center">
                    <div class="fw-bold"><?= count($userPosts) ?></div>
                    <div class="text-muted" style="font-size:.72rem">Posts</div>
                </div>
                <div class="text-center border-start ps-3">
                    <div class="fw-bold"><?= count($userClaims) ?></div>
                    <div class="text-muted" style="font-size:.72rem">Claims</div>
                </div>
            </div>
        </div>

        <div class="admin-card mt-3 p-3">
            <h6 style="font-family:'Outfit',sans-serif;font-weight:800;font-size:.8rem;color:#718096;text-transform:uppercase;letter-spacing:.6px">Personal Info</h6>
            <?php
            $fields = [
                'Student ID'  => $user['student_id'],
                'Course'      => $user['course'],
                'Year Level'  => $user['year_level'],
                'Phone'       => $user['phone'],
                'Joined'      => date('F j, Y', strtotime($user['created_at'])),
            ];
            foreach ($fields as $label => $val): ?>
            <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.83rem">
                <span class="text-muted"><?= $label ?></span>
                <span class="fw-semibold"><?= e($val ?: '—') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Posts & Claims -->
    <div class="col-lg-8">
        <!-- Posts -->
        <div class="admin-card mb-3">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Posts (<?= count($userPosts) ?>)</h6>
            </div>
            <?php if (empty($userPosts)): ?>
                <p class="text-muted text-center py-3 small">No posts yet.</p>
            <?php else: ?>
            <table class="table table-hover">
                <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($userPosts as $p): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>item.php?id=<?= $p['id'] ?>" class="small fw-semibold text-decoration-none" target="_blank">
                        <?= e(mb_strimwidth($p['title'],0,35,'…')) ?>
                    </a></td>
                    <td><span class="badge rounded-pill <?= $p['type']==='lost'?'bg-danger':'bg-success' ?>" style="font-size:.65rem"><?= ucfirst($p['type']) ?></span></td>
                    <td><span class="badge rounded-pill bg-secondary" style="font-size:.65rem"><?= ucfirst($p['status']) ?></span></td>
                    <td class="text-muted small"><?= timeAgo($p['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Claims -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title">Claims Submitted (<?= count($userClaims) ?>)</h6>
            </div>
            <?php if (empty($userClaims)): ?>
                <p class="text-muted text-center py-3 small">No claims submitted.</p>
            <?php else: ?>
            <table class="table table-hover">
                <thead><tr><th>Item</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($userClaims as $c): ?>
                <tr>
                    <td class="small fw-semibold"><?= e(mb_strimwidth($c['post_title'],0,35,'…')) ?></td>
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
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
