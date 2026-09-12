<?php

/**
 * prescriptions_controller.php
 * -----------------------------------------------------------------------
 * Handles:
 *   - upload_prescription : validate + store an uploaded file, insert a row
 *   - delete_prescription  : remove the DB row + the file on disk
 *
 * Uploaded files are stored outside any single request's control at
 * assets/uploads/prescriptions/{user_id}/{random}.{ext} — the random
 * filename means a guessed/sequential URL can't be used to find someone
 * else's file, and per-user subfolders keep things tidy.
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
    header('Location: prescriptions.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages, array $old = []): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    if (!empty($old)) {
        $_SESSION['prescription_old'] = $old;
    }
    header('Location: prescriptions.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================================================
// ACTION: upload_prescription
// =============================================================================
if ($action === 'upload_prescription') {

    $old = [
        'title'       => trim($_POST['title'] ?? ''),
        'doctor_name' => trim($_POST['doctor_name'] ?? ''),
        'notes'       => trim($_POST['notes'] ?? ''),
    ];

    $title      = $old['title'];
    $doctorName = $old['doctor_name'] !== '' ? $old['doctor_name'] : null;
    $notes      = $old['notes'] !== '' ? $old['notes'] : null;

    $errors = [];

    if ($title === '' || mb_strlen($title) < 2) {
        $errors[] = 'Please give this prescription a short title.';
    }

    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $maxBytes = 2 * 1024 * 1024; // 2MB — keep in sync with php.ini upload_max_filesize

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
        back_with_toast('error', $errors, $old);
    }

    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    $storedName = bin2hex(random_bytes(16)) . '.' . $ext;

    $userDir = __DIR__ . '/../../../assets/uploads/prescriptions/' . $userId;
    if (!is_dir($userDir)) {
        mkdir($userDir, 0755, true);
    }

    $destination = $userDir . '/' . $storedName;

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        back_with_toast('error', ['Something went wrong while saving the file. Please try again.'], $old);
    }

    // Path stored relative to the project root, for building a download link later.
    $relativePath = 'assets/uploads/prescriptions/' . $userId . '/' . $storedName;
    $originalName = basename($_FILES['file']['name']);

    // If this upload is fulfilling a doctor's "please update your
    // prescription" ask (to-do #14), it becomes the patient's current
    // prescription and closes out that request. Ownership check: the
    // request must belong to this patient, still be pending, and have
    // been raised by a doctor (not the patient's own outgoing request).
    $fulfillRequestId = (int) ($_POST['fulfill_request_id'] ?? 0);
    $fulfillsAsk = false;
    $fulfillDoctorId = 0;
    if ($fulfillRequestId > 0) {
        $askCheckStmt = mysqli_prepare($con, "
            SELECT pr.id, dc.doctor_id
            FROM prescription_requests pr
            JOIN doctor_connections dc ON dc.id = pr.connection_id
            WHERE pr.id = ? AND dc.patient_id = ? AND pr.status = 'pending' AND pr.requested_by = 'doctor'
            LIMIT 1
        ");
        mysqli_stmt_bind_param($askCheckStmt, 'ii', $fulfillRequestId, $userId);
        mysqli_stmt_execute($askCheckStmt);
        $askRow = mysqli_fetch_assoc(mysqli_stmt_get_result($askCheckStmt));
        $fulfillsAsk = $askRow !== null;
        if ($fulfillsAsk) {
            $fulfillDoctorId = (int) $askRow['doctor_id'];
        }
        mysqli_stmt_close($askCheckStmt);
    }

    mysqli_begin_transaction($con);
    try {
        if ($fulfillsAsk) {
            $clearStmt = mysqli_prepare($con, 'UPDATE prescriptions SET is_current = 0 WHERE user_id = ? AND is_current = 1');
            mysqli_stmt_bind_param($clearStmt, 'i', $userId);
            mysqli_stmt_execute($clearStmt);
            mysqli_stmt_close($clearStmt);
        }

        $isCurrent = $fulfillsAsk ? 1 : 0;
        $insertStmt = mysqli_prepare($con, '
            INSERT INTO prescriptions (user_id, is_current, title, doctor_name, notes, file_path, file_original_name)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        mysqli_stmt_bind_param($insertStmt, 'iisssss', $userId, $isCurrent, $title, $doctorName, $notes, $relativePath, $originalName);
        mysqli_stmt_execute($insertStmt);
        $newPrescriptionId = mysqli_insert_id($con);
        mysqli_stmt_close($insertStmt);

        if ($fulfillsAsk) {
            $closeReqStmt = mysqli_prepare($con, "
                UPDATE prescription_requests
                SET status = 'fulfilled', fulfilled_prescription_id = ?, responded_at = NOW()
                WHERE id = ?
            ");
            mysqli_stmt_bind_param($closeReqStmt, 'ii', $newPrescriptionId, $fulfillRequestId);
            mysqli_stmt_execute($closeReqStmt);
            mysqli_stmt_close($closeReqStmt);
        }

        mysqli_commit($con);
    } catch (Exception $e) {
        mysqli_rollback($con);
        @unlink($destination);
        back_with_toast('error', ['Something went wrong while saving the prescription. Please try again.'], $old);
    }

    if ($fulfillsAsk && $fulfillDoctorId > 0) {
        $patientName = $_SESSION['name'] ?? 'Your patient';
        create_notification(
            $con,
            $fulfillDoctorId,
            'prescription_request',
            $patientName . ' uploaded an updated prescription for your request.',
            'pages/doctor/prescriptions/prescriptions.php'
        );
    }

    back_with_toast('success', [$fulfillsAsk ? 'Prescription updated and sent to your doctor.' : 'Prescription uploaded.']);
}

// =============================================================================
// ACTION: delete_prescription  (arrives via the shared confirm modal)
// =============================================================================
if ($action === 'delete_prescription') {

    $prescriptionId = (int) ($_GET['prescription_id'] ?? $_POST['prescription_id'] ?? 0);

    if ($prescriptionId <= 0) {
        back_with_toast('error', ['Invalid prescription.']);
    }

    // Fetch the file path first so we can remove it from disk after the DB row is gone.
    $findStmt = mysqli_prepare($con, 'SELECT file_path FROM prescriptions WHERE id = ? AND user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($findStmt, 'ii', $prescriptionId, $userId);
    mysqli_stmt_execute($findStmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($findStmt));
    mysqli_stmt_close($findStmt);

    if (!$row) {
        back_with_toast('error', ['That prescription could not be found.']);
    }

    $deleteStmt = mysqli_prepare($con, 'DELETE FROM prescriptions WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($deleteStmt, 'ii', $prescriptionId, $userId);
    mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);

    $filePath = __DIR__ . '/../../../' . $row['file_path'];
    if (is_file($filePath)) {
        @unlink($filePath);
    }

    back_with_toast('success', ['Prescription removed.']);
}

// =============================================================================
// ACTION: request_update — patient asks a connected doctor for an update
// =============================================================================
if ($action === 'request_update') {

    $connectionId = (int) ($_POST['connection_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($message === '' || mb_strlen($message) < 3) {
        $_SESSION['toast'] = ['type' => 'error', 'messages' => ['Please describe what you need from your doctor.']];
        $_SESSION['prescription_request_old'] = ['message' => $message];
        header('Location: prescriptions.php');
        exit;
    }

    // Ownership: the connection must belong to this patient and be accepted.
    $checkStmt = mysqli_prepare($con, "SELECT id, doctor_id FROM doctor_connections WHERE id = ? AND patient_id = ? AND status = 'accepted' LIMIT 1");
    mysqli_stmt_bind_param($checkStmt, 'ii', $connectionId, $userId);
    mysqli_stmt_execute($checkStmt);
    $connRow = mysqli_fetch_assoc(mysqli_stmt_get_result($checkStmt));
    if (!$connRow) {
        mysqli_stmt_close($checkStmt);
        back_with_toast('error', ['That doctor connection could not be found.']);
    }
    mysqli_stmt_close($checkStmt);
    $reqDoctorId = (int) $connRow['doctor_id'];

    $insertStmt = mysqli_prepare($con, "
        INSERT INTO prescription_requests (connection_id, requested_by, message)
        VALUES (?, 'patient', ?)
    ");
    mysqli_stmt_bind_param($insertStmt, 'is', $connectionId, $message);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);

    $patientName = $_SESSION['name'] ?? 'A patient';
    create_notification(
        $con,
        $reqDoctorId,
        'prescription_request',
        $patientName . ' requested a prescription update.',
        'pages/doctor/prescriptions/prescriptions.php'
    );

    back_with_toast('success', ['Request sent to your doctor.']);
}

// =============================================================================
// ACTION: pay_fee — simulated payment (no real gateway; see README §7/§8
// for this project's documented no-external-integrations scope)
// =============================================================================
if ($action === 'pay_fee') {

    $requestId = (int) ($_POST['request_id'] ?? 0);

    $findDocStmt = mysqli_prepare($con, "
        SELECT dc.doctor_id
        FROM prescription_requests pr
        JOIN doctor_connections dc ON dc.id = pr.connection_id
        WHERE pr.id = ? AND dc.patient_id = ? AND pr.fee_amount IS NOT NULL
        LIMIT 1
    ");
    mysqli_stmt_bind_param($findDocStmt, 'ii', $requestId, $userId);
    mysqli_stmt_execute($findDocStmt);
    $feeRow = mysqli_fetch_assoc(mysqli_stmt_get_result($findDocStmt));
    mysqli_stmt_close($findDocStmt);

    $updateStmt = mysqli_prepare($con, "
        UPDATE prescription_requests pr
        JOIN doctor_connections dc ON dc.id = pr.connection_id
        SET pr.fee_paid = 1
        WHERE pr.id = ? AND dc.patient_id = ? AND pr.fee_amount IS NOT NULL
    ");
    mysqli_stmt_bind_param($updateStmt, 'ii', $requestId, $userId);
    mysqli_stmt_execute($updateStmt);
    $affected = mysqli_stmt_affected_rows($updateStmt);
    mysqli_stmt_close($updateStmt);

    if ($affected > 0 && $feeRow) {
        $patientName = $_SESSION['name'] ?? 'Your patient';
        create_notification(
            $con,
            (int) $feeRow['doctor_id'],
            'prescription_request',
            $patientName . ' marked the prescription fee as paid.',
            'pages/doctor/prescriptions/prescriptions.php'
        );
        back_with_toast('success', ['Payment recorded. Thank you.']);
    } else {
        back_with_toast('error', ['That request could not be found.']);
    }
}

// =============================================================================
// ACTION: decline_ask — patient dismisses a doctor's incoming update request
// (arrives via the shared confirm modal)
// =============================================================================
if ($action === 'decline_ask') {

    $requestId = (int) ($_GET['request_id'] ?? $_POST['request_id'] ?? 0);

    $findDocStmt = mysqli_prepare($con, "
        SELECT dc.doctor_id
        FROM prescription_requests pr
        JOIN doctor_connections dc ON dc.id = pr.connection_id
        WHERE pr.id = ? AND dc.patient_id = ? AND pr.status = 'pending' AND pr.requested_by = 'doctor'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($findDocStmt, 'ii', $requestId, $userId);
    mysqli_stmt_execute($findDocStmt);
    $declineRow = mysqli_fetch_assoc(mysqli_stmt_get_result($findDocStmt));
    mysqli_stmt_close($findDocStmt);

    $updateStmt = mysqli_prepare($con, "
        UPDATE prescription_requests pr
        JOIN doctor_connections dc ON dc.id = pr.connection_id
        SET pr.status = 'declined', pr.responded_at = NOW()
        WHERE pr.id = ? AND dc.patient_id = ? AND pr.status = 'pending' AND pr.requested_by = 'doctor'
    ");
    mysqli_stmt_bind_param($updateStmt, 'ii', $requestId, $userId);
    mysqli_stmt_execute($updateStmt);
    $affected = mysqli_stmt_affected_rows($updateStmt);
    mysqli_stmt_close($updateStmt);

    if ($affected > 0 && $declineRow) {
        $patientName = $_SESSION['name'] ?? 'Your patient';
        create_notification(
            $con,
            (int) $declineRow['doctor_id'],
            'prescription_request',
            $patientName . ' dismissed your prescription-update request.',
            'pages/doctor/prescriptions/prescriptions.php'
        );
        back_with_toast('success', ['Request dismissed.']);
    } else {
        back_with_toast('error', ['That request could not be found or has already been handled.']);
    }
}

header('Location: prescriptions.php');
exit;
