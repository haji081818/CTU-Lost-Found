<?php
/* actions/submit_claim.php */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$postId = (int)($_POST['post_id'] ?? 0);
$desc   = trim($_POST['description'] ?? '');

if (!$postId || !$desc) {
    setFlash('error', 'Missing required fields.');
    redirect(BASE_URL . "item.php?id=$postId");
}

$stmt = $conn->prepare("INSERT INTO claims (post_id, claimant_id, description) VALUES (?,?,?)");
$stmt->bind_param('iis', $postId, $_SESSION['user_id'], $desc);

if ($stmt->execute()) {
    setFlash('success', 'Claim submitted! The poster will review it.');
} else {
    setFlash('error', 'You may have already submitted a claim for this item.');
}

redirect(BASE_URL . "item.php?id=$postId");
