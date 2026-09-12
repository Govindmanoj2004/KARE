<?php

/**
 * payments.php (doctor side)
 * -----------------------------------------------------------------------
 * "Earnings" -- every consultation-fee payment this doctor has received,
 * newest first. Read-only (a doctor doesn't act on a payment; the patient
 * pays once, up front, in pages/user/payments/checkout.php). Scoped to
 * payee_id, same ownership pattern as every other list in this app (§5).
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

$mrRootBase = '../../../';
require_role('doctor', $mrRootBase);

$activePage = 'payments';
$doctorId = (int) $_SESSION['user_id'];

$currentUser = [
    'name'  => $_SESSION['name']  ?? 'Doctor',
    'email' => $_SESSION['email'] ?? '',
    'avatar' => null,
];

$payments = [];
$stmt = mysqli_prepare($con, "
    SELECT p.id, p.amount, p.type, p.status, p.paid_at, p.created_at,
           pat.name AS patient_name, pat.email AS patient_email
    FROM payments p
    JOIN users pat ON pat.id = p.payer_id
    WHERE p.payee_id = ?
    ORDER BY p.created_at DESC
");
mysqli_stmt_bind_param($stmt, 'i', $doctorId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $payments[] = $row;
}
mysqli_stmt_close($stmt);

$totalEarned = 0.0;
foreach ($payments as $p) {
    if ($p['status'] === 'paid') {
        $totalEarned += (float) $p['amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare for Doctors — your earnings.">
    <title>Payments · Kare for Doctors</title>

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

        <?php include __DIR__ . '/../../../shared/doctor/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../../shared/doctor/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">Your <strong>Earnings</strong></div>
                            <div class="mr-layout-header-sub">Consultation fees patients have paid to connect with you.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-stat-grid" data-mr-scroll-entry style="--index: 0">
                    <div class="mr-stat-card">
                        <div class="mr-field-hint">Total earned</div>
                        <div class="mr-medicine-card-name" style="font-size: 22px;">$<?= number_format($totalEarned, 2) ?></div>
                    </div>
                    <div class="mr-stat-card">
                        <div class="mr-field-hint">Payments received</div>
                        <div class="mr-medicine-card-name" style="font-size: 22px;"><?= count($payments) ?></div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <?php if (empty($payments)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 1">
                            <i class="ph ph-receipt"></i>
                            <p>No payments yet. Set a consultation fee on your Account page to start earning from new connections.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-table-wrap" data-mr-scroll-entry style="--index: 1">
                            <table class="mr-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>For</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $p): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($p['patient_name']) ?>
                                                <div class="mr-field-hint"><?= htmlspecialchars($p['patient_email']) ?></div>
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
