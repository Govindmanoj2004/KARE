<?php

/**
 * patients_controller.php
 * -----------------------------------------------------------------------
 * Handles: disconnect_patient — end an accepted connection from the
 * doctor's side (mirrors the patient's own "Disconnect" button in
 * pages/user/doctors/). Arrives via the shared confirm modal.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

require_role('doctor', '../../../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: patients.php');
    exit;
}

$doctorId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'disconnect_patient') {

    $connectionId = (int) ($_GET['connection_id'] ?? $_POST['connection_id'] ?? 0);

    if ($connectionId <= 0) {
        $_SESSION['toast'] = ['type' => 'error', 'messages' => ['Invalid request.']];
        header('Location: patients.php');
        exit;
    }

    $deleteStmt = mysqli_prepare($con, "DELETE FROM doctor_connections WHERE id = ? AND doctor_id = ? AND status = 'accepted'");
    mysqli_stmt_bind_param($deleteStmt, 'ii', $connectionId, $doctorId);
    mysqli_stmt_execute($deleteStmt);
    $affected = mysqli_stmt_affected_rows($deleteStmt);
    mysqli_stmt_close($deleteStmt);

    $_SESSION['toast'] = $affected > 0
        ? ['type' => 'success', 'messages' => ['Patient disconnected.']]
        : ['type' => 'error', 'messages' => ['That connection could not be found.']];

    header('Location: patients.php');
    exit;
}

header('Location: patients.php');
exit;
