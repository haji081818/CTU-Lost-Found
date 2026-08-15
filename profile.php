<?php
$pageTitle = 'My Profile — CTU Lost & Found';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
requireLogin();

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<div style="
    min-height: calc(100vh - 62px);
    background:
        linear-gradient(140deg, rgba(10,31,68,.92) 0%, rgba(15,45,92,.88) 45%, rgba(26,74,138,.85) 100%),
        url('<?= BASE_URL ?>uploads/ctudanao.jpg') center center / cover no-repeat fixed;
    padding: 2.5rem 0;
">
<div class="container-xl profile-wrap" style="max-width:860px">

    <h1 class="mb-1" style="font-family:'Outfit',sans-serif;font-weight:800;color:#fff;font-size:1.6rem">My Profile</h1>
    <p style="color:rgba(255,255,255,.6);font-size:.85rem;margin-bottom:1.5rem">Manage your personal information and password</p>

    <div class="row g-4">
        <!-- Profile Info -->
        <div class="col-lg-7">
            <div class="detail-card">
                <h6 class="sb-section-title mb-3"><i class="bi bi-person-fill me-2 text-primary"></i>Personal Information</h6>
                <form action="actions/update_profile.php" method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <?php if ($user['avatar']): ?>
                            <img src="<?= UPLOAD_URL . e($user['avatar']) ?>" alt="Avatar"
                                 class="rounded-circle" style="width:72px;height:72px;object-fit:cover;border:3px solid var(--border)">
                        <?php else: ?>
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                 style="width:72px;height:72px;background:linear-gradient(135deg,var(--primary),var(--primary-lt));color:#fff;font-size:1.8rem;font-family:'Outfit',sans-serif">
                                <?= strtoupper(substr($user['name'],0,1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <label class="form-label mb-1 fw-semibold">Profile Photo</label>
                            <input type="file" name="avatar" class="form-control form-control-sm" accept="image/*">
                            <div class="text-muted" style="font-size:.72rem">JPG, PNG or WEBP · max 2MB</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                            <div class="text-muted" style="font-size:.72rem">Email cannot be changed</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" value="<?= e($user['student_id'] ?? '') ?>" placeholder="2021-00001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>" placeholder="09XXXXXXXXX">
                        </div>
                        <div class="col-md-6">
                                <label class="form-label">Course</label>
                                <select name="course" class="form-select">
                                    <option value="">Select...</option>
                                    <?php foreach([
                                        'BEEd','BSEd','BTLEd','DPE',
                                        'BSCE','BSEE','BSME','BSIE','BSCpE',
                                        'BSIT','BSMX','BIT',
                                        'BSHM','BSTM','BSBA'
                                    ] as $course): ?>
                                    <option value="<?= $course ?>" <?= ($user['course'] ?? '') === $course ? 'selected' : '' ?>>
                                        <?= $course ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <div class="col-md-6">
                            <label class="form-label">Year Level</label>
                            <select name="year_level" class="form-select">
                                <option value="">Select...</option>
                                <?php foreach(['1st Year','2nd Year','3rd Year','4th Year','Graduate'] as $yr): ?>
                                <option value="<?= $yr ?>" <?= ($user['year_level'] ?? '') === $yr ? 'selected' : '' ?>><?= $yr ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-5">
            <!-- Account Info -->
            <div class="detail-card mb-3">
                <h6 class="sb-section-title mb-3"><i class="bi bi-info-circle-fill me-2 text-primary"></i>Account Info</h6>
                <div class="sb-stat-row">
                    <span class="sb-stat-label">Member since</span>
                    <span class="sb-stat-val"><?= date('M j, Y', strtotime($user['created_at'])) ?></span>
                </div>
                <div class="sb-stat-row">
                    <span class="sb-stat-label">Email</span>
                    <span class="sb-stat-val small"><?= e($user['email']) ?></span>
                </div>
                <div class="sb-stat-row">
                    <span class="sb-stat-label">Student ID</span>
                    <span class="sb-stat-val"><?= e($user['student_id'] ?? '—') ?></span>
                </div>
                <div class="sb-stat-row">
                    <span class="sb-stat-label">Course</span>
                    <span class="sb-stat-val"><?= e($user['course'] ?? '—') ?></span>
                </div>
                <div class="sb-stat-row">
                    <span class="sb-stat-label">Year Level</span>
                    <span class="sb-stat-val"><?= e($user['year_level'] ?? '—') ?></span>
                </div>
            </div>

            <!-- Change Password -->
            <div class="detail-card">
                <h6 class="sb-section-title mb-3"><i class="bi bi-shield-lock-fill me-2 text-primary"></i>Change Password</h6>
                <form action="actions/change_password.php" method="post">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-lock me-1"></i>Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
