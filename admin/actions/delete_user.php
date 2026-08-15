<?php
require_once __DIR__ . '/../../config/db.php';

/* ---------------------------
   CSRF VALIDATION
----------------------------*/
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(BASE_URL . 'admin/users.php');
}

/* ---------------------------
   ONLY POST REQUEST
----------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    redirect(BASE_URL . 'admin/users.php');
}

if (!isLoggedIn() || empty($_SESSION['is_admin'])) redirect(BASE_URL . 'admin/login.php');

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    setFlash('error', 'Invalid user ID.');
    redirect(BASE_URL . 'admin/users.php');
}

// Delete all post images first
$imgs = $conn->prepare("SELECT image FROM posts WHERE user_id = ?");
$imgs->bind_param('i', $id);
$imgs->execute();
foreach ($imgs->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    if ($row['image'] && file_exists(UPLOAD_DIR . $row['image'])) {
        unlink(UPLOAD_DIR . $row['image']);
    }
}

// Delete user (cascades posts + claims)
$del = $conn->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
$del->bind_param('i', $id);
$del->execute();

setFlash('success', 'User and all their data deleted.');
redirect(BASE_URL . 'admin/users.php');
