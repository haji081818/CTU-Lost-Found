<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL);

$type          = in_array($_POST['type'] ?? '', ['lost','found']) ? $_POST['type'] : null;
$title         = trim($_POST['title']          ?? '');
$description   = trim($_POST['description']    ?? '');
$category      = trim($_POST['category']       ?? '');
$location      = trim($_POST['location']       ?? '');
$contactNumber = trim($_POST['contact_number'] ?? '');

if (!$type || !$title || !$description || !$category || !$location) {
    setFlash('error', 'Please fill in all required fields.');
    redirect(BASE_URL);
}

// Image upload
$imageName = null;
if (!empty($_FILES['image']['name'])) {
    $file = $_FILES['image'];
    if ($file['size'] > MAX_FILE_SIZE) {
        setFlash('error', 'Image must be under 5MB.');
        redirect(BASE_URL);
    }
    if (!in_array($file['type'], ALLOWED_TYPES)) {
        setFlash('error', 'Only JPG, PNG, GIF, or WEBP images are allowed.');
        redirect(BASE_URL);
    }
    $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
    $imageName = uniqid('item_', true) . '.' . strtolower($ext);
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $imageName)) {
        setFlash('error', 'Failed to upload image. Check folder permissions.');
        redirect(BASE_URL);
    }
}

$stmt = $conn->prepare("
    INSERT INTO posts (user_id, type, title, description, category, location, image, contact_number)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('isssssss', $_SESSION['user_id'], $type, $title, $description, $category, $location, $imageName, $contactNumber);

if ($stmt->execute()) {
    setFlash('success', 'Your item has been posted successfully!');
} else {
    setFlash('error', 'Something went wrong. Please try again.');
}

redirect(BASE_URL);