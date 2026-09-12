<?php

/**
 * shared/notifications/notifications_fetch.php
 * -----------------------------------------------------------------------
 * AJAX endpoint used by notifications.js to populate the navbar bell
 * dropdown. Shared across all three portals (patient/doctor/admin) since
 * it's scoped entirely by $_SESSION['user_id'] -- no role check needed,
 * same as the pattern used for session-scoped reads elsewhere in the app.
 *
 * Request:  GET notifications_fetch.php
 * Response: JSON { unread_count: n, notifications: [ {id, type, body, link,
 *                  is_read, created_at}, ... ] }  (most recent 20)
 * -----------------------------------------------------------------------
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['unread_count' => 0, 'notifications' => []]);
    exit;
}

require_once __DIR__ . '/../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];

$countStmt = mysqli_prepare($con, 'SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND read_at IS NULL');
mysqli_stmt_bind_param($countStmt, 'i', $userId);
mysqli_stmt_execute($countStmt);
$unreadCount = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['c'] ?? 0);
mysqli_stmt_close($countStmt);

$listStmt = mysqli_prepare($con, '
    SELECT id, type, body, link, read_at, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
');
mysqli_stmt_bind_param($listStmt, 'i', $userId);
mysqli_stmt_execute($listStmt);
$result = mysqli_stmt_get_result($listStmt);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = [
        'id'         => (int) $row['id'],
        'type'       => $row['type'],
        'body'       => $row['body'],
        'link'       => $row['link'],
        'is_read'    => $row['read_at'] !== null,
        'created_at' => date('d M, g:i A', strtotime($row['created_at'])),
    ];
}
mysqli_stmt_close($listStmt);

echo json_encode(['unread_count' => $unreadCount, 'notifications' => $notifications]);
