<?php

/**
 * shared/notifications/notifications_controller.php
 * -----------------------------------------------------------------------
 * AJAX endpoint (not a normal PRG controller -- called via fetch() from
 * notifications.js, so it responds with JSON rather than a redirect).
 *
 * Request: POST notifications_controller.php
 *   action=mark_read      + id=<notification id>   -> marks one as read
 *   action=mark_all_read                            -> marks all of the
 *                                                       logged-in user's as read
 *
 * Same ownership-check convention as every other controller in this app:
 * every UPDATE is scoped to user_id = the logged-in session user, never
 * trusting an id alone.
 * -----------------------------------------------------------------------
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

require_once __DIR__ . '/../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'mark_read') {
    $id = (int) ($_POST['id'] ?? 0);
    $stmt = mysqli_prepare($con, "
        UPDATE notifications SET read_at = NOW()
        WHERE id = ? AND user_id = ? AND read_at IS NULL
    ");
    mysqli_stmt_bind_param($stmt, 'ii', $id, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'mark_all_read') {
    $stmt = mysqli_prepare($con, "
        UPDATE notifications SET read_at = NOW()
        WHERE user_id = ? AND read_at IS NULL
    ");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false]);
