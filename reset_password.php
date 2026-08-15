<?php
$pageTitle = 'Reset Password — CTU Lost & Found';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) redirect(BASE_URL);

$token = trim($_GET['token'] ?? '');
$token = preg_replace('/[^a-f0-9]/', '', $token); // keep only valid hex chars
$error = null;

// Validate token
if (!$token) {
    setFlash('error', 'Invalid or missing reset token.');
    redirect(BASE_URL . 'login.php');
}

$stmt = $conn->prepare(
    "SELECT * FROM password_resets
     WHERE token = ? AND used = 0
     LIMIT 1"
);
$stmt->bind_param('s', $token);
$stmt->execute();
$reset = $stmt->get_result()->fetch_assoc();
// Check expiry using PHP time instead of MySQL NOW()
if ($reset && strtotime($reset['expires_at']) < time()) {
    $reset = null; // treat as expired
}
if (!$reset) {
    setFlash('error', 'This reset link is invalid or has expired. Please request a new one.');
    redirect(BASE_URL . 'forgot_password.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $newPassword     = $_POST['new_password']     ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$newPassword || !$confirmPassword) {
            $error = 'Both password fields are required.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);

            // Update user's password
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upd->bind_param('ss', $hash, $reset['email']);
            $upd->execute();

            // Mark token as used
            $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $mark->bind_param('s', $token);
            $mark->execute();

            setFlash('success', 'Password reset successfully! You can now log in with your new password.');
            redirect(BASE_URL . 'login.php');
        }
    }
}
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo"><i class="bi bi-shield-lock-fill"></i></div>
        <h2 class="auth-title">Set New Password</h2>
        <p class="auth-sub">Choose a strong password for your account.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 small">
                <i class="bi bi-x-circle-fill me-1"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="?token=<?= urlencode($token) ?>" method="post" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <div class="input-group">
                    <input
                        type="password"
                        name="new_password"
                        id="newPasswordField"
                        class="form-control"
                        placeholder="Min 6 characters"
                        required
                        minlength="6"
                        autofocus
                    >
                    <button class="btn btn-outline-secondary" type="button" id="toggleNew" tabindex="-1">
                        <i class="bi bi-eye" id="toggleNewIcon"></i>
                    </button>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <input
                        type="password"
                        name="confirm_password"
                        id="confirmPasswordField"
                        class="form-control"
                        placeholder="Repeat password"
                        required
                    >
                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirm" tabindex="-1">
                        <i class="bi bi-eye" id="toggleConfirmIcon"></i>
                    </button>
                </div>
                <div id="matchFeedback" class="form-text"></div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-check-lg me-2"></i>Reset Password
            </button>
        </form>

        <p class="text-center mt-3 small text-muted">
            <a href="<?= BASE_URL ?>login.php" class="fw-semibold">Back to Login</a>
        </p>
    </div>
</div>

<script>
// Toggle visibility for new password
document.getElementById('toggleNew').addEventListener('click', function () {
    const f = document.getElementById('newPasswordField');
    const i = document.getElementById('toggleNewIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.classList.toggle('bi-eye');
    i.classList.toggle('bi-eye-slash');
});

// Toggle visibility for confirm password
document.getElementById('toggleConfirm').addEventListener('click', function () {
    const f = document.getElementById('confirmPasswordField');
    const i = document.getElementById('toggleConfirmIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.classList.toggle('bi-eye');
    i.classList.toggle('bi-eye-slash');
});

// Live match feedback
const np = document.getElementById('newPasswordField');
const cp = document.getElementById('confirmPasswordField');
const fb = document.getElementById('matchFeedback');

function checkMatch() {
    if (!cp.value) { fb.textContent = ''; return; }
    if (np.value === cp.value) {
        fb.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Passwords match</span>';
    } else {
        fb.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Passwords do not match</span>';
    }
}
np.addEventListener('input', checkMatch);
cp.addEventListener('input', checkMatch);
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
