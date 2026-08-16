<?php
/**
 * Smart Matching Engine for CTU Lost & Found
 * Automatically matches lost items with found items based on:
 * - Category match (35% weight)
 * - Location match (25% weight) 
 * - Title/Description keyword overlap (40% weight)
 */

require_once __DIR__ . '/../config/db.php';

/**
 * Calculate match score between a lost item and found item
 * @param array $lostPost Lost item data
 * @param array $foundPost Found item data
 * @return float Match score (0-100)
 */
function calculateMatchScore($lostPost, $foundPost) {
    $score = 0;
    
    // Category match (35% weight)
    if ($lostPost['category'] === $foundPost['category']) {
        $score += 35;
    }
    
    // Location match (25% weight)
    // Check both exact location and zone match
    $locationMatch = false;
    if ($lostPost['location'] === $foundPost['location']) {
        $score += 25;
        $locationMatch = true;
    } elseif (!empty($lostPost['location_zone']) && !empty($foundPost['location_zone']) && 
               $lostPost['location_zone'] === $foundPost['location_zone']) {
        $score += 15; // Partial match for same zone
        $locationMatch = true;
    }
    
    // Keyword overlap (40% weight)
    $keywordsLost = extractKeywords($lostPost['title'] . ' ' . $lostPost['description']);
    $keywordsFound = extractKeywords($foundPost['title'] . ' ' . $foundPost['description']);
    
    $keywordScore = calculateKeywordOverlap($keywordsLost, $keywordsFound);
    $score += $keywordScore * 0.4; // 40% weight
    
    return min(100, $score); // Cap at 100
}

/**
 * Extract meaningful keywords from text
 * @param string $text Input text
 * @return array Array of keywords
 */
function extractKeywords($text) {
    // Convert to lowercase and remove special characters
    $text = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $text));
    
    // Remove common stop words
    $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 
                  'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 
                  'should', 'may', 'might', 'must', 'shall', 'can', 'need', 'dare', 
                  'ought', 'used', 'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 
                  'from', 'as', 'into', 'through', 'during', 'before', 'after', 'above', 
                  'below', 'between', 'under', 'again', 'further', 'then', 'once', 'here', 
                  'there', 'when', 'where', 'why', 'how', 'all', 'each', 'few', 'more', 
                  'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 
                  'same', 'so', 'than', 'too', 'very', 'just', 'and', 'but', 'if', 'or', 
                  'because', 'until', 'while', 'this', 'that', 'these', 'those', 'i', 'me', 
                  'my', 'myself', 'we', 'our', 'ours', 'ourselves', 'you', 'your', 'yours', 
                  'yourself', 'yourselves', 'he', 'him', 'his', 'himself', 'she', 'her', 
                  'hers', 'herself', 'it', 'its', 'itself', 'they', 'them', 'their', 'theirs', 
                  'themselves', 'what', 'which', 'who', 'whom', 'lost', 'found', 'item'];
    
    $words = explode(' ', $text);
    $keywords = [];
    
    foreach ($words as $word) {
        $word = trim($word);
        if (strlen($word) > 2 && !in_array($word, $stopWords)) {
            $keywords[] = $word;
        }
    }
    
    return array_unique($keywords);
}

/**
 * Calculate keyword overlap between two keyword arrays
 * @param array $keywords1 First set of keywords
 * @param array $keywords2 Second set of keywords
 * @return float Overlap score (0-100)
 */
function calculateKeywordOverlap($keywords1, $keywords2) {
    if (empty($keywords1) || empty($keywords2)) {
        return 0;
    }
    
    $intersection = array_intersect($keywords1, $keywords2);
    $union = array_unique(array_merge($keywords1, $keywords2));
    
    if (empty($union)) {
        return 0;
    }
    
    // Jaccard similarity coefficient
    $jaccard = count($intersection) / count($union);
    
    return $jaccard * 100;
}

/**
 * Find and store matches for a newly created/updated post
 * @param int $postId The ID of the post to find matches for
 * @param string $postType The type of post ('lost' or 'found')
 * @return array Array of match results
 */
function findAndStoreMatches($postId, $postType) {
    global $conn;
    
    // Get the post details
    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND status = 'active'");
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    
    if (!$post) {
        return [];
    }
    
    // Find opposite type posts
    $oppositeType = ($postType === 'lost') ? 'found' : 'lost';
    $stmt = $conn->prepare("
        SELECT * FROM posts 
        WHERE type = ? AND status = 'active' AND id != ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY created_at DESC
    ");
    $stmt->bind_param('si', $oppositeType, $postId);
    $stmt->execute();
    $oppositePosts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $matchesFound = 0;
    
    foreach ($oppositePosts as $oppositePost) {
        $score = calculateMatchScore($post, $oppositePost);
        
        // Only store matches with 65%+ confidence
        if ($score >= 65) {
            // Check if match already exists
            $lostId = ($postType === 'lost') ? $postId : $oppositePost['id'];
            $foundId = ($postType === 'lost') ? $oppositePost['id'] : $postId;
            
            $checkStmt = $conn->prepare("
                SELECT id FROM post_matches 
                WHERE lost_post_id = ? AND found_post_id = ?
            ");
            $checkStmt->bind_param('ii', $lostId, $foundId);
            $checkStmt->execute();
            $existing = $checkStmt->get_result()->fetch_assoc();
            
            if (!$existing) {
                // Insert new match
                $insertStmt = $conn->prepare("
                    INSERT INTO post_matches (lost_post_id, found_post_id, match_score, status)
                    VALUES (?, ?, ?, 'pending')
                ");
                $insertStmt->bind_param('iid', $lostId, $foundId, $score);
                $insertStmt->execute();
                $matchesFound++;
                
                // Notify both users about the match
                notifyMatch($lostId, $foundId, $score);
            }
        }
    }
    
    return ['matches_found' => $matchesFound];
}

/**
 * Send notification to users about a match
 * @param int $lostPostId Lost post ID
 * @param int $foundPostId Found post ID  
 * @param float $score Match score
 */
function notifyMatch($lostPostId, $foundPostId, $score) {
    global $conn;
    
    // Get user IDs for both posts
    $stmt = $conn->prepare("
        SELECT user_id FROM posts WHERE id IN (?, ?)
    ");
    $stmt->bind_param('ii', $lostPostId, $foundPostId);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($users as $user) {
        $userId = $user['user_id'];
        
        // Store notification (you can extend this with a notifications table)
        // For now, we'll use session flash for demo purposes
        // In production, implement a proper notification system
        
        // Could also send email notifications here
        // sendMatchNotificationEmail($userId, $lostPostId, $foundPostId, $score);
    }
}

/**
 * Get matches for a specific user's posts
 * @param int $userId User ID
 * @return array Array of matches
 */
function getUserMatches($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT 
            pm.*,
            p1.title as lost_title,
            p1.image as lost_image,
            p1.category as lost_category,
            p1.location as lost_location,
            u1.name as lost_poster_name,
            p2.title as found_title,
            p2.image as found_image,
            p2.category as found_category,
            p2.location as found_location,
            u2.name as found_poster_name
        FROM post_matches pm
        JOIN posts p1 ON pm.lost_post_id = p1.id
        JOIN posts p2 ON pm.found_post_id = p2.id
        JOIN users u1 ON p1.user_id = u1.id
        JOIN users u2 ON p2.user_id = u2.id
        WHERE (p1.user_id = ? OR p2.user_id = ?)
        AND pm.status = 'pending'
        ORDER BY pm.match_score DESC, pm.created_at DESC
    ");
    $stmt->bind_param('ii', $userId, $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Update match status
 * @param int $matchId Match ID
 * @param string $status New status ('confirmed', 'dismissed')
 * @return bool Success status
 */
function updateMatchStatus($matchId, $status) {
    global $conn;
    
    if (!in_array($status, ['confirmed', 'dismissed'])) {
        return false;
    }
    
    $stmt = $conn->prepare("
        UPDATE post_matches SET status = ? WHERE id = ?
    ");
    $stmt->bind_param('si', $status, $matchId);
    return $stmt->execute();
}
