<?php
/* admin/actions/update_user.php */
require_once __DIR__ . '/../../config/db.php';

/* ---------------------------
   CSRF VALIDATION
----------------------------*/
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(BASE_URL . 'admin/users.php');
}

/* ---------------------------
   ONLY POST REQUEST
----------------------------*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method.');
    redirect(BASE_URL . 'admin/users.php');
}

if (!isLoggedIn() || empty($_SESSION['is_admin'])) redirect(BASE_URL . 'admin/login.php');

$id          = (int)($_POST['id']           ?? 0);
$name        = trim($_POST['name']          ?? '');
$email       = trim($_POST['email']         ?? '');
$studentId   = trim($_POST['student_id']    ?? '');
$phone       = trim($_POST['phone']         ?? '');
$course      = trim($_POST['course']        ?? '');
$yearLevel   = trim($_POST['year_level']    ?? '');
$newPassword =      $_POST['new_password']  ?? '';

if (!$id || !$name || !$email) {
    setFlash('error', 'Name and email are required.');
    redirect(BASE_URL . "admin/edit_user.php?id=$id");
}

// Check email unique
$chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
$chk->bind_param('si', $email, $id);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    setFlash('error', 'That email is already used by another account.');
    redirect(BASE_URL . "admin/edit_user.php?id=$id");
}

if ($newPassword) {
    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET name=?,email=?,student_id=?,phone=?,course=?,year_level=?,password=? WHERE id=?");
    if (!$stmt) {
        setFlash('error', 'Database error: ' . $conn->error);
        redirect(BASE_URL . "admin/edit_user.php?id=$id");
    }
    $stmt->bind_param('sssssssi', $name,$email,$studentId,$phone,$course,$yearLevel,$hash,$id);
} else {
    $stmt = $conn->prepare("UPDATE users SET name=?,email=?,student_id=?,phone=?,course=?,year_level=? WHERE id=?");
    if (!$stmt) {
        setFlash('error', 'Database error: ' . $conn->error);
        redirect(BASE_URL . "admin/edit_user.php?id=$id");
    }
    $stmt->bind_param('ssssssi', $name,$email,$studentId,$phone,$course,$yearLevel,$id);
}

if ($stmt->execute()) {
    setFlash('success', 'User updated successfully.');
} else {
    setFlash('error', 'Failed to update user.');
}

$stmt->close();
redirect(BASE_URL . "admin/view_user.php?id=$id");
