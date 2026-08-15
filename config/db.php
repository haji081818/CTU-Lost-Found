<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'ctu_lost_found');

define('BASE_URL', 'http://localhost/ctu-lost-found/'); // adjust as needed
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Email configuration (use environment variables or override in local config)
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: 'your-email@gmail.com');
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: 'your-app-password');
define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');

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

// CSRF Token Helpers
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// Secure File Upload Validator
function validateUploadedFile(array $file, array $allowedMimes = ALLOWED_TYPES): ?string {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return 'No file uploaded or invalid upload.';
    }

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return 'File size exceeds maximum allowed size of 5MB.';
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'File upload error code: ' . $file['error'];
    }

    // Verify actual MIME type using finfo (server-side check)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return 'Unable to verify file type.';
    }

    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        return 'Invalid file type. Allowed types: ' . implode(', ', $allowedMimes);
    }

    // Validate extension matches MIME type
    $allowedExtensions = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp']
    ];

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowedExtensions[$mimeType]) || !in_array($extension, $allowedExtensions[$mimeType])) {
        return 'File extension does not match the actual file type.';
    }

    return null; // Validation passed
}