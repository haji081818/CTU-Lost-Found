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
    setFlash('error', 'Invalid request method.');
    redirect(BASE_URL . 'profile.php');
}

requireLogin();

$current = $_POST['current_password']  ?? '';
$new     = $_POST['new_password']      ?? '';
$confirm = $_POST['confirm_password']  ?? '';

if (!$current || !$new || !$confirm) {
    setFlash('error', 'All fields are required.');
    redirect(BASE_URL . 'profile.php');
}
if ($new !== $confirm) {
    setFlash('error', 'New passwords do not match.');
    redirect(BASE_URL . 'profile.php');
}
if (strlen($new) < 6) {
    setFlash('error', 'Password must be at least 6 characters.');
    redirect(BASE_URL . 'profile.php');
}

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!password_verify($current, $user['password'])) {
    setFlash('error', 'Current password is incorrect.');
    redirect(BASE_URL . 'profile.php');
}

$hash = password_hash($new, PASSWORD_BCRYPT);
$upd  = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$upd->bind_param('si', $hash, $_SESSION['user_id']);
$upd->execute();

setFlash('success', 'Password changed successfully!');
redirect(BASE_URL . 'profile.php');
