<?php
/* actions/submit_sighting.php */
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
$sightingType = trim($_POST['sighting_type'] ?? '');
$locationDetails = trim($_POST['location_details'] ?? '');
$custodyOffice = trim($_POST['custody_office'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$postId || !$sightingType || !$locationDetails || !$message) {
    setFlash('error', 'Missing required fields.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// Validate sighting type
$validTypes = ['possession', 'custody', 'spotted'];
if (!in_array($sightingType, $validTypes)) {
    setFlash('error', 'Invalid sighting type.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// For custody type, office is required
if ($sightingType === 'custody' && empty($custodyOffice)) {
    setFlash('error', 'Please select the campus office where the item was surrendered.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// Business logic checks
// 1. Verify post exists and is a lost item
$stmt = $conn->prepare("SELECT id, user_id, status, type FROM posts WHERE id = ?");
$stmt->bind_param('i', $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    setFlash('error', 'Post not found.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// 2. Only allow sightings on lost items
if ($post['type'] !== 'lost') {
    setFlash('error', 'Sightings can only be reported for lost items.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// 3. Prevent users from reporting sightings on their own items
if ($post['user_id'] == $_SESSION['user_id']) {
    setFlash('error', 'You cannot report a sighting for your own lost item.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// 4. Check post status - only allow sightings on active posts
if ($post['status'] !== 'active') {
    setFlash('error', 'This item is no longer accepting sightings.');
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
    $proofImageName = uniqid('sighting_', true) . '.' . $ext;
    
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $proofImageName)) {
        setFlash('error', 'Failed to upload proof image.');
        redirect(BASE_URL . "item.php?id=$postId");
    }
}

// Get reporter name
$userStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->bind_param('i', $_SESSION['user_id']);
$userStmt->execute();
$userResult = $userStmt->get_result()->fetch_assoc();
$reporterName = $userResult['name'];

// Check if sightings table exists
$sightingsTableCheck = $conn->query("SHOW TABLES LIKE 'sightings'");
if (!$sightingsTableCheck || $sightingsTableCheck->num_rows === 0) {
    setFlash('error', 'Sightings feature is not available.');
    redirect(BASE_URL . "item.php?id=$postId");
}

// Insert sighting
$stmt = $conn->prepare("INSERT INTO sightings (post_id, reporter_id, reporter_name, sighting_type, location_details, custody_office, message, proof_image) VALUES (?,?,?,?,?,?,?,?)");
$stmt->bind_param('iissssss', $postId, $_SESSION['user_id'], $reporterName, $sightingType, $locationDetails, $custodyOffice, $message, $proofImageName);

if ($stmt->execute()) {
    // Create notification for the post owner (check if notifications table exists)
    $notificationsTableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($notificationsTableCheck && $notificationsTableCheck->num_rows > 0) {
        $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'system', ?, ?)");
        $notificationMessage = "New sighting reported for your lost item!";
        $notificationStmt->bind_param('iis', $post['user_id'], $postId, $notificationMessage);
        $notificationStmt->execute();
    }
    
    setFlash('success', 'Sighting reported! The owner will be notified.');
} else {
    setFlash('error', 'Failed to submit sighting. Please try again.');
}

redirect(BASE_URL . "item.php?id=$postId");
