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
require_once __DIR__ . '/../../../assets/helpers/notifications.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

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
        SELECT id, doctor_id FROM doctor_connections
        WHERE id = ? AND patient_id = ? AND status = 'accepted'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($ownStmt, 'ii', $connectionId, $userId);
    mysqli_stmt_execute($ownStmt);
    $ownRow = mysqli_fetch_assoc(mysqli_stmt_get_result($ownStmt));
    if (!$ownRow) {
        mysqli_stmt_close($ownStmt);
        $_SESSION['toast'] = ['type' => 'error', 'messages' => ['That conversation could not be found.']];
        header('Location: messages.php');
        exit;
    }
    mysqli_stmt_close($ownStmt);
    $doctorId = (int) $ownRow['doctor_id'];

    $insertStmt = mysqli_prepare($con, 'INSERT INTO messages (connection_id, sender_id, body) VALUES (?, ?, ?)');
    mysqli_stmt_bind_param($insertStmt, 'iis', $connectionId, $userId, $body);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    $patientName = $_SESSION['name'] ?? 'A patient';
    $preview = mb_strlen($body) > 60 ? mb_substr($body, 0, 60) . '…' : $body;
    create_notification(
        $con,
        $doctorId,
        'message',
        $patientName . ' sent you a message: "' . $preview . '"',
        'pages/doctor/messages/messages.php?connection_id=' . $connectionId
    );

    header('Location: ' . $redirectTo);
    exit;
}

header('Location: messages.php');
exit;
