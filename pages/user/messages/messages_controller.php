<?php

/**
 * messages_controller.php
 * -----------------------------------------------------------------------
 * Handles: send_message — post a chat message into an accepted
 * doctor_connections thread. Ownership is re-checked here too (not just
 * on the page) since this is a separate POST endpoint.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
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

    // Ownership check — the connection must belong to this patient and be accepted.
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
        $_SESSION['toast'] = ['type' => 'error', 'messages' => ['That conversation could not be found.']];
        header('Location: messages.php');
        exit;
    }
    mysqli_stmt_close($ownStmt);

    $insertStmt = mysqli_prepare($con, 'INSERT INTO messages (connection_id, sender_id, body) VALUES (?, ?, ?)');
    mysqli_stmt_bind_param($insertStmt, 'iis', $connectionId, $userId, $body);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    header('Location: ' . $redirectTo);
    exit;
}

header('Location: messages.php');
exit;
