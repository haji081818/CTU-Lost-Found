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

$sightingId = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$sightingId || !in_array($action, ['confirm', 'dismiss'])) {
    setFlash('error', 'Invalid parameters.');
    redirect(BASE_URL);
}

// Get sighting + verify ownership
$stmt = $conn->prepare("
    SELECT s.*, p.user_id AS post_owner, p.id AS post_id, p.status AS post_status
    FROM   sightings s JOIN posts p ON p.id = s.post_id
    WHERE  s.id = ?
");
$stmt->bind_param('i', $sightingId);
$stmt->execute();
$sighting = $stmt->get_result()->fetch_assoc();

if (!$sighting || $sighting['post_owner'] != $_SESSION['user_id']) {
    setFlash('error', 'Unauthorized.');
    redirect(BASE_URL);
}

if ($action === 'confirm') {
    // Mark sighting as confirmed
    $newStatus = 'confirmed';
    $upSighting = $conn->prepare("UPDATE sightings SET status=? WHERE id=?");
    $upSighting->bind_param('si', $newStatus, $sightingId);
    $upSighting->execute();
    
    // Mark post as reunited (recovered)
    $upPost = $conn->prepare("UPDATE posts SET status='reunited' WHERE id=?");
    $upPost->bind_param('i', $sighting['post_id']);
    $upPost->execute();
    
    // Create notification for the reporter
    $notificationsTableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($notificationsTableCheck && $notificationsTableCheck->num_rows > 0) {
        $notificationStmt = $conn->prepare("INSERT INTO notifications (user_id, type, related_id, message) VALUES (?, 'system', ?, ?)");
        $notificationMessage = "The owner confirmed retrieving their item based on your sighting!";
        $notificationStmt->bind_param('iis', $sighting['reporter_id'], $sighting['post_id'], $notificationMessage);
        $notificationStmt->execute();
    }
    
    setFlash('success', 'Sighting confirmed! Item marked as reunited.');
} elseif ($action === 'dismiss') {
    // Mark sighting as dismissed
    $newStatus = 'dismissed';
    $upSighting = $conn->prepare("UPDATE sightings SET status=? WHERE id=?");
    $upSighting->bind_param('si', $newStatus, $sightingId);
    $upSighting->execute();
    
    setFlash('info', 'Sighting dismissed.');
}

redirect(BASE_URL . "item.php?id={$sighting['post_id']}");
