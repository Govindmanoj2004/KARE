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
require_once __DIR__ . '/../../../assets/helpers/notifications.php';

require_role('doctor', '../../../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: messages.php');
    exit;
}

$doctorId = (int) $_SESSION['user_id'];
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

    $ownStmt = mysqli_prepare($con, "
        SELECT id, patient_id FROM doctor_connections
        WHERE id = ? AND doctor_id = ? AND status = 'accepted'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($ownStmt, 'ii', $connectionId, $doctorId);
    mysqli_stmt_execute($ownStmt);
    $ownRow = mysqli_fetch_assoc(mysqli_stmt_get_result($ownStmt));
    if (!$ownRow) {
        mysqli_stmt_close($ownStmt);
        $_SESSION['toast'] = ['type' => 'error', 'messages' => ['That conversation could not be found.']];
        header('Location: messages.php');
        exit;
    }
    mysqli_stmt_close($ownStmt);
    $patientId = (int) $ownRow['patient_id'];

    $insertStmt = mysqli_prepare($con, 'INSERT INTO messages (connection_id, sender_id, body) VALUES (?, ?, ?)');
    mysqli_stmt_bind_param($insertStmt, 'iis', $connectionId, $doctorId, $body);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    $doctorName = $_SESSION['name'] ?? 'Your doctor';
    $preview = mb_strlen($body) > 60 ? mb_substr($body, 0, 60) . '…' : $body;
    create_notification(
        $con,
        $patientId,
        'message',
        $doctorName . ' sent you a message: "' . $preview . '"',
        'pages/user/messages/messages.php?connection_id=' . $connectionId
    );

    header('Location: ' . $redirectTo);
    exit;
}

header('Location: messages.php');
exit;
