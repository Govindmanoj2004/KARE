<?php

/**
 * pages/doctor/prescriptions/prescriptions_controller.php
 * -----------------------------------------------------------------------
 * Handles:
 *   - request_update   : doctor asks a connected patient for a prescription update
 *   - fulfill_request   : doctor uploads a new prescription answering a
 *                         patient's request, marks it as the patient's
 *                         current prescription, and closes the request
 *   - decline_request   : doctor declines a patient's request (via shared confirm modal)
 *
 * Ownership rule used throughout: every action re-checks that the
 * request's connection belongs to *this* doctor before touching it —
 * same pattern as every other controller in this project (README §5).
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';
require_once __DIR__ . '/../../../assets/helpers/notifications.php';

$mrRootBase = '../../../';
require_role('doctor', $mrRootBase);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: prescriptions.php');
    exit;
}

$doctorId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages, array $old = []): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    if (!empty($old)) {
        $_SESSION['prescription_request_old'] = $old;
    }
    header('Location: prescriptions.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================================================
// ACTION: request_update — doctor asks a connected patient for an update
// =============================================================================
if ($action === 'request_update') {

    $connectionId = (int) ($_POST['connection_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $old = ['message' => $message];

    if ($message === '' || mb_strlen($message) < 3) {
        back_with_toast('error', ['Please enter a message for the patient.'], $old);
    }

    // Ownership: the connection must belong to this doctor and be accepted.
    $checkStmt = mysqli_prepare($con, "SELECT id, patient_id FROM doctor_connections WHERE id = ? AND doctor_id = ? AND status = 'accepted' LIMIT 1");
    mysqli_stmt_bind_param($checkStmt, 'ii', $connectionId, $doctorId);
    mysqli_stmt_execute($checkStmt);
    $connRow = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
    if (!$connRow) {
        mysqli_stmt_close($checkStmt);
        back_with_toast('error', ['That patient connection could not be found.']);
    }
    mysqli_stmt_close($checkStmt);
    $askPatientId = (int) $connRow['patient_id'];

    $insertStmt = mysqli_prepare($con, "
        INSERT INTO prescription_requests (connection_id, requested_by, message)
        VALUES (?, 'doctor', ?)
    ");
    mysqli_stmt_bind_param($insertStmt, 'is', $connectionId, $message);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    $doctorName = $_SESSION['name'] ?? 'Your doctor';
    create_notification(
        $con,
        $askPatientId,
        'prescription_request',
        $doctorName . ' asked you to update your prescription.',
        'pages/user/prescriptions/prescriptions.php'
    );

    back_with_toast('success', ['Request sent to the patient.']);
}

// =============================================================================
// ACTION: fulfill_request — upload a new prescription, close out the request
// =============================================================================
if ($action === 'fulfill_request') {

    $requestId = (int) ($_POST['request_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $feeRaw = trim($_POST['fee_amount'] ?? '');
    $feeAmount = $feeRaw === '' ? null : (float) $feeRaw;

    $errors = [];
    if ($title === '' || mb_strlen($title) < 2) {
        $errors[] = 'Please give this prescription a short title.';
    }
    if ($feeAmount !== null && $feeAmount < 0) {
        $errors[] = 'Fee cannot be negative.';
    }

    // Ownership + fetch the patient this request belongs to.
    $reqStmt = mysqli_prepare($con, "
        SELECT pr.id, dc.patient_id
        FROM prescription_requests pr
        JOIN doctor_connections dc ON dc.id = pr.connection_id
        WHERE pr.id = ? AND dc.doctor_id = ? AND pr.status = 'pending' AND pr.requested_by = 'patient'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($reqStmt, 'ii', $requestId, $doctorId);
    mysqli_stmt_execute($reqStmt);
    $reqRow = mysqli_fetch_assoc(mysqli_stmt_get_result($reqStmt));
    mysqli_stmt_close($reqStmt);

    if (!$reqRow) {
        back_with_toast('error', ['That request could not be found or has already been handled.']);
    }
    $patientId = (int) $reqRow['patient_id'];

    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $maxBytes = 2 * 1024 * 1024;

    if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please choose a file to upload.';
    } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'The file could not be uploaded. Please try again.';
    } else {
        $file = $_FILES['file'];
        if ($file['size'] > $maxBytes) {
            $errors[] = 'File is too large — maximum size is 2MB.';
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            $errors[] = 'Only PDF, JPG, and PNG files are allowed.';
        }
    }

    if (!empty($errors)) {
        back_with_toast('error', $errors);
    }

    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;

    // Stored under the *patient's* upload folder — it's their prescription,
    // same as if they'd uploaded it themselves (README §7: random filename
    // is the only access mitigation, unchanged by who uploads).
    $userDir = __DIR__ . '/../../../assets/uploads/prescriptions/' . $patientId;
    if (!is_dir($userDir)) {
        mkdir($userDir, 0755, true);
    }
    $destination = $userDir . '/' . $storedName;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        back_with_toast('error', ['Something went wrong while saving the file. Please try again.']);
    }

    $relativePath = 'assets/uploads/prescriptions/' . $patientId . '/' . $storedName;
    $originalName = basename($_FILES['file']['name']);
    $doctorName = $_SESSION['name'] ?? 'Doctor';

    mysqli_begin_transaction($con);
    try {
        // Only one "current" prescription per patient at a time.
        $clearStmt = mysqli_prepare($con, 'UPDATE prescriptions SET is_current = 0 WHERE user_id = ? AND is_current = 1');
        mysqli_stmt_bind_param($clearStmt, 'i', $patientId);
        mysqli_stmt_execute($clearStmt);
        mysqli_stmt_close($clearStmt);

        $insertStmt = mysqli_prepare($con, "
            INSERT INTO prescriptions (user_id, issued_by, is_current, title, doctor_name, file_path, file_original_name)
            VALUES (?, 'doctor', 1, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($insertStmt, 'issss', $patientId, $title, $doctorName, $relativePath, $originalName);
        mysqli_stmt_execute($insertStmt);
        $newPrescriptionId = mysqli_insert_id($con);
        mysqli_stmt_close($insertStmt);

        $updateReqStmt = mysqli_prepare($con, "
            UPDATE prescription_requests
            SET status = 'fulfilled', fee_amount = ?, fulfilled_prescription_id = ?, responded_at = NOW()
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($updateReqStmt, 'dii', $feeAmount, $newPrescriptionId, $requestId);
        mysqli_stmt_execute($updateReqStmt);
        mysqli_stmt_close($updateReqStmt);

        mysqli_commit($con);
    } catch (Exception $e) {
        mysqli_rollback($con);
        @unlink($destination);
        back_with_toast('error', ['Something went wrong while saving the prescription. Please try again.']);
    }

    create_notification(
        $con,
        $patientId,
        'prescription_request',
        $doctorName . ' sent you an updated prescription.',
        'pages/user/prescriptions/prescriptions.php'
    );

    back_with_toast('success', ['Prescription sent to the patient.']);
}

// =============================================================================
// ACTION: decline_request (arrives via the shared confirm modal)
// =============================================================================
if ($action === 'decline_request') {

    $requestId = (int) ($_GET['request_id'] ?? $_POST['request_id'] ?? 0);

    $findPatStmt = mysqli_prepare($con, "
        SELECT dc.patient_id
        FROM prescription_requests pr
        JOIN doctor_connections dc ON dc.id = pr.connection_id
        WHERE pr.id = ? AND dc.doctor_id = ? AND pr.status = 'pending'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($findPatStmt, 'ii', $requestId, $doctorId);
    mysqli_stmt_execute($findPatStmt);
    $declineRow = mysqli_fetch_assoc(mysqli_stmt_get_result($findPatStmt));
    mysqli_stmt_close($findPatStmt);

    $updateStmt = mysqli_prepare($con, "
        UPDATE prescription_requests pr
        JOIN doctor_connections dc ON dc.id = pr.connection_id
        SET pr.status = 'declined', pr.responded_at = NOW()
        WHERE pr.id = ? AND dc.doctor_id = ? AND pr.status = 'pending'
    ");
    mysqli_stmt_bind_param($updateStmt, 'ii', $requestId, $doctorId);
    mysqli_stmt_execute($updateStmt);
    $affected = mysqli_stmt_affected_rows($updateStmt);
    mysqli_stmt_close($updateStmt);

    if ($affected > 0 && $declineRow) {
        $doctorName = $_SESSION['name'] ?? 'Your doctor';
        create_notification(
            $con,
            (int) $declineRow['patient_id'],
            'prescription_request',
            $doctorName . ' declined your prescription request.',
            'pages/user/prescriptions/prescriptions.php'
        );
        back_with_toast('success', ['Request declined.']);
    } else {
        back_with_toast('error', ['That request could not be found or has already been handled.']);
    }
}

header('Location: prescriptions.php');
exit;
