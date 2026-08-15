<?php
require_once __DIR__ . '/../config/db.php';

/* ---------------------------
   CSRF VALIDATION
----------------------------*/
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(BASE_URL . 'profile.php');
}

/* ---------------------------
   ONLY POST REQUEST
----------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . 'profile.php');
}

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

    // Use secure file validation
    $validationError = validateUploadedFile($file);
    if ($validationError !== null) {
        setFlash('error', $validationError);
        redirect(BASE_URL . 'profile.php');
    }

    // Get current avatar to delete old one
    $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $oldAvatar = $user['avatar'] ?? null;

    $ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $avatarName = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $avatarName)) {
        setFlash('error', 'Failed to upload avatar.');
        redirect(BASE_URL . 'profile.php');
    }

    // Delete old avatar if it exists
    if ($oldAvatar && file_exists(UPLOAD_DIR . $oldAvatar)) {
        unlink(UPLOAD_DIR . $oldAvatar);
    }
}

if ($avatarName) {
    $stmt = $conn->prepare("UPDATE users SET name=?, student_id=?, phone=?, course=?, year_level=?, avatar=? WHERE id=?");
    if (!$stmt) {
        setFlash('error', 'Database error: ' . $conn->error);
        redirect(BASE_URL . 'profile.php');
    }
    $stmt->bind_param('ssssssi', $name, $studentId, $phone, $course, $yearLevel, $avatarName, $_SESSION['user_id']);
} else {
    $stmt = $conn->prepare("UPDATE users SET name=?, student_id=?, phone=?, course=?, year_level=? WHERE id=?");
    if (!$stmt) {
        setFlash('error', 'Database error: ' . $conn->error);
        redirect(BASE_URL . 'profile.php');
    }
    $stmt->bind_param('sssssi', $name, $studentId, $phone, $course, $yearLevel, $_SESSION['user_id']);
}

if ($stmt->execute()) {
    $_SESSION['user_name'] = $name;
    setFlash('success', 'Profile updated successfully!');
} else {
    setFlash('error', 'Failed to update profile.');
}

$stmt->close();
redirect(BASE_URL . 'profile.php');
