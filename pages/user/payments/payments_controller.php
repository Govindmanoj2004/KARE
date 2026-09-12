<?php

/**
 * payments_controller.php
 * -----------------------------------------------------------------------
 * pay_and_connect: the "pay-to-connect" consultation-fee flow.
 *   1. Re-validates the doctor (active, verified, has a fee, not already
 *      connected) server-side -- never trust the checkout page alone.
 *   2. Writes a `payments` row with status='paid' immediately (simulated
 *      payment, no real gateway -- see db/12_consultation_payments.sql
 *      and README §7/§8).
 *   3. Creates the doctor_connections row (same effect as the free
 *      request_connection action in doctors_controller.php).
 *   4. Links payments.reference_id back to the new connection id.
 *   5. Notifies the doctor of both the payment and the request.
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
    header('Location: ../doctors/doctors.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function back_to_checkout_with_toast(string $type, array $messages, int $doctorId, array $old = []): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    if (!empty($old)) {
        $_SESSION['checkout_old'] = $old;
    }
    header('Location: checkout.php?doctor_id=' . $doctorId);
    exit;
}

function back_with_toast(string $type, array $messages, string $location = '../doctors/doctors.php'): void
{
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    header('Location: ' . $location);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================================================
// ACTION: pay_and_connect
// =============================================================================
if ($action === 'pay_and_connect') {

    $doctorId = (int) ($_POST['doctor_id'] ?? 0);
    $message  = trim($_POST['message'] ?? '');
    $message  = $message !== '' ? $message : null;

    $old = [
        'card_name' => trim($_POST['card_name'] ?? ''),
        'message'   => $message ?? '',
    ];

    if ($doctorId <= 0) {
        back_with_toast('error', ['Please choose a doctor.']);
    }

    // Dummy card validation -- shape-only, matches the checkout form's
    // constraints. No real card network is ever contacted (README §7/§8).
    $cardName   = trim($_POST['card_name'] ?? '');
    $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $cardExpiry = trim($_POST['card_expiry'] ?? '');
    $cardCvc    = trim($_POST['card_cvc'] ?? '');

    $errors = [];
    if ($cardName === '') {
        $errors[] = 'Please enter the name on the card.';
    }
    if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
        $errors[] = 'Please enter a valid card number.';
    }
    if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $cardExpiry)) {
        $errors[] = 'Please enter the expiry as MM/YY.';
    }
    if (!preg_match('/^\d{3,4}$/', $cardCvc)) {
        $errors[] = 'Please enter a valid CVC.';
    }

    if (!empty($errors)) {
        back_to_checkout_with_toast('error', $errors, $doctorId, $old);
    }

    // Re-fetch the doctor server-side -- never trust the fee shown on the
    // checkout page the browser posted from.
    $docStmt = mysqli_prepare($con, "
        SELECT id, name, consultation_fee
        FROM users
        WHERE id = ? AND role = 'doctor' AND status = 'active' AND is_verified = 1
        LIMIT 1
    ");
    mysqli_stmt_bind_param($docStmt, 'i', $doctorId);
    mysqli_stmt_execute($docStmt);
    $doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($docStmt));
    mysqli_stmt_close($docStmt);

    if (!$doctor || empty($doctor['consultation_fee'])) {
        back_with_toast('error', ['That doctor could not be found.']);
    }

    $amount = (float) $doctor['consultation_fee'];

    // The unique (patient_id, doctor_id) key means a duplicate INSERT will
    // fail -- check first so a double-submit doesn't charge twice.
    $existsStmt = mysqli_prepare($con, 'SELECT status FROM doctor_connections WHERE patient_id = ? AND doctor_id = ? LIMIT 1');
    mysqli_stmt_bind_param($existsStmt, 'ii', $userId, $doctorId);
    mysqli_stmt_execute($existsStmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($existsStmt));
    mysqli_stmt_close($existsStmt);

    if ($existing) {
        $label = $existing['status'] === 'accepted' ? 'already connected to' : 'already sent a request to';
        back_with_toast('error', ["You've " . $label . ' this doctor.']);
    }

    mysqli_begin_transaction($con);
    try {
        // 1. Write the payment as paid immediately -- simulated, no gateway.
        $payStmt = mysqli_prepare($con, "
            INSERT INTO payments (payer_id, payee_id, type, amount, status, method, paid_at)
            VALUES (?, ?, 'consultation', ?, 'paid', 'simulated', NOW())
        ");
        mysqli_stmt_bind_param($payStmt, 'iid', $userId, $doctorId, $amount);
        mysqli_stmt_execute($payStmt);
        $paymentId = mysqli_insert_id($con);
        mysqli_stmt_close($payStmt);

        // 2. Create the connection request, same as the free flow.
        $connStmt = mysqli_prepare($con, 'INSERT INTO doctor_connections (patient_id, doctor_id, message) VALUES (?, ?, ?)');
        mysqli_stmt_bind_param($connStmt, 'iis', $userId, $doctorId, $message);
        mysqli_stmt_execute($connStmt);
        $connectionId = mysqli_insert_id($con);
        mysqli_stmt_close($connStmt);

        // 3. Link the payment back to the connection it paid for.
        $linkStmt = mysqli_prepare($con, 'UPDATE payments SET reference_id = ? WHERE id = ?');
        mysqli_stmt_bind_param($linkStmt, 'ii', $connectionId, $paymentId);
        mysqli_stmt_execute($linkStmt);
        mysqli_stmt_close($linkStmt);

        mysqli_commit($con);
    } catch (Throwable $e) {
        mysqli_rollback($con);
        back_with_toast('error', ['Something went wrong while processing your payment. You have not been charged. Please try again.']);
    }

    $patientName = $_SESSION['name'] ?? 'A patient';
    create_notification(
        $con,
        $doctorId,
        'connection_request',
        $patientName . ' paid $' . number_format($amount, 2) . ' and sent you a connection request.',
        'pages/doctor/requests/requests.php'
    );

    $_SESSION['toast'] = ['type' => 'success', 'messages' => ['Payment received. Your connection request has been sent.']];
    header('Location: receipt.php?payment_id=' . $paymentId);
    exit;
}

header('Location: ../doctors/doctors.php');
exit;
