<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

$token = sanitize($_GET['token'] ?? '');
if ($token === '') {
    setFlash('error', 'Invalid verification link.');
    redirect(SITE_URL . '/auth/login.php');
}
$email = mark_email_verified($token);
if ($email === false) {
    setFlash('error', 'Verification link is invalid or already used.');
    redirect(SITE_URL . '/auth/login.php');
}
$_SESSION['email_verified'] = true;
if (!isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = $email;
}
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = uniqid('u_');
}
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = in_array($email, ADMIN_EMAILS, true) ? 'admin' : 'user';
}
setFlash('success', 'Email verified successfully.');
$target = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ? '/admin/' : '/dashboard/';
redirect(SITE_URL . $target);
