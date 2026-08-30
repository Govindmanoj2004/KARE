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

$action = $_POST['action'] ?? '';

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

    $insertStmt = mysqli_prepare($con, '
        INSERT INTO prescriptions (user_id, title, doctor_name, notes, file_path, file_original_name)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $originalName = basename($_FILES['file']['name']);
    mysqli_stmt_bind_param($insertStmt, 'isssss', $userId, $title, $doctorName, $notes, $relativePath, $originalName);

    if (mysqli_stmt_execute($insertStmt)) {
        mysqli_stmt_close($insertStmt);
        back_with_toast('success', ['Prescription uploaded.']);
    } else {
        mysqli_stmt_close($insertStmt);
        @unlink($destination); // clean up the orphaned file if the DB insert failed
        back_with_toast('error', ['Something went wrong while saving the prescription. Please try again.'], $old);
    }
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

header('Location: prescriptions.php');
exit;
