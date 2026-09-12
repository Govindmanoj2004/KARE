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
    'name'      => trim($_POST['name'] ?? ''),
    'email'     => trim($_POST['email'] ?? ''),
    'phone'     => trim($_POST['phone'] ?? ''),
    'terms'     => isset($_POST['terms']) ? '1' : '',
    'role'      => ($_POST['role'] ?? 'patient') === 'doctor' ? 'doctor' : 'patient',
    'specialty' => trim($_POST['specialty'] ?? ''),
];

$name            = $old['name'];
$email           = $old['email'];
$phone           = $old['phone'];
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$termsAccepted   = isset($_POST['terms']);
$role            = $old['role'];
$specialty       = $old['specialty'];

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

// Doctor accounts require a specialty (this is the only extra field the
// role toggle adds — see auth/signup/script.js for the show/hide + hidden
// #role input wiring).
if ($role === 'doctor' && ($specialty === '' || mb_strlen($specialty) < 2)) {
    $errors[] = 'Please enter your medical specialty.';
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

// 3. Insert new user (password stored as plain text — entry-level project scope)
// Doctors self-register unverified (is_verified = 0) — an admin must verify
// them (pages/admin/users/) before they appear in the patient-facing doctor
// directory (pages/user/doctors/doctors.php filters on is_verified = 1).
// Patients have no verification concept and stay at 1, as before.
$isVerified = $role === 'doctor' ? 0 : 1;
$specialtyValue = $role === 'doctor' ? $specialty : null;

$insertStmt = mysqli_prepare(
    $con,
    'INSERT INTO users (name, email, phone, password, role, specialty, status, is_verified)
     VALUES (?, ?, ?, ?, ?, ?, "active", ?)'
);
mysqli_stmt_bind_param(
    $insertStmt,
    'ssssssi',
    $name,
    $emailLower,
    $phone,
    $password,
    $role,
    $specialtyValue,
    $isVerified
);

if (mysqli_stmt_execute($insertStmt)) {
    mysqli_stmt_close($insertStmt);
    session_regenerate_id(true);
    $successMessage = $role === 'doctor'
        ? 'Account created. A staff member will verify your doctor profile before patients can find you — you can still log in now.'
        : 'Account created successfully. Please log in.';
    $_SESSION['toast'] = ['type' => 'success', 'messages' => [$successMessage]];
    header('Location: ../login/index.php');
    exit;
} else {
    mysqli_stmt_close($insertStmt);
    back_with_errors(['Something went wrong while creating your account. Please try again.'], $old);
}
