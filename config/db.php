<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'ctu_lost_found');

define('BASE_URL', 'http://localhost/ctu-lost-found/'); // adjust as needed
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:2rem;color:red;">
        <h2>Database Connection Failed</h2>
        <p>' . htmlspecialchars($conn->connect_error) . '</p>
        <p>Please check your <code>config/db.php</code> settings.</p>
    </div>');
}

$conn->set_charset('utf8mb4');

// Helper: flash messages via session
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// Helper: redirect
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// Helper: is logged in
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Helper: require login
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to continue.');
        redirect(BASE_URL . 'login.php');
    }
}

// Helper: sanitize output
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Helper: time ago
function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('M j, Y', $time);
}