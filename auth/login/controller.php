<?php
// "Remember me" must be read and applied to the session cookie's
// lifetime *before* session_start() — $_POST is already populated by
// PHP at this point regardless, so this is safe to check first.
$mrRememberMe = !empty($_POST['remember_me']);
if ($mrRememberMe) {
    // 30 days. Unchecked leaves PHP's default (session cookie — expires
    // when the browser closes), same as before this feature existed.
    session_set_cookie_params(60 * 60 * 24 * 30);
}
session_start();
require_once __DIR__ . '/../../assets/connection/Connection.php';

function back_with_errors(array $errors, array $old): void
{
    $_SESSION['toast'] = ['type' => 'error', 'messages' => $errors];
    $_SESSION['login_old'] = $old;
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$old = [
    'email' => trim($_POST['email'] ?? ''),
];

$email    = $old['email'];
$password = $_POST['password'] ?? '';

$errors = [];

// 1. Validation
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if ($password === '') {
    $errors[] = 'Please enter your password.';
}

if (!empty($errors)) {
    back_with_errors($errors, $old);
}

// 2. Look up user by email
$emailLower = mb_strtolower($email);
$stmt = mysqli_prepare($con, 'SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 's', $emailLower);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// 3. Check credentials (plain text compare — entry-level project scope)
if (!$user || $password !== $user['password']) {
    back_with_errors(['Invalid email or password.'], $old);
}

// 4. Check account status
if ($user['status'] !== 'active') {
    back_with_errors(['Your account is not active. Please contact support.'], $old);
}

// 5. Success — start session and redirect to the right home for this role
$_SESSION['user_id'] = $user['id'];
$_SESSION['name']    = $user['name'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];

if ($user['role'] === 'doctor') {
    header('Location: ../../pages/doctor/home.php');
    exit;
}

if ($user['role'] === 'admin') {
    header('Location: ../../pages/admin/home.php');
    exit;
}

header('Location: ../../pages/user/home.php');
exit;
