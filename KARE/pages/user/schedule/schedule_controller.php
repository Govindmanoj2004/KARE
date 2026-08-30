<?php

/**
 * schedule_controller.php
 * -----------------------------------------------------------------------
 * Handles all write actions for the Schedule page:
 *   - create_medicine  : add a new medicine + one or more daily times
 *   - update_medicine   : edit a medicine's name/dosage/notes/times
 *   - delete_medicine   : remove a medicine (cascades schedules + dose_logs)
 *   - mark_dose         : mark a today's-schedule dose as taken / missed
 *
 * Same PRG + toast pattern as profile_controller.php / report_controller.php.
 * The delete action is triggered via the shared confirm modal, whose hidden
 * form has no room for extra fields, so the medicine id travels in the
 * query string (?action=delete_medicine&medicine_id=5) alongside the
 * POST method — read from $_GET for that one case.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';

// --- Auth guard -------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedule.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages, array $old = []): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    if (!empty($old)) {
        $_SESSION['schedule_old'] = $old;
    }
    header('Location: schedule.php');
    exit;
}

// action can arrive via POST body (normal forms) or via the query string
// (the shared confirm-modal's hidden form only carries action + method).
$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * Parses the times[] array from the form into a clean, deduped list of
 * "HH:MM" 24-hour strings, discarding anything blank or malformed.
 */
function parse_times(array $rawTimes): array
{
    $clean = [];
    foreach ($rawTimes as $t) {
        $t = trim((string) $t);
        if ($t !== '' && preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $t)) {
            $clean[$t] = true; // dedupe via keys
        }
    }
    return array_keys($clean);
}

// =============================================================================
// ACTION: create_medicine
// =============================================================================
if ($action === 'create_medicine') {

    $old = [
        'name'   => trim($_POST['name'] ?? ''),
        'dosage' => trim($_POST['dosage'] ?? ''),
        'notes'  => trim($_POST['notes'] ?? ''),
    ];

    $name   = $old['name'];
    $dosage = $old['dosage'] !== '' ? $old['dosage'] : null;
    $notes  = $old['notes'] !== '' ? $old['notes'] : null;
    $times  = parse_times($_POST['times'] ?? []);

    $errors = [];

    if ($name === '' || mb_strlen($name) < 2) {
        $errors[] = 'Please enter the medicine name.';
    }
    if (mb_strlen($name) > 150) {
        $errors[] = 'Medicine name must be under 150 characters.';
    }
    if (empty($times)) {
        $errors[] = 'Please add at least one valid reminder time.';
    }

    if (!empty($errors)) {
        back_with_toast('error', $errors, $old);
    }

    mysqli_begin_transaction($con);
    try {
        $insertMed = mysqli_prepare($con, 'INSERT INTO medicines (user_id, name, dosage, notes) VALUES (?, ?, ?, ?)');
        mysqli_stmt_bind_param($insertMed, 'isss', $userId, $name, $dosage, $notes);
        mysqli_stmt_execute($insertMed);
        $medicineId = mysqli_insert_id($con);
        mysqli_stmt_close($insertMed);

        $insertTime = mysqli_prepare($con, 'INSERT INTO medicine_schedules (medicine_id, time_of_day) VALUES (?, ?)');
        foreach ($times as $t) {
            $timeWithSeconds = $t . ':00';
            mysqli_stmt_bind_param($insertTime, 'is', $medicineId, $timeWithSeconds);
            mysqli_stmt_execute($insertTime);
        }
        mysqli_stmt_close($insertTime);

        mysqli_commit($con);
        back_with_toast('success', ['Medicine added to your schedule.']);
    } catch (Throwable $e) {
        mysqli_rollback($con);
        back_with_toast('error', ['Something went wrong while saving the medicine. Please try again.'], $old);
    }
}

// =============================================================================
// ACTION: update_medicine
// =============================================================================
if ($action === 'update_medicine') {

    $medicineId = (int) ($_POST['medicine_id'] ?? 0);

    $old = [
        'medicine_id' => $medicineId,
        'name'        => trim($_POST['name'] ?? ''),
        'dosage'      => trim($_POST['dosage'] ?? ''),
        'notes'       => trim($_POST['notes'] ?? ''),
    ];

    $name   = $old['name'];
    $dosage = $old['dosage'] !== '' ? $old['dosage'] : null;
    $notes  = $old['notes'] !== '' ? $old['notes'] : null;
    $times  = parse_times($_POST['times'] ?? []);

    $errors = [];

    if ($medicineId <= 0) {
        $errors[] = 'Invalid medicine.';
    }
    if ($name === '' || mb_strlen($name) < 2) {
        $errors[] = 'Please enter the medicine name.';
    }
    if (empty($times)) {
        $errors[] = 'Please add at least one valid reminder time.';
    }

    if (!empty($errors)) {
        back_with_toast('error', $errors, $old);
    }

    // Ownership check — a user may only edit their own medicines.
    $ownStmt = mysqli_prepare($con, 'SELECT id FROM medicines WHERE id = ? AND user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($ownStmt, 'ii', $medicineId, $userId);
    mysqli_stmt_execute($ownStmt);
    mysqli_stmt_store_result($ownStmt);
    if (mysqli_stmt_num_rows($ownStmt) === 0) {
        mysqli_stmt_close($ownStmt);
        back_with_toast('error', ['That medicine could not be found.']);
    }
    mysqli_stmt_close($ownStmt);

    mysqli_begin_transaction($con);
    try {
        $updateMed = mysqli_prepare($con, 'UPDATE medicines SET name = ?, dosage = ?, notes = ? WHERE id = ? AND user_id = ?');
        mysqli_stmt_bind_param($updateMed, 'sssii', $name, $dosage, $notes, $medicineId, $userId);
        mysqli_stmt_execute($updateMed);
        mysqli_stmt_close($updateMed);

        // Simplest correct approach for an entry-level project: replace the
        // time list wholesale. Schedules cascade-delete their dose_logs, so
        // editing times clears history for the times that were removed —
        // acceptable tradeoff here, called out for anyone extending this.
        $deleteTimes = mysqli_prepare($con, 'DELETE FROM medicine_schedules WHERE medicine_id = ?');
        mysqli_stmt_bind_param($deleteTimes, 'i', $medicineId);
        mysqli_stmt_execute($deleteTimes);
        mysqli_stmt_close($deleteTimes);

        $insertTime = mysqli_prepare($con, 'INSERT INTO medicine_schedules (medicine_id, time_of_day) VALUES (?, ?)');
        foreach ($times as $t) {
            $timeWithSeconds = $t . ':00';
            mysqli_stmt_bind_param($insertTime, 'is', $medicineId, $timeWithSeconds);
            mysqli_stmt_execute($insertTime);
        }
        mysqli_stmt_close($insertTime);

        mysqli_commit($con);
        back_with_toast('success', ['Medicine updated.']);
    } catch (Throwable $e) {
        mysqli_rollback($con);
        back_with_toast('error', ['Something went wrong while updating the medicine. Please try again.'], $old);
    }
}

// =============================================================================
// ACTION: delete_medicine  (arrives via the shared confirm modal)
// =============================================================================
if ($action === 'delete_medicine') {

    $medicineId = (int) ($_GET['medicine_id'] ?? $_POST['medicine_id'] ?? 0);

    if ($medicineId <= 0) {
        back_with_toast('error', ['Invalid medicine.']);
    }

    $deleteStmt = mysqli_prepare($con, 'DELETE FROM medicines WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($deleteStmt, 'ii', $medicineId, $userId);
    mysqli_stmt_execute($deleteStmt);
    $affected = mysqli_stmt_affected_rows($deleteStmt);
    mysqli_stmt_close($deleteStmt);

    if ($affected > 0) {
        back_with_toast('success', ['Medicine removed from your schedule.']);
    } else {
        back_with_toast('error', ['That medicine could not be found.']);
    }
}

// =============================================================================
// ACTION: mark_dose — mark a today's-schedule dose as taken or missed
// =============================================================================
if ($action === 'mark_dose') {

    $doseLogId = (int) ($_POST['dose_log_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';

    if ($doseLogId <= 0 || !in_array($newStatus, ['taken', 'missed'], true)) {
        back_with_toast('error', ['Invalid request.']);
    }

    // Ownership check via the medicine -> schedule -> dose_log chain.
    $ownStmt = mysqli_prepare($con, "
        SELECT dl.id
        FROM dose_logs dl
        JOIN medicine_schedules ms ON ms.id = dl.schedule_id
        JOIN medicines m ON m.id = ms.medicine_id
        WHERE dl.id = ? AND m.user_id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($ownStmt, 'ii', $doseLogId, $userId);
    mysqli_stmt_execute($ownStmt);
    mysqli_stmt_store_result($ownStmt);
    if (mysqli_stmt_num_rows($ownStmt) === 0) {
        mysqli_stmt_close($ownStmt);
        back_with_toast('error', ['That dose could not be found.']);
    }
    mysqli_stmt_close($ownStmt);

    $takenAt = $newStatus === 'taken' ? date('Y-m-d H:i:s') : null;

    $updateStmt = mysqli_prepare($con, 'UPDATE dose_logs SET status = ?, taken_at = ? WHERE id = ?');
    mysqli_stmt_bind_param($updateStmt, 'ssi', $newStatus, $takenAt, $doseLogId);

    if (mysqli_stmt_execute($updateStmt)) {
        mysqli_stmt_close($updateStmt);
        $label = $newStatus === 'taken' ? 'Marked as taken.' : 'Marked as missed.';
        back_with_toast('success', [$label]);
    } else {
        mysqli_stmt_close($updateStmt);
        back_with_toast('error', ['Something went wrong. Please try again.']);
    }
}

// Unknown action — just bounce back.
header('Location: schedule.php');
exit;
