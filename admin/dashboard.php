<?php
$pageTitle   = 'Dashboard — Admin';
$topbarTitle = 'Dashboard';
$topbarIcon  = 'grid-fill';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/header.php';

// Stats
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM users WHERE is_admin = 0)          AS total_users,
        (SELECT COUNT(*) FROM posts)                             AS total_posts,   
        (SELECT COUNT(*) FROM posts WHERE type='lost' AND status='active')  AS lost_active,
        (SELECT COUNT(*) FROM posts WHERE type='found' AND status='active') AS found_active,
        (SELECT COUNT(*) FROM posts WHERE status='returned')     AS returned,
        (SELECT COUNT(*) FROM claims WHERE status='pending')     AS pending_claims
")->fetch_assoc();

// Recent users
$recentUsers = $conn->query("SELECT * FROM users WHERE is_admin=0 ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Recent posts
$recentPosts = $conn->query("
    SELECT p.*, u.name AS poster_name FROM posts p
    JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#EBF4FF"><i class="bi bi-people-fill text-primary" style="color:#0F2D5C"></i></div>
            <div><div class="stat-num"><?= number_format($stats['total_users']) ?></div><div class="stat-label">Registered Users</div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#FFF5F5"><i class="bi bi-exclamation-circle-fill" style="color:#E53E3E"></i></div>
            <div><div class="stat-num"><?= number_format($stats['lost_active']) ?></div><div class="stat-label">Lost (Active)</div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#F0FFF4"><i class="bi bi-hand-thumbs-up-fill" style="color:#276749"></i></div>
            <div><div class="stat-num"><?= number_format($stats['found_active']) ?></div><div class="stat-label">Found (Active)</div></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#FFFBEB"><i class="bi bi-check-circle-fill" style="color:#D97706"></i></div>
            <div><div class="stat-num"><?= number_format($stats['returned']) ?></div><div class="stat-label">Items Returned</div></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Recent Users -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-person-plus me-2 text-primary"></i>Recent Users</h6>
                <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <table class="table table-hover">
                <thead><tr><th>User</th><th>Student ID</th><th>Joined</th></tr></thead>
                <tbody>
                <?php foreach ($recentUsers as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar-sm"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                            <div>
                                <div class="fw-semibold small"><?= e($u['name']) ?></div>
                                <div class="text-muted" style="font-size:.72rem"><?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted small"><?= e($u['student_id'] ?? '—') ?></td>
                    <td class="text-muted small"><?= timeAgo($u['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Posts -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-collection me-2 text-primary"></i>Recent Posts</h6>
                <a href="<?= BASE_URL ?>admin/posts.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <table class="table table-hover">
                <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>When</th></tr></thead>
                <tbody>
                <?php foreach ($recentPosts as $p): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>item.php?id=<?= $p['id'] ?>" class="fw-semibold small text-decoration-none">
                            <?= e(mb_strimwidth($p['title'],0,30,'…')) ?>
                        </a>
                        <div class="text-muted" style="font-size:.7rem">by <?= e($p['poster_name']) ?></div>
                    </td>
                    <td>
                        <span class="badge rounded-pill <?= $p['type']==='lost'?'bg-danger':'bg-success' ?>" style="font-size:.65rem">
                            <?= ucfirst($p['type']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge rounded-pill bg-secondary" style="font-size:.65rem"><?= ucfirst($p['status']) ?></span>
                    </td>
                    <td class="text-muted small"><?= timeAgo($p['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary -->
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header">
                <h6 class="admin-card-title"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Summary</h6>
            </div>
            <div class="p-3 d-flex gap-4 flex-wrap">
                <div class="text-center px-3">
                    <div style="font-size:1.6rem;font-weight:800;font-family:'Outfit',sans-serif"><?= $stats['total_posts'] ?></div>
                    <div class="text-muted small">Total Posts</div>
                </div>
                <div class="text-center px-3 border-start">
                    <div style="font-size:1.6rem;font-weight:800;font-family:'Outfit',sans-serif;color:#E53E3E"><?= $stats['lost_active'] ?></div>
                    <div class="text-muted small">Lost Active</div>
                </div>
                <div class="text-center px-3 border-start">
                    <div style="font-size:1.6rem;font-weight:800;font-family:'Outfit',sans-serif;color:#276749"><?= $stats['found_active'] ?></div>
                    <div class="text-muted small">Found Active</div>
                </div>
                <div class="text-center px-3 border-start">
                    <div style="font-size:1.6rem;font-weight:800;font-family:'Outfit',sans-serif;color:#D97706"><?= $stats['returned'] ?></div>
                    <div class="text-muted small">Returned</div>
                </div>
                <div class="text-center px-3 border-start">
                    <div style="font-size:1.6rem;font-weight:800;font-family:'Outfit',sans-serif;color:#6B46C1"><?= $stats['pending_claims'] ?></div>
                    <div class="text-muted small">Pending Claims</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
