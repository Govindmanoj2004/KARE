<?php

/**
 * settings_controller.php
 * -----------------------------------------------------------------------
 * Handles:
 *   - update_notifications : toggle email / SMS reminder preferences
 *   - deactivate_account   : flips status to 'deactivated' (same
 *                             confirm-modal pattern as logout) and signs
 *                             the user out immediately
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    header('Location: settings.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================================================
// ACTION: update_notifications
// =============================================================================
if ($action === 'update_notifications') {

    $notifyEmail = isset($_POST['notify_email']) ? 1 : 0;
    $notifySms   = isset($_POST['notify_sms']) ? 1 : 0;

    $stmt = mysqli_prepare($con, 'UPDATE users SET notify_email = ?, notify_sms = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'iii', $notifyEmail, $notifySms, $userId);

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

    // Re-verify the password before doing something this destructive
    // (same plain-text convention as login/change_password).
    $stmt = mysqli_prepare($con, 'SELECT password FROM users WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row || $password === '' || $password !== $row['password']) {
        back_with_toast('error', ['Incorrect password. Your account was not deactivated.']);
    }

    $updateStmt = mysqli_prepare($con, "UPDATE users SET status = 'deactivated' WHERE id = ?");
    mysqli_stmt_bind_param($updateStmt, 'i', $userId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    // Sign the user out immediately — a deactivated account shouldn't
    // stay logged in (login/controller.php already blocks non-active
    // accounts from signing back in).
    $_SESSION = [];
    session_destroy();
    session_start();
    $_SESSION['toast'] = ['type' => 'success', 'messages' => ['Your account has been deactivated. Contact support to reactivate it.']];
    header('Location: ../../../auth/login/index.php');
    exit;
}

header('Location: settings.php');
exit;
