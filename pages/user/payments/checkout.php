<?php

/**
 * checkout.php
 * -----------------------------------------------------------------------
 * "Pay & Connect" checkout, reached from pages/user/doctors/doctors.php
 * when the target doctor has a consultation_fee set. Collects an optional
 * note (same as the free-connect dialog) plus a dummy card form, then
 * posts to payments_controller.php's pay_and_connect action.
 *
 * Simulated payment, same spirit as prescriptions' existing pay_fee
 * action (README §7/§8) -- no real gateway, no card processing. The card
 * fields exist to make the flow feel real; nothing about their contents
 * is validated against a payment network.
 * -----------------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];
$mrRootBase = '../../../';
$activePage = 'doctors';

$doctorId = (int) ($_GET['doctor_id'] ?? 0);

$stmt = mysqli_prepare($con, "
    SELECT id, name, specialty, consultation_fee
    FROM users
    WHERE id = ? AND role = 'doctor' AND status = 'active' AND is_verified = 1
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'i', $doctorId);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$doctor || empty($doctor['consultation_fee'])) {
    // Not a real paid doctor -- nothing to check out for.
    header('Location: ../doctors/doctors.php');
    exit;
}

// Already connected/pending? Send them back rather than letting them pay twice.
$existsStmt = mysqli_prepare($con, 'SELECT status FROM doctor_connections WHERE patient_id = ? AND doctor_id = ? LIMIT 1');
mysqli_stmt_bind_param($existsStmt, 'ii', $userId, $doctorId);
mysqli_stmt_execute($existsStmt);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($existsStmt));
mysqli_stmt_close($existsStmt);

if ($existing) {
    $_SESSION['toast'] = ['type' => 'error', 'messages' => ["You've already " . ($existing['status'] === 'accepted' ? 'connected with' : 'sent a request to') . ' this doctor.']];
    header('Location: ../doctors/doctors.php');
    exit;
}

$formOld = $_SESSION['checkout_old'] ?? null;
unset($_SESSION['checkout_old']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare — pay a consultation fee and connect with your doctor.">
    <title>Checkout · Kare</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="payments.css">

    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../../shared/toast/toast.php'; ?>
    <?php include __DIR__ . '/../../../shared/modal/modal.php'; ?>

    <div class="mr-app-shell" data-mr-app-shell>

        <?php include __DIR__ . '/../../../shared/user/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../../shared/user/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">Pay &amp; <strong>Connect</strong></div>
                            <div class="mr-layout-header-sub">This doctor charges a consultation fee. Pay it once to send your connection request.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card mr-checkout-grid">

                    <!-- Order summary -->
                    <div class="mr-checkout-summary" data-mr-scroll-entry style="--index: 0">
                        <div class="mr-card-heading">Summary</div>
                        <div class="mr-doctor-card" style="border:none; padding: 12px 0;">
                            <div class="mr-profile-avatar mr-doctor-avatar">
                                <?= htmlspecialchars(strtoupper(substr($doctor['name'], 0, 1))) ?>
                            </div>
                            <div class="mr-doctor-main">
                                <div class="mr-medicine-card-name"><?= htmlspecialchars($doctor['name']) ?></div>
                                <?php if (!empty($doctor['specialty'])): ?>
                                    <div class="mr-field-hint"><?= htmlspecialchars($doctor['specialty']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mr-divider"></div>
                        <div class="mr-checkout-line">
                            <span>Consultation fee</span>
                            <span>$<?= number_format((float) $doctor['consultation_fee'], 2) ?></span>
                        </div>
                        <div class="mr-checkout-line mr-checkout-total">
                            <span>Total due today</span>
                            <span>$<?= number_format((float) $doctor['consultation_fee'], 2) ?></span>
                        </div>
                        <p class="mr-field-hint" style="margin-top:12px;">
                            <i class="ph ph-shield-check"></i>
                            Simulated payment for demo purposes — no real card is charged.
                        </p>
                    </div>

                    <!-- Payment form -->
                    <form action="payments_controller.php" method="post" class="mr-form mr-checkout-form" data-mr-scroll-entry style="--index: 1">
                        <input type="hidden" name="action" value="pay_and_connect">
                        <input type="hidden" name="doctor_id" value="<?= (int) $doctor['id'] ?>">

                        <div class="mr-card-heading">Card details</div>

                        <div class="mr-form-grid">
                            <div class="mr-field is-full">
                                <label class="mr-label" for="card_name">Name on card</label>
                                <input class="mr-input" type="text" id="card_name" name="card_name" placeholder="Jane Doe" value="<?= htmlspecialchars($formOld['card_name'] ?? ($_SESSION['name'] ?? '')) ?>" required>
                            </div>
                            <div class="mr-field is-full">
                                <label class="mr-label" for="card_number">Card number</label>
                                <input class="mr-input" type="text" id="card_number" name="card_number" placeholder="4242 4242 4242 4242" inputmode="numeric" maxlength="19" required>
                            </div>
                            <div class="mr-field">
                                <label class="mr-label" for="card_expiry">Expiry</label>
                                <input class="mr-input" type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" maxlength="5" required>
                            </div>
                            <div class="mr-field">
                                <label class="mr-label" for="card_cvc">CVC</label>
                                <input class="mr-input" type="text" id="card_cvc" name="card_cvc" placeholder="123" inputmode="numeric" maxlength="4" required>
                            </div>
                            <div class="mr-field is-full">
                                <label class="mr-label" for="message">Note to the doctor (optional)</label>
                                <textarea class="mr-input mr-textarea" id="message" name="message" rows="3" placeholder="e.g. I'd like you to review my medication schedule."><?= htmlspecialchars($formOld['message'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="mr-form-actions">
                            <a href="../doctors/doctors.php" class="mr-btn-secondary">Cancel</a>
                            <button type="submit" class="mr-btn">
                                <i class="ph ph-lock-key"></i> Pay $<?= number_format((float) $doctor['consultation_fee'], 2) ?> &amp; send request
                            </button>
                        </div>
                    </form>

                </section>

            </main>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="../../../shared/notifications/notifications.js"></script>
    <script src="payments.js"></script>
</body>

</html>
