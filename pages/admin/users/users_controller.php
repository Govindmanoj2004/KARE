<?php

/**
 * users_controller.php
 * -----------------------------------------------------------------------
 * Handles:
 *   - update_status     : set a user's status (active/suspended/deactivated)
 *   - toggle_verified     : flip a doctor's is_verified flag
 *
 * An admin can act on any user except themselves for status changes
 * (so an admin can't accidentally lock themselves out) — self-service
 * account changes for the admin's own account go through
 * pages/admin/account/, not here.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

require_role('admin', '../../../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$adminId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    header('Location: users.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================================================
// ACTION: update_status
// =============================================================================
if ($action === 'update_status') {

    $targetId = (int) ($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($targetId <= 0 || !in_array($status, ['active', 'suspended', 'deactivated'], true)) {
        back_with_toast('error', ['Invalid request.']);
    }

    if ($targetId === $adminId) {
        back_with_toast('error', ["You can't change your own status here — use Account instead."]);
    }

    $updateStmt = mysqli_prepare($con, 'UPDATE users SET status = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'si', $status, $targetId);

    if (mysqli_stmt_execute($updateStmt) && mysqli_stmt_affected_rows($updateStmt) > 0) {
        mysqli_stmt_close($updateStmt);
        back_with_toast('success', ["User status updated to \"$status\"."]);
    } else {
        mysqli_stmt_close($updateStmt);
        back_with_toast('error', ['That user could not be found or was already in that state.']);
    }
}

// =============================================================================
// ACTION: toggle_verified (doctors only)
// =============================================================================
if ($action === 'toggle_verified') {

    $targetId = (int) ($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

    if ($targetId <= 0) {
        back_with_toast('error', ['Invalid request.']);
    }

    $findStmt = mysqli_prepare($con, "SELECT is_verified FROM users WHERE id = ? AND role = 'doctor' LIMIT 1");
    mysqli_stmt_bind_param($findStmt, 'i', $targetId);
    mysqli_stmt_execute($findStmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($findStmt));
    mysqli_stmt_close($findStmt);

    if (!$row) {
        back_with_toast('error', ['That doctor could not be found.']);
    }

    $newValue = $row['is_verified'] ? 0 : 1;
    $updateStmt = mysqli_prepare($con, 'UPDATE users SET is_verified = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'ii', $newValue, $targetId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    $label = $newValue ? 'Doctor verified.' : 'Doctor verification removed.';
    back_with_toast('success', [$label]);
}

header('Location: users.php');
exit;
