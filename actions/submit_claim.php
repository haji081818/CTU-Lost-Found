<?php
/* actions/submit_claim.php */
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

$postId = (int)($_POST['post_id'] ?? 0);
$desc   = trim($_POST['description'] ?? '');

if (!$postId || !$desc) {
    setFlash('error', 'Missing required fields.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// Business logic checks
// 1. Verify post exists and is active
$stmt = $conn->prepare("SELECT id, user_id, status FROM posts WHERE id = ?");
$stmt->bind_param('i', $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    setFlash('error', 'Post not found.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// 2. Check post status - only allow claims on active posts
if ($post['status'] !== 'active') {
    setFlash('error', 'This item is no longer available for claims.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// 3. Prevent users from claiming their own items
if ($post['user_id'] == $_SESSION['user_id']) {
    setFlash('error', 'You cannot claim your own posted item.');
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
