<?php

/**
 * messages_controller.php (doctor side)
 * -----------------------------------------------------------------------
 * Mirrors pages/user/messages/messages_controller.php, scoped to
 * doctor_id instead of patient_id.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

require_role('doctor', '../../../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$doctorId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'send_message') {

    $connectionId = (int) ($_POST['connection_id'] ?? 0);
    $body = trim($_POST['body'] ?? '');

    $redirectTo = 'messages.php?connection_id=' . $connectionId;

    if ($body === '') {
        header('Location: ' . $redirectTo);
        exit;
    }
    if (mb_strlen($body) > 2000) {
        $body = mb_substr($body, 0, 2000);
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
        $_SESSION['toast'] = ['type' => 'error', 'messages' => ['That conversation could not be found.']];
        header('Location: messages.php');
        exit;
    }
    mysqli_stmt_close($ownStmt);

    $insertStmt = mysqli_prepare($con, 'INSERT INTO messages (connection_id, sender_id, body) VALUES (?, ?, ?)');
    mysqli_stmt_bind_param($insertStmt, 'iis', $connectionId, $doctorId, $body);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    header('Location: ' . $redirectTo);
    exit;
}

header('Location: messages.php');
exit;
