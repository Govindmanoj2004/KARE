<?php

/**
 * pages/admin/home.php
 * -----------------------------------------------------------------------
 * Admin dashboard: system-wide counts (patients, doctors, open reports,
 * unverified doctors) and a "system health" style breakdown of user
 * status, plus a preview of the most recent open reports.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../assets/connection/Connection.php';
require_once __DIR__ . '/../../assets/helpers/auth.php';

$mrRootBase = '../../';
require_role('admin', $mrRootBase);

$activePage = 'dashboard';

$currentUser = [
    'name'  => $_SESSION['name']  ?? 'Admin',
    'email' => $_SESSION['email'] ?? '',
    'avatar' => null,
];

// --- Stats -------------------------------------------------------------------
function mr_count(mysqli $con, string $sql): int
{
    $result = mysqli_query($con, $sql);
    return (int) (mysqli_fetch_assoc($result)['c'] ?? 0);
}

$patientCount  = mr_count($con, "SELECT COUNT(*) AS c FROM users WHERE role = 'patient'");
$doctorCount   = mr_count($con, "SELECT COUNT(*) AS c FROM users WHERE role = 'doctor'");
$openReports   = mr_count($con, "SELECT COUNT(*) AS c FROM reports WHERE status = 'open'");
$unverifiedDoc = mr_count($con, "SELECT COUNT(*) AS c FROM users WHERE role = 'doctor' AND is_verified = 0");

$stats = [
    ['icon' => 'ph-users-three',      'value' => (string) $patientCount,  'label' => 'Patients'],
    ['icon' => 'ph-stethoscope',      'value' => (string) $doctorCount,   'label' => 'Doctors'],
    ['icon' => 'ph-flag',             'value' => (string) $openReports,   'label' => 'Open reports'],
    ['icon' => 'ph-shield-warning',   'value' => (string) $unverifiedDoc, 'label' => 'Unverified doctors'],
];

// --- Account-status breakdown (a lightweight "system health" view) ----------
$statusBreakdown = [];
$statusResult = mysqli_query($con, "SELECT status, COUNT(*) AS c FROM users GROUP BY status");
while ($row = mysqli_fetch_assoc($statusResult)) {
    $statusBreakdown[$row['status']] = (int) $row['c'];
}

// --- Recent open reports preview (up to 5) -----------------------------------
$recentReports = [];
$reportsResult = mysqli_query($con, "
    SELECT r.id, r.subject, r.created_at, u.name AS user_name
    FROM reports r
    JOIN users u ON u.id = r.user_id
    WHERE r.status = 'open'
    ORDER BY r.created_at DESC
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($reportsResult)) {
    $recentReports[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare admin dashboard.">
    <title>Dashboard · Kare Admin</title>

    <link rel="stylesheet" href="../../shared/tokens.css">
    <link rel="stylesheet" href="../../shared/base.css">
    <link rel="stylesheet" href="../../shared/components.css">
    <link rel="stylesheet" href="../../shared/modal/modal.css">
    <link rel="stylesheet" href="home.css">

    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../shared/modal/modal.php'; ?>

    <div class="mr-app-shell" data-mr-app-shell>

        <?php include __DIR__ . '/../../shared/admin/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../shared/admin/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">Admin <strong>Dashboard</strong></div>
                            <div class="mr-layout-header-sub">System overview and what needs your attention.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <div class="mr-stat-grid">
                        <?php foreach ($stats as $i => $stat): ?>
                            <div class="mr-stat-card" data-mr-scroll-entry style="--index: <?= $i ?>">
                                <div class="mr-stat-card-top">
                                    <span class="mr-stat-icon"><i class="ph <?= htmlspecialchars($stat['icon']) ?>"></i></span>
                                </div>
                                <div class="mr-stat-value"><?= htmlspecialchars($stat['value']) ?></div>
                                <div class="mr-stat-label"><?= htmlspecialchars($stat['label']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mr-divider"></div>

                    <!-- Account status breakdown -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 4">
                        <div class="mr-card-heading">Account status</div>
                    </div>

                    <div class="mr-status-breakdown" data-mr-scroll-entry style="--index: 5">
                        <?php foreach (['active', 'suspended', 'deactivated'] as $statusKey): ?>
                            <div class="mr-status-chip">
                                <span class="mr-status <?= $statusKey === 'active' ? 'is-taken' : ($statusKey === 'suspended' ? 'is-missed' : 'is-upcoming') ?>">
                                    <?= ucfirst($statusKey) ?>
                                </span>
                                <span class="mr-field-hint"><?= (int) ($statusBreakdown[$statusKey] ?? 0) ?> users</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mr-divider"></div>

                    <!-- Recent open reports -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 6">
                        <div class="mr-card-heading">Recent open reports</div>
                        <a href="reports/reports.php" class="mr-card-link">View all &rarr;</a>
                    </div>

                    <?php if (empty($recentReports)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 7">
                            <i class="ph ph-flag"></i>
                            <p>No open reports right now.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-request-list" data-mr-scroll-entry style="--index: 7">
                            <?php foreach ($recentReports as $r): ?>
                                <div class="mr-request-item">
                                    <div class="mr-request-main">
                                        <div class="mr-medicine-card-name"><?= htmlspecialchars($r['subject']) ?></div>
                                        <div class="mr-field-hint">
                                            from <?= htmlspecialchars($r['user_name']) ?> &middot;
                                            <?= htmlspecialchars(date('d M Y', strtotime($r['created_at']))) ?>
                                        </div>
                                    </div>
                                    <a href="reports/reports.php?id=<?= (int) $r['id'] ?>" class="mr-btn-secondary">Review</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </section>

            </main>
        </div>
    </div>

    <script src="../../shared/modal/modal.js"></script>
    <script src="home.js"></script>
</body>

</html>
