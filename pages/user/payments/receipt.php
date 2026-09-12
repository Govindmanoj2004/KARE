<?php

/**
 * receipt.php
 * -----------------------------------------------------------------------
 * Printable receipt for one payments row. Ownership-checked to the
 * logged-in patient (payer_id = session user), same pattern as every
 * other read in this app (README §5). "Download" is just the browser's
 * print-to-PDF, consistent with this project's no-build-step approach --
 * no server-side PDF library is introduced for one receipt view.
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
$activePage = 'payments';

$paymentId = (int) ($_GET['payment_id'] ?? 0);

$stmt = mysqli_prepare($con, "
    SELECT p.id, p.amount, p.type, p.status, p.method, p.paid_at, p.created_at,
           doc.name AS doctor_name, doc.specialty AS doctor_specialty,
           pat.name AS patient_name
    FROM payments p
    JOIN users doc ON doc.id = p.payee_id
    JOIN users pat ON pat.id = p.payer_id
    WHERE p.id = ? AND p.payer_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'ii', $paymentId, $userId);
mysqli_stmt_execute($stmt);
$payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$payment) {
    header('Location: payments.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare — payment receipt.">
    <title>Receipt #<?= (int) $payment['id'] ?> · Kare</title>

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
                            <div class="mr-layout-header-heading">Payment <strong>Receipt</strong></div>
                            <div class="mr-layout-header-sub">Receipt #<?= (int) $payment['id'] ?></div>
                        </div>
                        <button type="button" class="mr-btn-secondary" id="mr-print-receipt">
                            <i class="ph ph-printer"></i> Print / Save as PDF
                        </button>
                    </div>
                </section>

                <section class="mr-layout-card mr-receipt" id="mr-receipt-card" data-mr-scroll-entry style="--index: 0">

                    <div class="mr-receipt-head">
                        <div>
                            <div class="mr-sidebar-brand-name" style="font-size: 22px;">Kare</div>
                            <div class="mr-field-hint">Payment receipt</div>
                        </div>
                        <span class="mr-badge is-success" style="text-transform: capitalize;"><?= htmlspecialchars($payment['status']) ?></span>
                    </div>

                    <div class="mr-divider"></div>

                    <div class="mr-receipt-grid">
                        <div>
                            <div class="mr-field-hint">Receipt No.</div>
                            <div class="mr-medicine-card-name">#<?= (int) $payment['id'] ?></div>
                        </div>
                        <div>
                            <div class="mr-field-hint">Date</div>
                            <div class="mr-medicine-card-name"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($payment['paid_at'] ?? $payment['created_at']))) ?></div>
                        </div>
                        <div>
                            <div class="mr-field-hint">Paid by</div>
                            <div class="mr-medicine-card-name"><?= htmlspecialchars($payment['patient_name']) ?></div>
                        </div>
                        <div>
                            <div class="mr-field-hint">Paid to</div>
                            <div class="mr-medicine-card-name">
                                <?= htmlspecialchars($payment['doctor_name']) ?>
                                <?php if (!empty($payment['doctor_specialty'])): ?>
                                    <span class="mr-field-hint">(<?= htmlspecialchars($payment['doctor_specialty']) ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <div class="mr-field-hint">For</div>
                            <div class="mr-medicine-card-name" style="text-transform: capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $payment['type'])) ?> fee</div>
                        </div>
                        <div>
                            <div class="mr-field-hint">Method</div>
                            <div class="mr-medicine-card-name" style="text-transform: capitalize;"><?= htmlspecialchars($payment['method']) ?> (demo)</div>
                        </div>
                    </div>

                    <div class="mr-divider"></div>

                    <div class="mr-checkout-line mr-checkout-total">
                        <span>Total paid</span>
                        <span>$<?= number_format((float) $payment['amount'], 2) ?></span>
                    </div>

                    <p class="mr-field-hint" style="margin-top: 20px;">
                        <i class="ph ph-shield-check"></i>
                        This is a simulated payment generated for demo purposes — no real transaction occurred.
                    </p>

                </section>

                <div data-mr-scroll-entry style="--index: 1">
                    <a href="payments.php" class="mr-btn-secondary"><i class="ph ph-arrow-left"></i> Back to payment history</a>
                </div>

            </main>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="../../../shared/notifications/notifications.js"></script>
    <script src="payments.js"></script>
</body>

</html>
