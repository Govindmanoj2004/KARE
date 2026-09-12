<?php

/**
 * reports_controller.php
 * -----------------------------------------------------------------------
 * Handles: reply_report — writes an admin reply to a support ticket
 * (reports.admin_reply/replied_at, which existed unused since §9 of the
 * changelog) and updates its status. This is the admin-side half of the
 * "Report an Issue" flow the patient side (pages/user/report/) started.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';
require_once __DIR__ . '/../../../assets/helpers/notifications.php';

require_role('admin', '../../../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reports.php');
    exit;
}

function back_with_toast(string $type, array $messages, ?int $reportId = null): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    header('Location: reports.php' . ($reportId ? '?id=' . $reportId : ''));
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================================================
// ACTION: reply_report
// =============================================================================
if ($action === 'reply_report') {

    $reportId = (int) ($_POST['report_id'] ?? 0);
    $reply = trim($_POST['admin_reply'] ?? '');
    $status = $_POST['status'] ?? 'resolved';

    if ($reportId <= 0) {
        back_with_toast('error', ['Invalid report.']);
    }
    if ($reply === '' || mb_strlen($reply) < 2) {
        back_with_toast('error', ['Please write a reply before sending.'], $reportId);
    }
    if (!in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
        $status = 'resolved';
    }

    $findStmt = mysqli_prepare($con, '
        SELECT r.id, r.user_id, u.role
        FROM reports r
        JOIN users u ON u.id = r.user_id
        WHERE r.id = ?
        LIMIT 1
    ');
    mysqli_stmt_bind_param($findStmt, 'i', $reportId);
    mysqli_stmt_execute($findStmt);
    $reportRow = mysqli_fetch_assoc(mysqli_stmt_get_result($findStmt));
    if (!$reportRow) {
        mysqli_stmt_close($findStmt);
        back_with_toast('error', ['That report could not be found.']);
    }
    mysqli_stmt_close($findStmt);

    $updateStmt = mysqli_prepare($con, '
        UPDATE reports SET admin_reply = ?, replied_at = NOW(), status = ? WHERE id = ?
    ');
    mysqli_stmt_bind_param($updateStmt, 'ssi', $reply, $status, $reportId);

    if (mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        // Only the patient-facing "Report an Issue" page exists today
        // (README §6) -- link there; a future doctor/admin report page
        // could extend this by role.
        $reportLink = $reportRow['role'] === 'patient' ? 'pages/user/report/report.php' : null;
        create_notification(
            $con,
            (int) $reportRow['user_id'],
            'report_reply',
            'An admin replied to your support ticket.',
            $reportLink
        );
        back_with_toast('success', ['Reply sent.'], $reportId);
    } else {
        mysqli_stmt_close($updateStmt);
        back_with_toast('error', ['Something went wrong. Please try again.'], $reportId);
    }
}

// =============================================================================
// ACTION: update_status (change status without necessarily writing a new reply)
// =============================================================================
if ($action === 'update_status') {

    $reportId = (int) ($_POST['report_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($reportId <= 0 || !in_array($status, ['open', 'in_progress', 'resolved', 'closed'], true)) {
        back_with_toast('error', ['Invalid request.']);
    }

    $updateStmt = mysqli_prepare($con, 'UPDATE reports SET status = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'si', $status, $reportId);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);

    back_with_toast('success', ['Status updated.'], $reportId);
}

header('Location: reports.php');
exit;
