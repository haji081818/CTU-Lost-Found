<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/messaging.php';

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

$claimId = (int)($_POST['claim_id'] ?? 0);
$sightingId = (int)($_POST['sighting_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if (!$claimId && !$sightingId || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Handle claim messaging
if ($claimId) {
    // Check if user can access this claim thread
    if (!canAccessClaimThread($claimId, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to send messages in this thread']);
        exit;
    }

    // Get thread participants to determine receiver
    $participants = getThreadParticipants($claimId);
    $receiverId = ($participants['claimant_id'] == $_SESSION['user_id']) 
        ? $participants['poster_id'] 
        : $participants['claimant_id'];

    // Send message
    if (sendMessage($claimId, $_SESSION['user_id'], $receiverId, $message)) {
        // Create notification for the receiver
        createMessageNotification($receiverId, $claimId, $_SESSION['user_id']);
        
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
}

// Handle sighting messaging
if ($sightingId) {
    // Check if user can access this sighting thread
    if (!canAccessSightingThread($sightingId, $_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to send messages in this thread']);
        exit;
    }

    // Get sighting participants to determine receiver
    $participants = getSightingParticipants($sightingId);
    $receiverId = ($participants['reporter_id'] == $_SESSION['user_id']) 
        ? $participants['post_owner_id'] 
        : $participants['reporter_id'];

    // Send sighting message
    if (sendSightingMessage($sightingId, $_SESSION['user_id'], $receiverId, $message)) {
        // Create notification for the receiver
        createSightingMessageNotification($receiverId, $sightingId, $_SESSION['user_id']);
        
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
}
