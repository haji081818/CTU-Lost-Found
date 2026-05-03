<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';

if (isLoggedIn() && !empty($_SESSION['is_admin'])) {
    redirect(BASE_URL . 'admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ? AND is_admin = 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin']  = 1;
        redirect(BASE_URL . 'admin/dashboard.php');
    } else {
        $error = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — CTU Lost & Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;800&family=Nunito:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Nunito',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;
            background:linear-gradient(140deg,rgba(10,31,68,.93) 0%,rgba(15,45,92,.89) 45%,rgba(26,74,138,.86) 100%),
            url('../uploads/ctudanao.jpg') center/cover no-repeat fixed;
        }
        h1,h2,h3,.btn{font-family:'Outfit',sans-serif}
        .login-card{width:100%;max-width:400px;background:#fff;border-radius:18px;padding:2.25rem;box-shadow:0 20px 60px rgba(0,0,0,.25)}
        .admin-logo{width:58px;height:58px;background:linear-gradient(135deg,#0F2D5C,#2356B0);border-radius:16px;
            display:flex;align-items:center;justify-content:center;font-size:1.6rem;color:#FFAB00;margin-bottom:1.2rem;
            box-shadow:0 4px 16px rgba(15,45,92,.25)}
        .form-control{border-radius:9px;border:1.5px solid #E2E8F0;font-size:.9rem}
        .form-control:focus{border-color:#2356B0;box-shadow:0 0 0 3px rgba(35,86,176,.12)}
        .btn-admin{background:#0F2D5C;color:#fff;border:none;border-radius:9px;font-weight:700;padding:.6rem}
        .btn-admin:hover{background:#1A4080;color:#fff}
        .form-label{font-size:.83rem;font-weight:600;color:#4A5568}
    </style>
</head>
<body>
<div class="login-card">
    <div class="admin-logo"><i class="bi bi-shield-lock"></i></div>
    <h4 style="font-weight:800;margin-bottom:.2rem">Admin Login</h4>
    <p class="text-muted small mb-3">CTU Danao Lost &amp; Found — Admin Panel</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="admin@ctu.edu.ph" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <input type="password" name="password" id="adminPass" class="form-control" placeholder="••••••••" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePass" tabindex="-1">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-admin w-100">
            <i class="bi bi-shield-check me-2"></i>Log In as Admin
        </button>
    </form>
    <p class="text-center mt-3 small text-muted">
        <a href="<?= BASE_URL ?>">← Back to main site</a>
    </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('togglePass').addEventListener('click', function() {
    const f = document.getElementById('adminPass');
    const i = document.getElementById('toggleIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.classList.toggle('bi-eye'); i.classList.toggle('bi-eye-slash');
});
</script>
</body>
</html>
