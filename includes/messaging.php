<?php
/**
 * Secure In-App Messaging System
 * Handles private messaging between post owners and claimants
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Send a message in a claim thread
 * @param int $claimId The claim ID
 * @param int $senderId The sender user ID
 * @param int $receiverId The receiver user ID
 * @param string $message The message content
 * @return bool Success status
 */
function sendMessage($claimId, $senderId, $receiverId, $message) {
    global $conn;
    
    if (empty($message) || trim($message) === '') {
        return false;
    }
    
    // Check if messages table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        return false; // Messaging not available
    }
    
    $stmt = $conn->prepare("
        INSERT INTO messages (claim_id, sender_id, receiver_id, message)
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param('iiis', $claimId, $senderId, $receiverId, $message);
    return $stmt->execute();
}

/**
 * Get messages for a claim thread
 * @param int $claimId The claim ID
 * @param int $userId The current user ID (for read status)
 * @return array Array of messages
 */
function getClaimMessages($claimId, $userId) {
    global $conn;
    
    // Check if messages table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        return []; // Messaging not available
    }
    
    // Mark messages as read for the current user
    $markReadStmt = $conn->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE claim_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $markReadStmt->bind_param('ii', $claimId, $userId);
    $markReadStmt->execute();
    
    // Get messages
    $stmt = $conn->prepare("
        SELECT m.*, 
               u_sender.name as sender_name,
               u_sender.avatar as sender_avatar,
               u_receiver.name as receiver_name
        FROM messages m
        JOIN users u_sender ON m.sender_id = u_sender.id
        JOIN users u_receiver ON m.receiver_id = u_receiver.id
        WHERE m.claim_id = ?
        ORDER BY m.created_at ASC
    ");
    
    $stmt->bind_param('i', $claimId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get unread message count for a user
 * @param int $userId The user ID
 * @return int Number of unread messages
 */
function getUnreadMessageCount($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM messages 
        WHERE receiver_id = ? AND is_read = 0
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    
    $result = $stmt->get_result()->fetch_assoc();
    return (int)$result['count'];
}

/**
 * Get all message threads for a user
 * @param int $userId The user ID
 * @return array Array of message threads
 */
function getUserMessageThreads($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            c.id as claim_id,
            c.status as claim_status,
            p.id as post_id,
            p.title as post_title,
            p.type as post_type,
            p.image as post_image,
            u_claimant.name as claimant_name,
            u_poster.name as poster_name,
            (
                SELECT COUNT(*) 
                FROM messages m 
                WHERE m.claim_id = c.id 
                AND m.receiver_id = ? 
                AND m.is_read = 0
            ) as unread_count,
            (
                SELECT m.created_at 
                FROM messages m 
                WHERE m.claim_id = c.id 
                ORDER BY m.created_at DESC 
                LIMIT 1
            ) as last_message_time
        FROM claims c
        JOIN posts p ON c.post_id = p.id
        JOIN users u_claimant ON c.claimant_id = u_claimant.id
        JOIN users u_poster ON p.user_id = u_poster.id
        WHERE c.claimant_id = ? OR p.user_id = ?
        ORDER BY last_message_time DESC
    ");
    
    $stmt->bind_param('iii', $userId, $userId, $userId);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Check if user can participate in a claim thread
 * @param int $claimId The claim ID
 * @param int $userId The user ID
 * @return bool Permission status
 */
function canAccessClaimThread($claimId, $userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT c.post_id, c.claimant_id, p.user_id as poster_id
        FROM claims c
        JOIN posts p ON c.post_id = p.id
        WHERE c.id = ?
    ");
    
    $stmt->bind_param('i', $claimId);
    $stmt->execute();
    $claim = $stmt->get_result()->fetch_assoc();
    
    if (!$claim) {
        return false;
    }
    
    // User can access if they are the claimant or the post owner
    return ($claim['claimant_id'] == $userId || $claim['poster_id'] == $userId);
}

/**
 * Get message thread participants
 * @param int $claimId The claim ID
 * @return array Array of participant user IDs
 */
function getThreadParticipants($claimId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT c.claimant_id, p.user_id as poster_id
        FROM claims c
        JOIN posts p ON c.post_id = p.id
        WHERE c.id = ?
    ");
    
    $stmt->bind_param('i', $claimId);
    $stmt->execute();
    $claim = $stmt->get_result()->fetch_assoc();
    
    return [
        'claimant_id' => $claim['claimant_id'],
        'poster_id' => $claim['poster_id']
    ];
}

/**
 * Create a notification for a new message
 * @param int $receiverId The user ID to notify
 * @param int $claimId The claim ID
 * @param int $senderId The sender user ID
 * @return bool Success status
 */
function createMessageNotification($receiverId, $claimId, $senderId) {
    global $conn;
    
    // Check if notifications table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'notifications'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        return false; // Notifications not available
    }
    
    // Get sender name
    $senderStmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $senderStmt->bind_param('i', $senderId);
    $senderStmt->execute();
    $sender = $senderStmt->get_result()->fetch_assoc();
    $senderName = $sender ? $sender['name'] : 'Someone';
    
    // Get post details
    $claimStmt = $conn->prepare("
        SELECT p.title, p.type 
        FROM claims c 
        JOIN posts p ON c.post_id = p.id 
        WHERE c.id = ?
    ");
    $claimStmt->bind_param('i', $claimId);
    $claimStmt->execute();
    $claimData = $claimStmt->get_result()->fetch_assoc();
    
    $itemTitle = $claimData ? $claimData['title'] : 'an item';
    $itemType = $claimData ? ucfirst($claimData['type']) : 'Item';
    
    $message = "New message from {$senderName} regarding your claim on the {$itemType}: {$itemTitle}";
    
    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, type, related_id, message, is_read)
        VALUES (?, 'message', ?, ?, 0)
    ");
    
    $notifStmt->bind_param('iis', $receiverId, $claimId, $message);
    return $notifStmt->execute();
}
