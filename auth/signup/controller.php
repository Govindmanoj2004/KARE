<?php
session_start();
require_once __DIR__ . '/../../assets/connection/Connection.php';

function back_with_errors(array $errors, array $old): void
{
    $_SESSION['toast'] = ['type' => 'error', 'messages' => $errors];
    $_SESSION['signup_old'] = $old;
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$old = [
    'name'  => trim($_POST['name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'terms' => isset($_POST['terms']) ? '1' : '',
];

$name            = $old['name'];
$email           = $old['email'];
$phone           = $old['phone'];
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$termsAccepted   = isset($_POST['terms']);

$errors = [];

// 1. Validation
if ($name === '' || mb_strlen($name) < 2) {
    $errors[] = 'Please enter your full name.';
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if ($phone === '' || !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
    $errors[] = 'Please enter a valid phone number (7-15 digits, optional +country code).';
}

if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if (!$termsAccepted) {
    $errors[] = 'You must accept the Terms and Conditions.';
}

if (!empty($errors)) {
    back_with_errors($errors, $old);
}

// 2. Check for duplicate email
$emailLower = mb_strtolower($email);
$checkStmt = mysqli_prepare($con, 'SELECT id FROM users WHERE email = ? LIMIT 1');
mysqli_stmt_bind_param($checkStmt, 's', $emailLower);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) > 0) {
    mysqli_stmt_close($checkStmt);
    back_with_errors(['An account with this email already exists.'], $old);
}
mysqli_stmt_close($checkStmt);

// 3. Insert new patient user (password stored as plain text — entry-level project scope)
$role = 'patient';

$insertStmt = mysqli_prepare(
    $con,
    'INSERT INTO users (name, email, phone, password, role, status, is_verified)
     VALUES (?, ?, ?, ?, ?, "active", 1)'
);
mysqli_stmt_bind_param(
    $insertStmt,
    'sssss',
    $name,
    $emailLower,
    $phone,
    $password,
    $role
);

if (mysqli_stmt_execute($insertStmt)) {
    mysqli_stmt_close($insertStmt);
    session_regenerate_id(true);
    $_SESSION['toast'] = ['type' => 'success', 'messages' => ['Account created successfully. Please log in.']];
    header('Location: ../login/index.php');
    exit;
} else {
    mysqli_stmt_close($insertStmt);
    back_with_errors(['Something went wrong while creating your account. Please try again.'], $old);
}
