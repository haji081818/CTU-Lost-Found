<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/db.php';

/* ---------------------------
   HELPERS
----------------------------*/
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (empty($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('setFlash')) {
    function setFlash($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }
}

/* ---------------------------
   AUTH CHECK
----------------------------*/
requireLogin();

/* ---------------------------
   ONLY POST REQUEST
----------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL);
}

/* ---------------------------
   INPUTS
----------------------------*/
$type = (isset($_POST['type']) && in_array($_POST['type'], ['lost', 'found']))
    ? $_POST['type']
    : null;

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');
$location    = trim($_POST['location'] ?? '');
$contactNumber = trim($_POST['contact_number'] ?? '');

/* ---------------------------
   VALIDATION
----------------------------*/
if (!$type || !$title || !$description || !$category || !$contactNumber || !$location) {
    setFlash('error', 'Please fill in all required fields.');
    redirect(BASE_URL);
}

/* OPTIONAL: validate PH number */
if (!empty($contactNumber)) {
    $contactNumber = preg_replace('/[^0-9+]/', '', $contactNumber);

    if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $contactNumber)) {
        setFlash('error', 'Invalid contact number format.');
        redirect(BASE_URL);
    }
}

/* ---------------------------
   IMAGE UPLOAD
----------------------------*/
$imageName = null;

if (!empty($_FILES['image']['name'])) {

    $file = $_FILES['image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('error', 'Image upload failed.');
        redirect(BASE_URL);
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        setFlash('error', 'Image must be under 5MB.');
        redirect(BASE_URL);
    }

    if (!in_array($file['type'], ALLOWED_TYPES)) {
        setFlash('error', 'Only JPG, PNG, GIF, WEBP allowed.');
        redirect(BASE_URL);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $imageName = uniqid('item_', true) . '.' . $ext;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }

    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $imageName)) {
        setFlash('error', 'Failed to upload image.');
        redirect(BASE_URL);
    }
}

/* ---------------------------
   DATABASE INSERT
----------------------------*/
$stmt = $conn->prepare("
    INSERT INTO posts 
    (user_id, type, title, description, category, location, contact_number, image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    setFlash('error', 'Database error: ' . $conn->error);
    redirect(BASE_URL);
}

$userId = $_SESSION['user_id'];

$stmt->bind_param(
    'isssssss',
    $userId,
    $type,
    $title,
    $description,
    $category,
    $location,
    $contactNumber,
    $imageName
);

/* ---------------------------
   EXECUTE
----------------------------*/
if ($stmt->execute()) {
    setFlash('success', 'Your item has been posted successfully!');
} else {
    setFlash('error', 'Failed to post item.');
}

$stmt->close();

redirect(BASE_URL);