<?php
/* actions/delete_post.php */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$postId = (int)($_GET['id'] ?? 0);
if (!$postId) redirect(BASE_URL);

// Fetch image before e delete ang post para ma delete ang file sa server
$row = $conn->prepare("SELECT image FROM posts WHERE id=? AND user_id=?");
$row->bind_param('ii', $postId, $_SESSION['user_id']);
$row->execute();
$post = $row->get_result()->fetch_assoc();

if (!$post) { setFlash('error','Post not found or unauthorized.'); redirect(BASE_URL); }

$del = $conn->prepare("DELETE FROM posts WHERE id=? AND user_id=?");
$del->bind_param('ii', $postId, $_SESSION['user_id']);
$del->execute();

if ($del->affected_rows > 0) {
    if ($post['image'] && file_exists(UPLOAD_DIR . $post['image'])) {
        unlink(UPLOAD_DIR . $post['image']);
    }
    setFlash('success', 'Post deleted successfully.');
} else {
    setFlash('error', 'Could not delete post.');
}

redirect(BASE_URL . 'index.php');
