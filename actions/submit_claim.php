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
$verificationAnswer = trim($_POST['verification_answer'] ?? '');

if (!$postId || !$desc) {
    setFlash('error', 'Missing required fields.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// Business logic checks
// 1. Verify post exists and is active (include verification_question if available)
$columnCheck = $conn->query("SHOW COLUMNS FROM posts LIKE 'verification_question'");
$hasVerification = $columnCheck->num_rows > 0;

if ($hasVerification) {
    $stmt = $conn->prepare("SELECT id, user_id, status, verification_question FROM posts WHERE id = ?");
} else {
    $stmt = $conn->prepare("SELECT id, user_id, status FROM posts WHERE id = ?");
}
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

// Check if verification is required and answer is provided
if ($hasVerification && !empty($post['verification_question']) && empty($verificationAnswer)) {
    setFlash('error', 'Please answer the verification question to submit your claim.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// Handle proof image upload
$proofImageName = null;
if (!empty($_FILES['proof_image']['name'])) {
    $file = $_FILES['proof_image'];
    $validationError = validateUploadedFile($file);
    if ($validationError !== null) {
        setFlash('error', $validationError);
        redirect(BASE_URL . "item.php?id=$postId");
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $proofImageName = uniqid('proof_', true) . '.' . $ext;
    
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $proofImageName)) {
        setFlash('error', 'Failed to upload proof image.');
        redirect(BASE_URL . "item.php?id=$postId");
    }
}

// Check if claims table has the new columns
$claimsColumnCheck = $conn->query("SHOW COLUMNS FROM claims LIKE 'verification_answer'");
$hasClaimsColumns = $claimsColumnCheck->num_rows > 0;

// Use appropriate INSERT statement based on database schema
if ($hasClaimsColumns) {
    $stmt = $conn->prepare("INSERT INTO claims (post_id, claimant_id, description, verification_answer, proof_image) VALUES (?,?,?,?,?)");
    $stmt->bind_param('iisss', $postId, $_SESSION['user_id'], $desc, $verificationAnswer, $proofImageName);
} else {
    $stmt = $conn->prepare("INSERT INTO claims (post_id, claimant_id, description) VALUES (?,?,?)");
    $stmt->bind_param('iis', $postId, $_SESSION['user_id'], $desc);
}

if ($stmt->execute()) {
    setFlash('success', 'Claim submitted! The poster will review it.');
} else {
    setFlash('error', 'You may have already submitted a claim for this item.');
}

redirect(BASE_URL . "item.php?id=$postId");
