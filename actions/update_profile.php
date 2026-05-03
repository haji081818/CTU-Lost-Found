<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
requireLogin();

$name      = trim($_POST['name']       ?? '');
$studentId = trim($_POST['student_id'] ?? '');
$phone     = trim($_POST['phone']      ?? '');
$course    = trim($_POST['course']     ?? '');
$yearLevel = trim($_POST['year_level'] ?? '');

if (!$name) {
    setFlash('error', 'Name is required.');
    redirect(BASE_URL . 'profile.php');
}

// Avatar upload
$avatarName = null;
if (!empty($_FILES['avatar']['name'])) {
    $file = $_FILES['avatar'];
    if ($file['size'] > 2 * 1024 * 1024) {
        setFlash('error', 'Avatar must be under 2MB.');
        redirect(BASE_URL . 'profile.php');
    }
    if (!in_array($file['type'], ALLOWED_TYPES)) {
        setFlash('error', 'Only image files allowed.');
        redirect(BASE_URL . 'profile.php');
    }
    $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
    $avatarName = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . strtolower($ext);
    move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $avatarName);
}

if ($avatarName) {
    $stmt = $conn->prepare("UPDATE users SET name=?, student_id=?, phone=?, course=?, year_level=?, avatar=? WHERE id=?");
    $stmt->bind_param('ssssssi', $name, $studentId, $phone, $course, $yearLevel, $avatarName, $_SESSION['user_id']);
} else {
    $stmt = $conn->prepare("UPDATE users SET name=?, student_id=?, phone=?, course=?, year_level=? WHERE id=?");
    $stmt->bind_param('sssssi', $name, $studentId, $phone, $course, $yearLevel, $_SESSION['user_id']);
}

$stmt->execute();
$_SESSION['user_name'] = $name;
setFlash('success', 'Profile updated successfully!');
redirect(BASE_URL . 'profile.php');
