<?php

/**
 * pages/doctor/home.php
 * -----------------------------------------------------------------------
 * Doctor dashboard: pending connection requests, active patient count,
 * unread messages, and a preview list of the most recent pending
 * requests so a doctor can act on them without leaving the dashboard.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../assets/connection/Connection.php';
require_once __DIR__ . '/../../assets/helpers/auth.php';

$mrRootBase = '../../';
require_role('doctor', $mrRootBase);

$activePage = 'dashboard';
$doctorId = (int) $_SESSION['user_id'];

$currentUser = [
    'name'  => $_SESSION['name']  ?? 'Doctor',
    'email' => $_SESSION['email'] ?? '',
    'avatar' => null,
];

// --- Stats -------------------------------------------------------------------
$pendingStmt = mysqli_prepare($con, "SELECT COUNT(*) AS c FROM doctor_connections WHERE doctor_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($pendingStmt, 'i', $doctorId);
mysqli_stmt_execute($pendingStmt);
$pendingCount = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($pendingStmt))['c'] ?? 0);
mysqli_stmt_close($pendingStmt);

$activeStmt = mysqli_prepare($con, "SELECT COUNT(*) AS c FROM doctor_connections WHERE doctor_id = ? AND status = 'accepted'");
mysqli_stmt_bind_param($activeStmt, 'i', $doctorId);
mysqli_stmt_execute($activeStmt);
$activeCount = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($activeStmt))['c'] ?? 0);
mysqli_stmt_close($activeStmt);

$unreadStmt = mysqli_prepare($con, "
    SELECT COUNT(*) AS c
    FROM messages m
    JOIN doctor_connections dc ON dc.id = m.connection_id
    WHERE dc.doctor_id = ? AND m.sender_id != ? AND m.read_at IS NULL
");
mysqli_stmt_bind_param($unreadStmt, 'ii', $doctorId, $doctorId);
mysqli_stmt_execute($unreadStmt);
$unreadCount = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($unreadStmt))['c'] ?? 0);
mysqli_stmt_close($unreadStmt);

$earningsStmt = mysqli_prepare($con, "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE payee_id = ? AND status = 'paid'");
mysqli_stmt_bind_param($earningsStmt, 'i', $doctorId);
mysqli_stmt_execute($earningsStmt);
$totalEarnings = (float) (mysqli_fetch_assoc(mysqli_stmt_get_result($earningsStmt))['total'] ?? 0);
mysqli_stmt_close($earningsStmt);

$stats = [
    ['icon' => 'ph-user-plus',       'value' => (string) $pendingCount, 'label' => 'Pending requests'],
    ['icon' => 'ph-users-three',     'value' => (string) $activeCount,  'label' => 'Active patients'],
    ['icon' => 'ph-chat-circle-dots', 'value' => (string) $unreadCount, 'label' => 'Unread messages'],
    ['icon' => 'ph-currency-circle-dollar', 'value' => '$' . number_format($totalEarnings, 2), 'label' => 'Total earnings', 'href' => 'payments/payments.php'],
];

// --- Recent pending requests preview (up to 5) --------------------------------
$recentRequests = [];
$reqStmt = mysqli_prepare($con, "
    SELECT dc.id, dc.message, dc.requested_at, u.name, u.email
    FROM doctor_connections dc
    JOIN users u ON u.id = dc.patient_id
    WHERE dc.doctor_id = ? AND dc.status = 'pending'
    ORDER BY dc.requested_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($reqStmt, 'i', $doctorId);
mysqli_stmt_execute($reqStmt);
$reqResult = mysqli_stmt_get_result($reqStmt);
while ($row = mysqli_fetch_assoc($reqResult)) {
    $recentRequests[] = $row;
}
mysqli_stmt_close($reqStmt);

// --- Recent payments received preview (up to 5) -------------------------------
$recentPayments = [];
$payStmt = mysqli_prepare($con, "
    SELECT p.id, p.amount, p.paid_at, p.created_at, pat.name AS patient_name
    FROM payments p
    JOIN users pat ON pat.id = p.payer_id
    WHERE p.payee_id = ? AND p.status = 'paid'
    ORDER BY p.created_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($payStmt, 'i', $doctorId);
mysqli_stmt_execute($payStmt);
$payResult = mysqli_stmt_get_result($payStmt);
while ($row = mysqli_fetch_assoc($payResult)) {
    $recentPayments[] = $row;
}
mysqli_stmt_close($payStmt);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare doctor dashboard.">
    <title>Dashboard · Kare for Doctors</title>

    <link rel="stylesheet" href="../../shared/tokens.css">
    <link rel="stylesheet" href="../../shared/base.css">
    <link rel="stylesheet" href="../../shared/components.css">
    <link rel="stylesheet" href="../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="home.css">

    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../shared/modal/modal.php'; ?>

    <div class="mr-app-shell" data-mr-app-shell>

        <?php include __DIR__ . '/../../shared/doctor/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../shared/doctor/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">
                                Welcome, <strong><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?></strong>
                            </div>
                            <div class="mr-layout-header-sub">Here's what needs your attention today.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <div class="mr-stat-grid">
                        <?php foreach ($stats as $i => $stat): ?>
                            <?php $statTag = !empty($stat['href']) ? 'a' : 'div'; ?>
                            <<?= $statTag ?> class="mr-stat-card" <?= !empty($stat['href']) ? 'href="' . htmlspecialchars($stat['href']) . '"' : '' ?> data-mr-scroll-entry style="--index: <?= $i ?>">
                                <div class="mr-stat-card-top">
                                    <span class="mr-stat-icon"><i class="ph <?= htmlspecialchars($stat['icon']) ?>"></i></span>
                                </div>
                                <div class="mr-stat-value"><?= htmlspecialchars($stat['value']) ?></div>
                                <div class="mr-stat-label"><?= htmlspecialchars($stat['label']) ?></div>
                            </<?= $statTag ?>>
                        <?php endforeach; ?>
                    </div>

                    <div class="mr-divider"></div>

                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 3">
                        <div class="mr-card-heading">Recent connection requests</div>
                        <a href="requests/requests.php" class="mr-card-link">View all &rarr;</a>
                    </div>

                    <?php if (empty($recentRequests)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 4">
                            <i class="ph ph-user-plus"></i>
                            <p>No pending requests right now.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-request-list" data-mr-scroll-entry style="--index: 4">
                            <?php foreach ($recentRequests as $r): ?>
                                <div class="mr-request-item">
                                    <span class="mr-profile-avatar mr-request-avatar"><?= htmlspecialchars(strtoupper(substr($r['name'], 0, 1))) ?></span>
                                    <div class="mr-request-main">
                                        <div class="mr-medicine-card-name"><?= htmlspecialchars($r['name']) ?></div>
                                        <div class="mr-field-hint"><?= htmlspecialchars($r['email']) ?></div>
                                    </div>
                                    <a href="requests/requests.php" class="mr-btn-secondary">Review</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mr-divider"></div>

                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 5">
                        <div class="mr-card-heading">Recent payments received</div>
                        <a href="payments/payments.php" class="mr-card-link">View all &rarr;</a>
                    </div>

                    <?php if (empty($recentPayments)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 6">
                            <i class="ph ph-receipt"></i>
                            <p>No payments yet. Set a consultation fee on your Account page to start earning from new connections.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-request-list" data-mr-scroll-entry style="--index: 6">
                            <?php foreach ($recentPayments as $p): ?>
                                <div class="mr-request-item">
                                    <span class="mr-profile-avatar mr-request-avatar"><?= htmlspecialchars(strtoupper(substr($p['patient_name'], 0, 1))) ?></span>
                                    <div class="mr-request-main">
                                        <div class="mr-medicine-card-name"><?= htmlspecialchars($p['patient_name']) ?></div>
                                        <div class="mr-field-hint"><?= htmlspecialchars(date('d M Y', strtotime($p['paid_at'] ?? $p['created_at']))) ?></div>
                                    </div>
                                    <span class="mr-badge is-success">$<?= number_format((float) $p['amount'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </section>

            </main>
        </div>
    </div>

    <script src="../../shared/modal/modal.js"></script>
    <script src="../../shared/notifications/notifications.js"></script>
    <script src="home.js"></script>
</body>

</html>
