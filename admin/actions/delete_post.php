<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
if (!isLoggedIn() || empty($_SESSION['is_admin'])) redirect(BASE_URL . 'admin/login.php');

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(BASE_URL . 'admin/posts.php');

$row = $conn->prepare("SELECT image FROM posts WHERE id = ?");
$row->bind_param('i', $id);
$row->execute();
$post = $row->get_result()->fetch_assoc();

if ($post) {
    if ($post['image'] && file_exists(UPLOAD_DIR . $post['image'])) {
        unlink(UPLOAD_DIR . $post['image']);
    }
    $conn->prepare("DELETE FROM posts WHERE id = ?")->bind_param('i', $id) ?: null;
    $del = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $del->bind_param('i', $id);
    $del->execute();
    setFlash('success', 'Post deleted.');
}

redirect(BASE_URL . 'admin/posts.php');
