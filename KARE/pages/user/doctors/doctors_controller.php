<?php

/**
 * doctors_controller.php
 * -----------------------------------------------------------------------
 * Handles the patient side of the connection lifecycle:
 *   - request_connection : send a connection request to a doctor
 *   - cancel_connection    : withdraw a still-pending request, or
 *                             disconnect from an accepted one
 *
 * The doctor's side of this (accepting/declining requests) would live in
 * a doctor-facing area that doesn't exist yet — out of scope for "the
 * user side" of the app. This only builds the patient-initiated half of
 * the lifecycle described in CHANGELOG.md's "what's next" list.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: doctors.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    header('Location: doctors.php');
    exit;
}

$action = $_POST['action'] ?? '';

// =============================================================================
// ACTION: request_connection
// =============================================================================
if ($action === 'request_connection') {

    $doctorId = (int) ($_POST['doctor_id'] ?? 0);
    $message  = trim($_POST['message'] ?? '');
    $message  = $message !== '' ? $message : null;

    if ($doctorId <= 0) {
        back_with_toast('error', ['Please choose a doctor.']);
    }

    // Make sure the target is actually an active doctor.
    $docStmt = mysqli_prepare($con, "SELECT id FROM users WHERE id = ? AND role = 'doctor' AND status = 'active' LIMIT 1");
    mysqli_stmt_bind_param($docStmt, 'i', $doctorId);
    mysqli_stmt_execute($docStmt);
    mysqli_stmt_store_result($docStmt);
    if (mysqli_stmt_num_rows($docStmt) === 0) {
        mysqli_stmt_close($docStmt);
        back_with_toast('error', ['That doctor could not be found.']);
    }
    mysqli_stmt_close($docStmt);

    // The unique (patient_id, doctor_id) key means a duplicate INSERT will fail —
    // check first so we can give a friendly message instead of a raw DB error.
    $existsStmt = mysqli_prepare($con, 'SELECT status FROM doctor_connections WHERE patient_id = ? AND doctor_id = ? LIMIT 1');
    mysqli_stmt_bind_param($existsStmt, 'ii', $userId, $doctorId);
    mysqli_stmt_execute($existsStmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($existsStmt));
    mysqli_stmt_close($existsStmt);

    if ($existing) {
        $label = $existing['status'] === 'accepted' ? 'already connected to' : 'already sent a request to';
        back_with_toast('error', ["You've " . $label . ' this doctor.']);
    }

    $insertStmt = mysqli_prepare($con, 'INSERT INTO doctor_connections (patient_id, doctor_id, message) VALUES (?, ?, ?)');
    mysqli_stmt_bind_param($insertStmt, 'iis', $userId, $doctorId, $message);

    if (mysqli_stmt_execute($insertStmt)) {
        mysqli_stmt_close($insertStmt);
        back_with_toast('success', ['Connection request sent.']);
    } else {
        mysqli_stmt_close($insertStmt);
        back_with_toast('error', ['Something went wrong while sending the request. Please try again.']);
    }
}

// =============================================================================
// ACTION: cancel_connection (arrives via the shared confirm modal)
// =============================================================================
if ($action === 'cancel_connection') {

    $connectionId = (int) ($_GET['connection_id'] ?? $_POST['connection_id'] ?? 0);

    if ($connectionId <= 0) {
        back_with_toast('error', ['Invalid request.']);
    }

    $deleteStmt = mysqli_prepare($con, 'DELETE FROM doctor_connections WHERE id = ? AND patient_id = ?');
    mysqli_stmt_bind_param($deleteStmt, 'ii', $connectionId, $userId);
    mysqli_stmt_execute($deleteStmt);
    $affected = mysqli_stmt_affected_rows($deleteStmt);
    mysqli_stmt_close($deleteStmt);

    if ($affected > 0) {
        back_with_toast('success', ['Connection removed.']);
    } else {
        back_with_toast('error', ['That connection could not be found.']);
    }
}

header('Location: doctors.php');
exit;
