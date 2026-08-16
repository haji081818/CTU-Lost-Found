<?php
/**
 * Student ID Auto-Detection System
 * Detects CTU Student ID patterns and notifies registered owners
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Detect CTU Student ID patterns in text
 * CTU IDs typically follow patterns like: CTU-XXXX-XXXX or 7-digit numeric IDs
 * @param string $text Text to search for student IDs
 * @return array Array of detected student IDs
 */
function detectStudentIds($text) {
    $detectedIds = [];
    
    // Pattern 1: CTU-XXXX-XXXX format
    if (preg_match_all('/CTU[-\s]?(\d{4})[-\s]?(\d{4})/i', $text, $matches)) {
        foreach ($matches[0] as $match) {
            $detectedIds[] = strtoupper(str_replace(' ', '-', $match));
        }
    }
    
    // Pattern 2: 7-digit numeric IDs
    if (preg_match_all('/\b(\d{7})\b/', $text, $matches)) {
        foreach ($matches[1] as $match) {
            $detectedIds[] = $match;
        }
    }
    
    // Pattern 3: Generic student ID patterns (4-4, 3-4, etc.)
    if (preg_match_all('/\b(\d{3,4})[-\s]?(\d{4})\b/', $text, $matches)) {
        foreach ($matches[0] as $match) {
            if (!in_array($match, $detectedIds)) {
                $detectedIds[] = $match;
            }
        }
    }
    
    return array_unique($detectedIds);
}

/**
 * Find users by detected student IDs
 * @param array $studentIds Array of student IDs to search for
 * @return array Array of matching users
 */
function findUsersByStudentIds($studentIds) {
    global $conn;
    
    if (empty($studentIds)) {
        return [];
    }
    
    $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
    $types = str_repeat('s', count($studentIds));
    
    $stmt = $conn->prepare("
        SELECT id, name, email, student_id 
        FROM users 
        WHERE student_id IN ($placeholders)
    ");
    
    $stmt->bind_param($types, ...$studentIds);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Notify users about potential ID matches
 * @param int $postId The post ID where the ID was detected
 * @param array $matchedUsers Array of users who matched the detected IDs
 */
function notifyIdMatch($postId, $matchedUsers) {
    global $conn;
    
    foreach ($matchedUsers as $user) {
        // Store notification (you can extend this with a notifications table)
        // For now, we'll create a simple in-app notification mechanism
        
        // Create a notification record (assuming notifications table exists)
        $notificationStmt = $conn->prepare("
            INSERT INTO notifications (user_id, type, related_id, message, created_at)
            VALUES (?, 'id_match', ?, ?, NOW())
        ");
        
        $message = "Your student ID may have been found! Check post #{$postId}";
        $notificationStmt->bind_param('iis', $user['id'], $postId, $message);
        $notificationStmt->execute();
        
        // Could also send email notification here
        // sendIdMatchNotificationEmail($user['email'], $postId);
    }
}

/**
 * Process a post for student ID detection
 * @param int $postId The post ID to process
 * @param string $title Post title
 * @param string $description Post description
 * @param string $category Post category
 * @return array Detection results
 */
function processPostForIdDetection($postId, $title, $description, $category) {
    // Only process relevant categories
    $relevantCategories = ['Books & Documents', 'Keys & Cards'];
    if (!in_array($category, $relevantCategories)) {
        return ['detected' => false, 'reason' => 'Category not relevant'];
    }
    
    $combinedText = $title . ' ' . $description;
    $detectedIds = detectStudentIds($combinedText);
    
    if (empty($detectedIds)) {
        return ['detected' => false, 'reason' => 'No student IDs detected'];
    }
    
    $matchedUsers = findUsersByStudentIds($detectedIds);
    
    if (empty($matchedUsers)) {
        return ['detected' => true, 'ids' => $detectedIds, 'matches' => 0];
    }
    
    notifyIdMatch($postId, $matchedUsers);
    
    return [
        'detected' => true,
        'ids' => $detectedIds,
        'matches' => count($matchedUsers),
        'users' => $matchedUsers
    ];
}

/**
 * Simple OCR-like processing for image metadata
 * This is a placeholder for actual OCR implementation
 * Real OCR would require libraries like Tesseract OCR
 * @param string $imagePath Path to the uploaded image
 * @return string Extracted text from image
 */
function extractTextFromImage($imagePath) {
    // Placeholder for actual OCR implementation
    // In production, you would use:
    // - Tesseract OCR with exec() or library
    // - Google Cloud Vision API
    // - AWS Rekognition
    // - Azure Computer Vision
    
    // For now, return empty string
    return '';
}

/**
 * Enhanced post processing with image OCR
 * @param int $postId The post ID to process
 * @param string $title Post title
 * @param string $description Post description
 * @param string $category Post category
 * @param string $imagePath Path to uploaded image (if any)
 * @return array Detection results
 */
function processPostWithOcr($postId, $title, $description, $category, $imagePath = null) {
    $combinedText = $title . ' ' . $description;
    
    // If image exists and OCR is available, extract text from it
    if ($imagePath && file_exists($imagePath)) {
        $ocrText = extractTextFromImage($imagePath);
        if (!empty($ocrText)) {
            $combinedText .= ' ' . $ocrText;
        }
    }
    
    return processPostForIdDetection($postId, $title, $combinedText, $category);
}
