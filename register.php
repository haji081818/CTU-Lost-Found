<?php
$pageTitle = 'Register — CTU Lost & Found';
require_once __DIR__ . '/includes/header.php';
if (isLoggedIn()) redirect(BASE_URL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name']       ?? '');
    $email      = trim($_POST['email']      ?? '');
    $studentId  = trim($_POST['student_id'] ?? '');
    $course     = trim($_POST['course']     ?? '');
    $yearLevel  = trim($_POST['year_level'] ?? '');
    $phone      = trim($_POST['phone']      ?? '');
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
    $ins  = $conn->prepare("INSERT INTO users (name, email, password, student_id, course, year_level, phone) VALUES (?,?,?,?,?,?,?)");
    $ins->bind_param('sssssss', $name, $email, $hash, $studentId, $course, $yearLevel, $phone);
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
            <div class="mb-3">
                <label class="form-label">Course <span class="text-muted">(optional)</span></label>
                <select name="course" class="form-select">
                    <option value="">— Select Course —</option>
                    <optgroup label="College of Engineering">
                        <option value="BSCE">BSCE – Civil Engineering</option>
                        <option value="BSME">BSME – Mechanical Engineering</option>
                        <option value="BSEE">BSEE – Electrical Engineering</option>
                        <option value="BSIE">BSIE – Industrial Engineering</option>
                        <option value="BSCpE">BSCpE – Computer Engineering</option>
                    </optgroup>
                    <optgroup label="College of Technology">
                        <option value="BSIT">BSIT – Information Technology</option>
                        <option value="BSMx">BSMx – Mechatronics</option>
                        <option value="BIT-CT">BIT – Computer Technology</option>
                        <option value="BIT-DT">BIT – Drafting Technology</option>
                        <option value="BIT-ET">BIT – Electrical Technology</option>
                        <option value="BIT-ELT">BIT – Electronics Technology</option>
                    </optgroup>
                    <optgroup label="College of Education">
                        <option value="BEEd">BEEd – Elementary Education</option>
                        <option value="BTLEd">BTLEd – Technology and Livelihood Education</option>
                        <option value="BSEd-English">BSEd – English</option>
                        <option value="BSEd-Math">BSEd – Mathematics</option>
                        <option value="BSEd-Science">BSEd – Science</option>
                        <option value="BSEd-SocStud">BSEd – Social Studies</option>
                    </optgroup>
                    <optgroup label="College of Management and Entrepreneurship">
                        <option value="BSHM">BSHM – Hospitality Management</option>
                        <option value="BSTM">BSTM – Tourism Management</option>
                        <option value="BSBA-MM">BSBA – Marketing Management</option>
                    </optgroup>
                </select>
            </div>
            <div class="row g-2 mb-3">
                <div class="col">
                    <label class="form-label">Year Level <span class="text-muted">(optional)</span></label>
                    <select name="year_level" class="form-select">
                        <option value="">— Select —</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
                <div class="col">
                    <label class="form-label">Phone <span class="text-muted">(optional)</span></label>
                    <input type="text" name="phone" class="form-control" placeholder="09XXXXXXXXX">
                </div>
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
      <!-- BACK BUTTON (LEFT SIDE) -->
<!-- BACK BUTTON (CENTERED) -->
<div class="text-center mt-2">
    <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>