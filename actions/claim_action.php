<?php
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

$claimId = (int)($_POST['id']     ?? 0);
$action  =       $_POST['action'] ?? '';

if (!$claimId || !in_array($action, ['approve','reject'])) {
    setFlash('error', 'Invalid parameters.');
    redirect(BASE_URL);
}

// Get claim + verify ownership
$stmt = $conn->prepare("
    SELECT c.*, p.user_id AS post_owner, p.id AS post_id
    FROM   claims c JOIN posts p ON p.id = c.post_id
    WHERE  c.id = ?
");
$stmt->bind_param('i', $claimId);
$stmt->execute();
$claim = $stmt->get_result()->fetch_assoc();

if (!$claim || $claim['post_owner'] != $_SESSION['user_id']) {
    setFlash('error', 'Unauthorized.');
    redirect(BASE_URL);
}

$newStatus = $action === 'approve' ? 'approved' : 'rejected';
$upClaim   = $conn->prepare("UPDATE claims SET status=? WHERE id=?");
$upClaim->bind_param('si', $newStatus, $claimId);
$upClaim->execute();

if ($action === 'approve') {
    $upPost = $conn->prepare("UPDATE posts SET status='claimed' WHERE id=?");
    $upPost->bind_param('i', $claim['post_id']);
    $upPost->execute();
    setFlash('success', 'Claim approved and item marked as claimed.');
} else {
    setFlash('info', 'Claim rejected.');
}

redirect(BASE_URL . "item.php?id={$claim['post_id']}");
