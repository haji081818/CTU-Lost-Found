<?php
/* actions/update_status.php */
require_once __DIR__ . '/../config/db.php';

/* ---------------------------
   CSRF VALIDATION
----------------------------*/
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(BASE_URL);
}

/* ---------------------------
   ONLY POST REQUEST
----------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    redirect(BASE_URL);
}

requireLogin();

$postId    = (int)($_POST['id']     ?? 0);
$newStatus =       $_POST['status'] ?? '';

if (!$postId || !in_array($newStatus, ['active','claimed','returned'])) {
    setFlash('error', 'Invalid parameters.');
    redirect(BASE_URL);
}

$stmt = $conn->prepare("UPDATE posts SET status=? WHERE id=? AND user_id=?");
$stmt->bind_param('sii', $newStatus, $postId, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    setFlash('success', 'Item status updated to "' . ucfirst($newStatus) . '".');
} else {
    setFlash('error', 'Could not update status.');
}

redirect(BASE_URL . "item.php?id=$postId");
