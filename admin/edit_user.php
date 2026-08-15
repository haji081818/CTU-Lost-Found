<?php
$pageTitle   = 'Edit User — Admin';
$topbarTitle = 'Edit User';
$topbarIcon  = 'pencil-fill';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/header.php';

$id   = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 0");
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) { setFlash('error','User not found.'); redirect(BASE_URL.'admin/users.php'); }
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="view_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back
    </a>
</div>

<div class="row justify-content-center">
<div class="col-lg-7">
<div class="admin-card p-4">
    <h6 style="font-family:'Outfit',sans-serif;font-weight:800;margin-bottom:1.5rem">
        <i class="bi bi-person-gear me-2 text-primary"></i>Edit: <?= e($user['name']) ?>
    </h6>

    <form action="actions/update_user.php" method="post">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Student ID</label>
                <input type="text" name="student_id" class="form-control" value="<?= e($user['student_id'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Course</label>
                <input type="text" name="course" class="form-control" value="<?= e($user['course'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Year Level</label>
                <select name="year_level" class="form-select">
                    <option value="">Select...</option>
                    <?php foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year','Graduate'] as $yr): ?>
                    <option value="<?= $yr ?>" <?= ($user['year_level'] ?? '') === $yr ? 'selected' : '' ?>><?= $yr ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">New Password <span class="text-muted">(leave blank to keep current)</span></label>
                <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" minlength="6">
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="view_user.php?id=<?= $user['id'] ?>" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i>Save Changes
            </button>
        </div>
    </form>
</div>
</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
