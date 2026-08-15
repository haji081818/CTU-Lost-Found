<?php
$pageTitle = 'Log In — CTU Lost & Found';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) redirect(BASE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Invalid request. Please try again.');
        redirect(BASE_URL . 'login.php');
    }

    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin']  = $user['is_admin'];
        setFlash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect($user['is_admin'] ? BASE_URL . 'admin/dashboard.php' : BASE_URL);
    } else {
        setFlash('error', 'Invalid email or password.');
        redirect(BASE_URL . 'login.php');
    }
}
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo"><i class="bi bi-search-heart"></i></div>
        <h2 class="auth-title">Welcome back</h2>
        <p class="auth-sub">Log in to post and manage items</p>

        <form action="" method="post">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@ctu.edu.ph" required autofocus>
            </div>
            <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label mb-0">Password</label>
                    <a href="<?= BASE_URL ?>forgot_password.php" class="small text-muted">Forgot password?</a>
                </div>
                <div class="input-group mt-1">
                    <input type="password" name="password" id="passwordField" class="form-control" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            <div class="mb-4"></div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Log In
            </button>
        </form>

        <p class="text-center mt-3 small text-muted">
            Don't have an account?
            <a href="<?= BASE_URL ?>register.php" class="fw-semibold">Register here</a>
        </p>
        <p class="text-center small text-muted">
            <a href="<?= BASE_URL ?>admin/login.php" class="text-muted">Admin? Log in here</a>
        </p>
    </div>
</div>
<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const field = document.getElementById('passwordField');
    const icon  = document.getElementById('toggleIcon');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
