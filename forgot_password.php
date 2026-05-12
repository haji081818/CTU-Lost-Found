<?php
$pageTitle = 'Forgot Password — CTU Lost & Found';
require_once __DIR__ . '/includes/header.php';

// ── PHPMailer ──────────────────────────────────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

// ── YOUR GMAIL SETTINGS — edit these ──────────────────────
define('MAIL_USERNAME', 'groovifyui@gmail.com');
define('MAIL_PASSWORD', 'ouknuefctybytomd');
define('MAIL_FROM_NAME', 'CTU Danao Lost & Found');
// ──────────────────────────────────────────────────────────

if (isLoggedIn()) redirect(BASE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Please enter a valid email address.');
        redirect(BASE_URL . 'forgot_password.php');
    }

    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->bind_param('s', $email);
        $del->execute();

        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $ins->bind_param('sss', $email, $token, $expiresAt);
        $ins->execute();

        $resetLink = BASE_URL . 'reset_password.php?token=' . urlencode($token);
        $name      = $user['name'];

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
            $mail->addAddress($email, $name);

            $mail->isHTML(true);
            $mail->Subject = 'CTU Danao Lost & Found — Password Reset';
            $mail->Body    = '
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Nunito, Arial, sans-serif; background: #F0F4F8; margin:0; padding:0; }
    .wrap { max-width:520px; margin:40px auto; background:#fff; border-radius:12px;
            box-shadow:0 4px 16px rgba(0,0,0,.09); overflow:hidden; }
    .header { background:linear-gradient(135deg,#0F2D5C,#1A4080); padding:28px 32px; text-align:center; }
    .header h1 { color:#fff; font-size:1.3rem; margin:0; letter-spacing:.5px; }
    .header p  { color:#FFD166; font-size:.8rem; margin:4px 0 0; letter-spacing:1px; text-transform:uppercase; }
    .body  { padding:32px; color:#1A202C; }
    .body p { line-height:1.7; margin:0 0 16px; }
    .btn   { display:block; width:fit-content; margin:24px auto; background:#FFAB00;
             color:#0F2D5C !important; text-decoration:none; padding:13px 32px;
             border-radius:8px; font-weight:700; font-size:1rem; }
    .note  { font-size:.8rem; color:#718096; text-align:center; margin-top:24px; }
    .footer{ background:#F0F4F8; padding:16px 32px; text-align:center;
             font-size:.75rem; color:#718096; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <h1>🔍 CTU Danao Lost &amp; Found</h1>
      <p>Cebu Technological University — Danao Campus</p>
    </div>
    <div class="body">
      <p>Hi <strong>' . htmlspecialchars($name) . '</strong>,</p>
      <p>We received a request to reset the password for your account. Click the button below to choose a new password.</p>
      <a href="' . $resetLink . '" class="btn">Reset My Password</a>
      <p>This link expires in <strong>1 hour</strong>. If you did not request a password reset, you can safely ignore this email — your password will not change.</p>
      <p class="note">Or copy and paste this link into your browser:<br>
        <a href="' . $resetLink . '" style="color:#0F2D5C;word-break:break-all;">' . $resetLink . '</a>
      </p>
    </div>
    <div class="footer">&copy; ' . date('Y') . ' Cebu Technological University – Danao Campus</div>
  </div>
</body>
</html>';
            $mail->AltBody = "Hi {$name},\r\n\r\nReset your password here:\r\n{$resetLink}\r\n\r\nThis link expires in 1 hour.\r\n\r\n— CTU Danao Lost & Found";

            $mail->send();

        } catch (Exception $e) {
            error_log('Mailer error: ' . $mail->ErrorInfo);
        }
    }

    setFlash('success', 'If that email is registered, a reset link has been sent. Check your inbox and spam folder.');
    redirect(BASE_URL . 'forgot_password.php');
}
?>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo"><i class="bi bi-key-fill"></i></div>
        <h2 class="auth-title">Forgot Password?</h2>
        <p class="auth-sub">Enter your registered email and we'll send you a reset link.</p>

        <form action="" method="post" novalidate>
            <div class="mb-4">
                <label class="form-label">Email Address</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="you@ctu.edu.ph"
                    required
                    autofocus
                    value="<?= e($_POST['email'] ?? '') ?>"
                >
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-send me-2"></i>Send Reset Link
            </button>
        </form>

        <p class="text-center mt-3 small text-muted">
            Remembered it?
            <a href="<?= BASE_URL ?>login.php" class="fw-semibold">Back to Login</a>
        </p>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>