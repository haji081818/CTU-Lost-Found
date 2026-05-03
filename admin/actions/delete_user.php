<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isLoggedIn() || empty($_SESSION['is_admin'])) redirect(BASE_URL . 'admin/login.php');

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . 'admin/users.php');

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
