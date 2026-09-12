<?php

/**
 * payments.php (admin side)
 * -----------------------------------------------------------------------
 * Read-only oversight of every payments row across the platform (all
 * patients, all doctors). No admin action on a payment is built (no
 * refund, no void) -- this is visibility only, matching how the admin's
 * Reports page (§6) is the only admin write surface built so far, and
 * consistent with the simulated/no-real-gateway scope (README §7/§8).
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

$mrRootBase = '../../../';
require_role('admin', $mrRootBase);

$activePage = 'payments';

$currentUser = [
    'name'  => $_SESSION['name']  ?? 'Admin',
    'email' => $_SESSION['email'] ?? '',
    'avatar' => null,
];

$payments = [];
$stmt = mysqli_prepare($con, "
    SELECT p.id, p.amount, p.type, p.status, p.paid_at, p.created_at,
           pat.name AS patient_name, pat.email AS patient_email,
           doc.name AS doctor_name, doc.email AS doctor_email
    FROM payments p
    JOIN users pat ON pat.id = p.payer_id
    JOIN users doc ON doc.id = p.payee_id
    ORDER BY p.created_at DESC
");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $payments[] = $row;
}
mysqli_stmt_close($stmt);

$paidPayments = array_filter($payments, fn($p) => $p['status'] === 'paid');
$totalVolume = array_sum(array_map(fn($p) => (float) $p['amount'], $paidPayments));
$transactionCount = count($payments);
$paidCount = count($paidPayments);
$averageTransaction = $paidCount > 0 ? $totalVolume / $paidCount : 0.0;

// --- This calendar month's revenue -------------------------------------------
$monthRevenueStmt = mysqli_prepare($con, "
    SELECT COALESCE(SUM(amount), 0) AS total, COUNT(*) AS c
    FROM payments
    WHERE status = 'paid' AND YEAR(paid_at) = YEAR(CURDATE()) AND MONTH(paid_at) = MONTH(CURDATE())
");
mysqli_stmt_execute($monthRevenueStmt);
$monthRow = mysqli_fetch_assoc(mysqli_stmt_get_result($monthRevenueStmt));
$monthRevenue = (float) ($monthRow['total'] ?? 0);
$monthCount = (int) ($monthRow['c'] ?? 0);
mysqli_stmt_close($monthRevenueStmt);

// --- Revenue by doctor (for the breakdown bars) ------------------------------
$revenueByDoctor = [];
$byDocStmt = mysqli_prepare($con, "
    SELECT doc.id, doc.name, doc.specialty, COALESCE(SUM(p.amount), 0) AS total, COUNT(*) AS c
    FROM payments p
    JOIN users doc ON doc.id = p.payee_id
    WHERE p.status = 'paid'
    GROUP BY doc.id, doc.name, doc.specialty
    ORDER BY total DESC
");
mysqli_stmt_execute($byDocStmt);
$byDocResult = mysqli_stmt_get_result($byDocStmt);
while ($row = mysqli_fetch_assoc($byDocResult)) {
    $revenueByDoctor[] = $row;
}
mysqli_stmt_close($byDocStmt);
$topDoctorTotal = !empty($revenueByDoctor) ? (float) $revenueByDoctor[0]['total'] : 0.0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare admin — platform financial statistics.">
    <title>Financial Statistics · Kare Admin</title>

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

        <?php include __DIR__ . '/../../../shared/admin/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../../shared/admin/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">Financial <strong>Statistics</strong></div>
                            <div class="mr-layout-header-sub">Platform-wide revenue from consultation-fee payments, read-only.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-stat-grid" data-mr-scroll-entry style="--index: 0">
                    <div class="mr-stat-card">
                        <div class="mr-field-hint">Total platform revenue</div>
                        <div class="mr-medicine-card-name" style="font-size: 22px;">$<?= number_format($totalVolume, 2) ?></div>
                    </div>
                    <div class="mr-stat-card">
                        <div class="mr-field-hint">This month</div>
                        <div class="mr-medicine-card-name" style="font-size: 22px;">$<?= number_format($monthRevenue, 2) ?></div>
                        <div class="mr-field-hint"><?= $monthCount ?> payment<?= $monthCount === 1 ? '' : 's' ?></div>
                    </div>
                    <div class="mr-stat-card">
                        <div class="mr-field-hint">Transactions</div>
                        <div class="mr-medicine-card-name" style="font-size: 22px;"><?= $transactionCount ?></div>
                    </div>
                    <div class="mr-stat-card">
                        <div class="mr-field-hint">Average payment</div>
                        <div class="mr-medicine-card-name" style="font-size: 22px;">$<?= number_format($averageTransaction, 2) ?></div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 1">
                        <div class="mr-card-heading">Revenue by doctor</div>
                    </div>

                    <?php if (empty($revenueByDoctor)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 2">
                            <i class="ph ph-chart-bar"></i>
                            <p>No revenue yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-revenue-list" data-mr-scroll-entry style="--index: 2">
                            <?php foreach ($revenueByDoctor as $row): ?>
                                <?php $pct = $topDoctorTotal > 0 ? ((float) $row['total'] / $topDoctorTotal) * 100 : 0; ?>
                                <div class="mr-revenue-item">
                                    <div class="mr-revenue-header">
                                        <span>
                                            <?= htmlspecialchars($row['name']) ?>
                                            <?php if (!empty($row['specialty'])): ?>
                                                <span class="mr-field-hint">&middot; <?= htmlspecialchars($row['specialty']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <span class="mr-field-hint">$<?= number_format((float) $row['total'], 2) ?> &middot; <?= (int) $row['c'] ?> payment<?= (int) $row['c'] === 1 ? '' : 's' ?></span>
                                    </div>
                                    <div class="mr-rate-bar">
                                        <div class="mr-rate-bar-fill" style="width: <?= number_format($pct, 1) ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mr-divider"></div>

                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 3">
                        <div class="mr-card-heading">All transactions</div>
                    </div>

                    <?php if (empty($payments)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 4">
                            <i class="ph ph-receipt"></i>
                            <p>No payments have been made yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-table-wrap" data-mr-scroll-entry style="--index: 4">
                            <table class="mr-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Doctor</th>
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
                                            <td>
                                                <?= htmlspecialchars($p['doctor_name']) ?>
                                                <div class="mr-field-hint"><?= htmlspecialchars($p['doctor_email']) ?></div>
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
