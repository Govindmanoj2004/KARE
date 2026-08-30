<?php

/**
 * messages_poll.php (doctor side)
 * -----------------------------------------------------------------------
 * Mirrors pages/user/messages/messages_poll.php, scoped to doctor_id.
 * -----------------------------------------------------------------------
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'doctor') {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

$doctorId = (int) $_SESSION['user_id'];
$connectionId = isset($_GET['connection_id']) ? (int) $_GET['connection_id'] : 0;
$afterId = isset($_GET['after_id']) ? (int) $_GET['after_id'] : 0;

if ($connectionId <= 0) {
    echo json_encode([]);
    exit;
}

$ownStmt = mysqli_prepare($con, "
    SELECT id FROM doctor_connections
    WHERE id = ? AND doctor_id = ? AND status = 'accepted'
    LIMIT 1
");
mysqli_stmt_bind_param($ownStmt, 'ii', $connectionId, $doctorId);
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
        'is_mine'    => (int) $row['sender_id'] === $doctorId,
        'body'       => $row['body'],
        'created_at' => date('g:i A', strtotime($row['created_at'])),
    ];
}
mysqli_stmt_close($msgStmt);

echo json_encode($messages);
