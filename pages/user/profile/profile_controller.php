<?php
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';

// --- Auth guard -------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages, array $old = []): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    if (!empty($old)) {
        $_SESSION['profile_old'] = $old;
    }
    header('Location: profile.php');
    exit;
}

$action = $_POST['action'] ?? '';

// =============================================================================
// ACTION: update_profile — name, email, phone
// =============================================================================
if ($action === 'update_profile') {

    $old = [
        'name'  => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
    ];

    $name  = $old['name'];
    $email = $old['email'];
    $phone = $old['phone'];

    $errors = [];

    if ($name === '' || mb_strlen($name) < 2) {
        $errors[] = 'Please enter your full name.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($phone === '' || !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number (7-15 digits, optional +country code).';
    }

    if (!empty($errors)) {
        back_with_toast('error', $errors, $old);
    }

    // Make sure no *other* user already owns this email.
    $emailLower = mb_strtolower($email);
    $checkStmt = mysqli_prepare($con, 'SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
    mysqli_stmt_bind_param($checkStmt, 'si', $emailLower, $userId);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);

    if (mysqli_stmt_num_rows($checkStmt) > 0) {
        mysqli_stmt_close($checkStmt);
        back_with_toast('error', ['Another account already uses this email.'], $old);
    }
    mysqli_stmt_close($checkStmt);

    $updateStmt = mysqli_prepare($con, 'UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'sssi', $name, $emailLower, $phone, $userId);

    if (mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);

        // Keep the session in sync with the new details.
        $_SESSION['name']  = $name;
        $_SESSION['email'] = $emailLower;

        back_with_toast('success', ['Your details have been updated.']);
    } else {
        mysqli_stmt_close($updateStmt);
        back_with_toast('error', ['Something went wrong while saving your details. Please try again.'], $old);
    }
}

// =============================================================================
// ACTION: change_password
// =============================================================================
if ($action === 'change_password') {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $errors = [];

    if ($currentPassword === '') {
        $errors[] = 'Please enter your current password.';
    }

    if (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New passwords do not match.';
    }

    if (!empty($errors)) {
        back_with_toast('error', $errors);
    }

    // Verify the current password (plain text compare — entry-level project scope)
    $stmt = mysqli_prepare($con, 'SELECT password FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row || $currentPassword !== $row['password']) {
        back_with_toast('error', ['Your current password is incorrect.']);
    }

    $updateStmt = mysqli_prepare($con, 'UPDATE users SET password = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'si', $newPassword, $userId);

    if (mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        back_with_toast('success', ['Your password has been updated.']);
    } else {
        mysqli_stmt_close($updateStmt);
        back_with_toast('error', ['Something went wrong while updating your password. Please try again.']);
    }
}

// Unknown action — just bounce back.
header('Location: profile.php');
exit;
