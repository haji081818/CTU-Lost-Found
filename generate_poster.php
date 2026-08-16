<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/poster_generator.php';

$postId = (int)($_GET['id'] ?? 0);
$format = $_GET['format'] ?? 'html';

if (!$postId) {
    die('Invalid post ID.');
}

// Check if post exists
$stmt = $conn->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->bind_param('i', $postId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    die('Post not found.');
}

generateAndServePoster($postId, $format);
