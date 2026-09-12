<?php

/**
 * account_controller.php
 * -----------------------------------------------------------------------
 * Same shape as pages/doctor/account/account_controller.php:
 *   - update_profile       : name, phone
 *   - change_password       : same convention as every other role
 *   - update_notifications   : notify_email / notify_sms
 *   - deactivate_account     : password-gated, signs out immediately
 *
 * One admin-specific safeguard: deactivation is blocked if this is the
 * last active admin account, since there's no other way back into the
 * admin portal once that happens.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

require_role('admin', '../../../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account.php');
    exit;
}

$adminId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages, array $old = []): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    if (!empty($old)) {
        $_SESSION['account_old'] = $old;
    }
    header('Location: account.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================================================
// ACTION: update_profile
// =============================================================================
if ($action === 'update_profile') {

    $old = [
        'name'  => trim($_POST['name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
    ];

    $name = $old['name'];
    $phone = $old['phone'];

    $errors = [];
    if ($name === '' || mb_strlen($name) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if ($phone === '' || !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number (7-15 digits, optional +country code).';
    }

    if (!empty($errors)) {
        back_with_toast('error', $errors, $old);
    }

    $updateStmt = mysqli_prepare($con, 'UPDATE users SET name = ?, phone = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'ssi', $name, $phone, $adminId);

    if (mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        $_SESSION['name'] = $name;
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

    $stmt = mysqli_prepare($con, 'SELECT password FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $adminId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row || $currentPassword !== $row['password']) {
        back_with_toast('error', ['Your current password is incorrect.']);
    }

    $updateStmt = mysqli_prepare($con, 'UPDATE users SET password = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'si', $newPassword, $adminId);

    if (mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        back_with_toast('success', ['Your password has been updated.']);
    } else {
        mysqli_stmt_close($updateStmt);
        back_with_toast('error', ['Something went wrong while updating your password. Please try again.']);
    }
}

// =============================================================================
// ACTION: update_notifications
// =============================================================================
if ($action === 'update_notifications') {

    $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
    $notifySms   = isset($_POST['notify_sms']) ? 1 : 0;

    $stmt = mysqli_prepare($con, 'UPDATE users SET notify_email = ?, notify_sms = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'iii', $notifyEmail, $notifySms, $adminId);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        back_with_toast('success', ['Notification preferences saved.']);
    } else {
        mysqli_stmt_close($stmt);
        back_with_toast('error', ['Something went wrong while saving your preferences. Please try again.']);
    }
}

// =============================================================================
// ACTION: deactivate_account
// =============================================================================
if ($action === 'deactivate_account') {

    $password = $_POST['confirm_password'] ?? '';

    $stmt = mysqli_prepare($con, 'SELECT password FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $adminId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row || $password === '' || $password !== $row['password']) {
        back_with_toast('error', ['Incorrect password. Your account was not deactivated.']);
    }

    // Safety check: refuse to deactivate the last active admin — there
    // would be no way back into the admin portal afterward.
    $countStmt = mysqli_query($con, "SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND status = 'active'");
    $activeAdmins = (int) (mysqli_fetch_assoc($countStmt)['c'] ?? 0);
    if ($activeAdmins <= 1) {
        back_with_toast('error', ["You're the only active admin — deactivating your account would lock everyone out of the admin portal. Create another admin account first."]);
    }

    $updateStmt = mysqli_prepare($con, "UPDATE users SET status = 'deactivated' WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, 'i', $adminId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['toast'] = ['type' => 'success', 'messages' => ['Your account has been deactivated.']];
    header('Location: ../../../auth/login/index.php');
    exit;
}

header('Location: account.php');
exit;
