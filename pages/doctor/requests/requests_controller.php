<?php

/**
 * requests_controller.php
 * -----------------------------------------------------------------------
 * Handles: respond_request — accept or decline a pending connection
 * request. This is the doctor-side half of the lifecycle the patient
 * side (pages/user/doctors/) only started.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';
require_once __DIR__ . '/../../../assets/helpers/notifications.php';

require_role('doctor', '../../../');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: requests.php');
    exit;
}

$doctorId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    header('Location: requests.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'respond_request') {

    $connectionId = (int) ($_POST['connection_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';

    if ($connectionId <= 0 || !in_array($decision, ['accepted', 'declined'], true)) {
        back_with_toast('error', ['Invalid request.']);
    }

    // Ownership check — the request must be addressed to this doctor and still pending.
    $ownStmt = mysqli_prepare($con, "
        SELECT id, patient_id FROM doctor_connections
        WHERE id = ? AND doctor_id = ? AND status = 'pending'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($ownStmt, 'ii', $connectionId, $doctorId);
    mysqli_stmt_execute($ownStmt);
    $ownRow = mysqli_fetch_assoc(mysqli_stmt_get_result($ownStmt));
    if (!$ownRow) {
        mysqli_stmt_close($ownStmt);
        back_with_toast('error', ['That request could not be found.']);
    }
    mysqli_stmt_close($ownStmt);
    $patientId = (int) $ownRow['patient_id'];

    $updateStmt = mysqli_prepare($con, 'UPDATE doctor_connections SET status = ?, responded_at = NOW() WHERE id = ? AND doctor_id = ?');
    mysqli_stmt_bind_param($updateStmt, 'sii', $decision, $connectionId, $doctorId);

    if (mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        $doctorName = $_SESSION['name'] ?? 'A doctor';
        $notifBody = $decision === 'accepted'
            ? $doctorName . ' accepted your connection request.'
            : $doctorName . ' declined your connection request.';
        create_notification($con, $patientId, 'connection_response', $notifBody, 'pages/user/doctors/doctors.php');
        $label = $decision === 'accepted' ? 'Request accepted.' : 'Request declined.';
        back_with_toast('success', [$label]);
    } else {
        mysqli_stmt_close($updateStmt);
        back_with_toast('error', ['Something went wrong. Please try again.']);
    }
}

header('Location: requests.php');
exit;
