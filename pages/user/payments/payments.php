<?php

/**
 * payments.php
 * -----------------------------------------------------------------------
 * Patient's own payment history -- every consultation fee they've paid,
 * newest first, each linking to its receipt. Scoped to payer_id (§5
 * ownership pattern) -- a patient can never see another patient's rows.
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

$payments = [];
$stmt = mysqli_prepare($con, "
    SELECT p.id, p.amount, p.type, p.status, p.paid_at, p.created_at,
           doc.name AS doctor_name, doc.specialty AS doctor_specialty
    FROM payments p
    JOIN users doc ON doc.id = p.payee_id
    WHERE p.payer_id = ?
    ORDER BY p.created_at DESC
");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $payments[] = $row;
}
mysqli_stmt_close($stmt);

$totalPaid = 0.0;
foreach ($payments as $p) {
    if ($p['status'] === 'paid') {
        $totalPaid += (float) $p['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare — your payment history.">
    <title>Payments · Kare</title>

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
                            <div class="mr-layout-header-heading">Payment <strong>History</strong></div>
                            <div class="mr-layout-header-sub">Consultation fees you've paid to your doctors.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <div class="mr-stat-grid" data-mr-scroll-entry style="--index: 0">
                        <div class="mr-stat-card">
                            <div class="mr-field-hint">Total paid</div>
                            <div class="mr-medicine-card-name" style="font-size: 22px;">$<?= number_format($totalPaid, 2) ?></div>
                        </div>
                        <div class="mr-stat-card">
                            <div class="mr-field-hint">Payments made</div>
                            <div class="mr-medicine-card-name" style="font-size: 22px;"><?= count($payments) ?></div>
                        </div>
                    </div>

                    <div class="mr-divider"></div>

                    <?php if (empty($payments)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 1">
                            <i class="ph ph-receipt"></i>
                            <p>No payments yet. Connecting with a paid doctor will show up here.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-table-wrap" data-mr-scroll-entry style="--index: 1">
                            <table class="mr-table">
                                <thead>
                                    <tr>
                                        <th>Doctor</th>
                                        <th>For</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($p['doctor_name']) ?>
                                                <?php if (!empty($p['doctor_specialty'])): ?>
                                                    <div class="mr-field-hint"><?= htmlspecialchars($p['doctor_specialty']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-transform: capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $p['type'])) ?></td>
                                            <td>$<?= number_format((float) $p['amount'], 2) ?></td>
                                            <td>
                                                <?php if ($p['status'] === 'paid'): ?>
                                                    <span class="mr-status is-taken">Paid</span>
                                                <?php elseif ($p['status'] === 'pending'): ?>
                                                    <span class="mr-status is-upcoming">Pending</span>
                                                <?php else: ?>
                                                    <span class="mr-status is-missed">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars(date('d M Y', strtotime($p['paid_at'] ?? $p['created_at']))) ?></td>
                                            <td>
                                                <a class="mr-btn-secondary" href="receipt.php?payment_id=<?= (int) $p['id'] ?>">
                                                    <i class="ph ph-receipt"></i> Receipt
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

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
