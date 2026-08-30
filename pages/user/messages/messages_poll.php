<?php

/**
 * messages_poll.php
 * -----------------------------------------------------------------------
 * AJAX endpoint used by messages.js to poll for new messages in a
 * conversation without a full page reload.
 *
 * Request:  GET messages_poll.php?connection_id=1&after_id=12
 * Response: JSON array of messages with id > after_id, oldest first.
 * -----------------------------------------------------------------------
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];
$connectionId = isset($_GET['connection_id']) ? (int) $_GET['connection_id'] : 0;
$afterId = isset($_GET['after_id']) ? (int) $_GET['after_id'] : 0;

if ($connectionId <= 0) {
    echo json_encode([]);
    exit;
}

// Ownership check — only the patient on this accepted connection may poll it.
$ownStmt = mysqli_prepare($con, "
    SELECT id FROM doctor_connections
    WHERE id = ? AND patient_id = ? AND status = 'accepted'
    LIMIT 1
");
mysqli_stmt_bind_param($ownStmt, 'ii', $connectionId, $userId);
mysqli_stmt_execute($ownStmt);
mysqli_stmt_store_result($ownStmt);
if (mysqli_stmt_num_rows($ownStmt) === 0) {
    mysqli_stmt_close($ownStmt);
    http_response_code(403);
    echo json_encode([]);
    exit;
}
mysqli_stmt_close($ownStmt);

$msgStmt = mysqli_prepare($con, '
    SELECT id, sender_id, body, created_at
    FROM messages
    WHERE connection_id = ? AND id > ?
    ORDER BY id ASC
');
mysqli_stmt_bind_param($msgStmt, 'ii', $connectionId, $afterId);
mysqli_stmt_execute($msgStmt);
$result = mysqli_stmt_get_result($msgStmt);

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = [
        'id'         => (int) $row['id'],
        'is_mine'    => (int) $row['sender_id'] === $userId,
        'body'       => $row['body'],
        'created_at' => date('g:i A', strtotime($row['created_at'])),
    ];
}
mysqli_stmt_close($msgStmt);

echo json_encode($messages);
