<?php
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';

// --- Auth guard -------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: report.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function back_with_toast(string $type, array $messages, array $old = []): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    if (!empty($old)) {
        $_SESSION['report_old'] = $old;
    }
    header('Location: report.php');
    exit;
}

$action = $_POST['action'] ?? '';

// =============================================================================
// ACTION: create_report — user files a new issue report
// =============================================================================
if ($action === 'create_report') {

    $old = [
        'subject' => trim($_POST['subject'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
    ];

    $subject = $old['subject'];
    $message = $old['message'];

    $errors = [];

    if ($subject === '' || mb_strlen($subject) < 3) {
        $errors[] = 'Please enter a short subject (at least 3 characters).';
    }

    if (mb_strlen($subject) > 150) {
        $errors[] = 'Subject must be under 150 characters.';
    }

    if ($message === '' || mb_strlen($message) < 10) {
        $errors[] = 'Please describe the issue in a bit more detail (at least 10 characters).';
    }

    if (!empty($errors)) {
        back_with_toast('error', $errors, $old);
    }

    $insertStmt = mysqli_prepare($con, 'INSERT INTO reports (user_id, subject, message, status) VALUES (?, ?, ?, ?)');
    $status = 'open';
    mysqli_stmt_bind_param($insertStmt, 'isss', $userId, $subject, $message, $status);

    if (mysqli_stmt_execute($insertStmt)) {
        mysqli_stmt_close($insertStmt);
        back_with_toast('success', ['Your report has been submitted. We\'ll get back to you soon.']);
    } else {
        mysqli_stmt_close($insertStmt);
        back_with_toast('error', ['Something went wrong while submitting your report. Please try again.'], $old);
    }
}

// Unknown action — just bounce back.
header('Location: report.php');
exit;
