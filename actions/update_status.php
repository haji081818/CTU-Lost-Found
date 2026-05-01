<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$postId    = (int)($_GET['id']     ?? 0);
$newStatus =       $_GET['status'] ?? '';

if (!$postId || !in_array($newStatus, ['active','claimed','returned'])) redirect(BASE_URL);

$stmt = $conn->prepare("UPDATE posts SET status=? WHERE id=? AND user_id=?");
$stmt->bind_param('sii', $newStatus, $postId, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    setFlash('success', 'Item status updated to "' . ucfirst($newStatus) . '".');
} else {
    setFlash('error', 'Could not update status.');
}

redirect(BASE_URL . "item.php?id=$postId");
