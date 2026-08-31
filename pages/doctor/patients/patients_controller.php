<?php

/**
 * patients_controller.php
 * -----------------------------------------------------------------------
 * Handles:
 *   - disconnect_patient : end an accepted connection from the doctor's
 *     side (mirrors the patient's own "Disconnect" button in
 *     pages/user/doctors/). Arrives via the shared confirm modal.
 *   - save_note           : upsert this doctor's private note on a
 *     patient (doctor_patient_notes) — never visible to the patient.
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

if ($action === 'save_note') {

    $patientId = (int) ($_POST['patient_id'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($patientId <= 0) {
        $_SESSION['toast'] = ['type' => 'error', 'messages' => ['Invalid request.']];
        header('Location: patients.php');
        exit;
    }

    // Ownership check — the doctor must actually be connected (accepted) to this patient.
    $ownStmt = mysqli_prepare($con, "SELECT id FROM doctor_connections WHERE doctor_id = ? AND patient_id = ? AND status = 'accepted' LIMIT 1");
    mysqli_stmt_bind_param($ownStmt, 'ii', $doctorId, $patientId);
    mysqli_stmt_execute($ownStmt);
    mysqli_stmt_store_result($ownStmt);
    if (mysqli_stmt_num_rows($ownStmt) === 0) {
        mysqli_stmt_close($ownStmt);
        $_SESSION['toast'] = ['type' => 'error', 'messages' => ['That patient could not be found.']];
        header('Location: patients.php');
        exit;
    }
    mysqli_stmt_close($ownStmt);

    if ($note === '') {
        // Empty note: remove the row entirely rather than storing a blank one.
        $deleteStmt = mysqli_prepare($con, 'DELETE FROM doctor_patient_notes WHERE doctor_id = ? AND patient_id = ?');
        mysqli_stmt_bind_param($deleteStmt, 'ii', $doctorId, $patientId);
        mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);
        $_SESSION['toast'] = ['type' => 'success', 'messages' => ['Note cleared.']];
        header('Location: patients.php');
        exit;
    }

    // Upsert: one note per (doctor, patient) pair — see the UNIQUE key on doctor_patient_notes.
    $upsertStmt = mysqli_prepare($con, '
        INSERT INTO doctor_patient_notes (doctor_id, patient_id, note)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE note = VALUES(note), updated_at = NOW()
    ');
    mysqli_stmt_bind_param($upsertStmt, 'iis', $doctorId, $patientId, $note);
    mysqli_stmt_execute($upsertStmt);
    mysqli_stmt_close($upsertStmt);

    $_SESSION['toast'] = ['type' => 'success', 'messages' => ['Note saved.']];
    header('Location: patients.php');
    exit;
}

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
