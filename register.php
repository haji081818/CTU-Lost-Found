<?php
$pageTitle = 'Register — CTU Lost & Found';
require_once __DIR__ . '/includes/header.php';

if (isLoggedIn()) redirect(BASE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']       ?? '');
    $email      = trim($_POST['email']      ?? '');
    $studentId  = trim($_POST['student_id'] ?? '');
    $password   =      $_POST['password']   ?? '';
    $confirm    =      $_POST['confirm']    ?? '';

    if (!$name || !$email || !$password) {
        setFlash('error', 'Name, email, and password are required.');
        redirect(BASE_URL . 'register.php');
    }
    if ($password !== $confirm) {
        setFlash('error', 'Passwords do not match.');
        redirect(BASE_URL . 'register.php');
    }
    if (strlen($password) < 6) {
        setFlash('error', 'Password must be at least 6 characters.');
        redirect(BASE_URL . 'register.php');
    }

    // Check duplicate
    $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $chk->bind_param('s', $email);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        setFlash('error', 'That email is already registered.');
        redirect(BASE_URL . 'register.php');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins  = $conn->prepare("INSERT INTO users (name, email, password, student_id) VALUES (?,?,?,?)");
    $ins->bind_param('ssss', $name, $email, $hash, $studentId);
    if ($ins->execute()) {
        $userId = $conn->insert_id;
        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $name;
        setFlash('success', 'Account created! Welcome, ' . $name . '.');
        redirect(BASE_URL);
    } else {
        setFlash('error', 'Registration failed. Please try again.');
        redirect(BASE_URL . 'register.php');
    }
}
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo"><i class="bi bi-person-plus"></i></div>
        <h2 class="auth-title">Create account</h2>
        <p class="auth-sub">Join CTU Danao Lost &amp; Found</p>

        <form action="" method="post">
            <div class="mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="Juan dela Cruz" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="you@ctu.edu.ph" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Student ID <span class="text-muted">(optional)</span></label>
                <input type="text" name="student_id" class="form-control" placeholder="3240747">
            </div>
            <div class="row g-2 mb-4">
                <div class="col">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Min 6 chars" required>
                </div>
                <div class="col">
                    <label class="form-label">Confirm</label>
                    <input type="password" name="confirm" class="form-control" placeholder="Repeat" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-person-check me-2"></i>Create Account
            </button>
        </form>

        <p class="text-center mt-3 small text-muted">
            Already have an account?
            <a href="<?= BASE_URL ?>login.php" class="fw-semibold">Log in</a>
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
