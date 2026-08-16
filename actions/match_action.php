<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/smart_matching.php';

// CSRF validation
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$matchId = (int)($_POST['match_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$matchId || !in_array($action, ['confirm', 'dismiss'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Verify the match belongs to user's posts
$stmt = $conn->prepare("
    SELECT pm.*, p1.user_id as lost_user_id, p2.user_id as found_user_id
    FROM post_matches pm
    JOIN posts p1 ON pm.lost_post_id = p1.id
    JOIN posts p2 ON pm.found_post_id = p2.id
    WHERE pm.id = ?
");
$stmt->bind_param('i', $matchId);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) {
    echo json_encode(['success' => false, 'message' => 'Match not found']);
    exit;
}

// Check if user owns either post
if ($match['lost_user_id'] != $_SESSION['user_id'] && $match['found_user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to update this match']);
    exit;
}

// Update match status
$status = ($action === 'confirm') ? 'confirmed' : 'dismissed';
if (updateMatchStatus($matchId, $status)) {
    echo json_encode(['success' => true, 'message' => 'Match updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update match']);
}
