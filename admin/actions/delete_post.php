<?php
require_once __DIR__ . '/../../config/db.php';

/* ---------------------------
   CSRF VALIDATION
----------------------------*/
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(BASE_URL . 'admin/posts.php');
}

/* ---------------------------
   ONLY POST REQUEST
----------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    redirect(BASE_URL . 'admin/posts.php');
}

if (!isLoggedIn() || empty($_SESSION['is_admin'])) redirect(BASE_URL . 'admin/login.php');

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    setFlash('error', 'Invalid post ID.');
    redirect(BASE_URL . 'admin/posts.php');
}

$row = $conn->prepare("SELECT image FROM posts WHERE id = ?");
$row->bind_param('i', $id);
$row->execute();
$post = $row->get_result()->fetch_assoc();

if ($post) {
    if ($post['image'] && file_exists(UPLOAD_DIR . $post['image'])) {
        unlink(UPLOAD_DIR . $post['image']);
    }
    $del = $conn->prepare("DELETE FROM posts WHERE id = ?");
    $del->bind_param('i', $id);
    $del->execute();
    setFlash('success', 'Post deleted.');
} else {
    setFlash('error', 'Post not found.');
}

redirect(BASE_URL . 'admin/posts.php');
