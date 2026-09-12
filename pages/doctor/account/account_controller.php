<?php

/**
 * account_controller.php
 * -----------------------------------------------------------------------
 * Combines what the patient side splits across profile_controller.php
 * and settings_controller.php into one file for the doctor side:
 *   - update_profile        : name, phone, specialty
 *   - change_password        : same convention as the patient side
 *   - update_notifications    : notify_email / notify_sms
 *   - deactivate_account      : password-gated, signs out immediately
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

require_role('doctor', '../../../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account.php');
    exit;
}

$doctorId = (int) $_SESSION['user_id'];

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
        'name'             => trim($_POST['name'] ?? ''),
        'phone'            => trim($_POST['phone'] ?? ''),
        'specialty'        => trim($_POST['specialty'] ?? ''),
        'consultation_fee' => trim($_POST['consultation_fee'] ?? ''),
    ];

    $name = $old['name'];
    $phone = $old['phone'];
    $specialty = $old['specialty'] !== '' ? $old['specialty'] : null;

    $errors = [];
    if ($name === '' || mb_strlen($name) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if ($phone === '' || !preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number (7-15 digits, optional +country code).';
    }

    $consultationFee = null;
    if ($old['consultation_fee'] !== '') {
        if (!is_numeric($old['consultation_fee']) || (float) $old['consultation_fee'] < 0) {
            $errors[] = 'Please enter a valid consultation fee (0 or more), or leave it blank for free.';
        } else {
            $consultationFee = round((float) $old['consultation_fee'], 2);
        }
    }

    if (!empty($errors)) {
        back_with_toast('error', $errors, $old);
    }

    $updateStmt = mysqli_prepare($con, 'UPDATE users SET name = ?, phone = ?, specialty = ?, consultation_fee = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'sssdi', $name, $phone, $specialty, $consultationFee, $doctorId);

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
    mysqli_stmt_bind_param($stmt, 'i', $doctorId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row || $currentPassword !== $row['password']) {
        back_with_toast('error', ['Your current password is incorrect.']);
    }

    $updateStmt = mysqli_prepare($con, 'UPDATE users SET password = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'si', $newPassword, $doctorId);

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
    mysqli_stmt_bind_param($stmt, 'iii', $notifyEmail, $notifySms, $doctorId);

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
    mysqli_stmt_bind_param($stmt, 'i', $doctorId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row || $password === '' || $password !== $row['password']) {
        back_with_toast('error', ['Incorrect password. Your account was not deactivated.']);
    }

    $updateStmt = mysqli_prepare($con, "UPDATE users SET status = 'deactivated' WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, 'i', $doctorId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['toast'] = ['type' => 'success', 'messages' => ['Your account has been deactivated. Contact support to reactivate it.']];
    header('Location: ../../../auth/login/index.php');
    exit;
}

header('Location: account.php');
exit;
