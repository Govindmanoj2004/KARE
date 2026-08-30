<?php

/**
 * home.php
 * -----------------------------------------------------------------------
 * Home dashboard for a logged-in patient/caretaker user.
 * Demonstrates how sidebar.php and navbar.php are wired into a page.
 * -----------------------------------------------------------------------
 */
session_start();

// --- Auth guard -----------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login/index.php');
    exit;
}

// --- DB connection ----------------------------------------------------------
require_once __DIR__ . '/../../assets/connection/Connection.php';
require_once __DIR__ . '/../../assets/helpers/dose_logs.php';

$activePage = 'dashboard';
$mrRootBase = '../../'; // this file lives at pages/user/home.php

$userId = (int) $_SESSION['user_id'];
ensure_todays_dose_logs($con, $userId);

$currentUser = [
    'name'   => $_SESSION['name']  ?? 'Guest Caretaker',
    'email'  => $_SESSION['email'] ?? '',
    'avatar' => null, // e.g. 'assets/img/users/12.jpg'
];

// --- Stats: doses due today / taken this week / missed doses --------------
$stats = [
    ['icon' => 'ph-pill',           'value' => '0', 'label' => 'Doses due today'],
    ['icon' => 'ph-check-circle',   'value' => '0', 'label' => 'Taken this week'],
    ['icon' => 'ph-warning-circle', 'value' => '0', 'label' => 'Missed doses'],
    ['icon' => 'ph-users-three',    'value' => '1', 'label' => 'Patients in your care'],
];

$dueTodayStmt = mysqli_prepare($con, "
    SELECT COUNT(*) AS c
    FROM dose_logs dl
    JOIN medicine_schedules ms ON ms.id = dl.schedule_id
    JOIN medicines m ON m.id = ms.medicine_id
    WHERE m.user_id = ? AND DATE(dl.scheduled_for) = CURDATE()
");
mysqli_stmt_bind_param($dueTodayStmt, 'i', $userId);
mysqli_stmt_execute($dueTodayStmt);
$dueTodayRow = mysqli_fetch_assoc(mysqli_stmt_get_result($dueTodayStmt));
mysqli_stmt_close($dueTodayStmt);
$stats[0]['value'] = (string) $dueTodayRow['c'];

$takenWeekStmt = mysqli_prepare($con, "
    SELECT COUNT(*) AS c
    FROM dose_logs dl
    JOIN medicine_schedules ms ON ms.id = dl.schedule_id
    JOIN medicines m ON m.id = ms.medicine_id
    WHERE m.user_id = ? AND dl.status = 'taken' AND dl.scheduled_for >= (CURDATE() - INTERVAL 7 DAY)
");
mysqli_stmt_bind_param($takenWeekStmt, 'i', $userId);
mysqli_stmt_execute($takenWeekStmt);
$takenWeekRow = mysqli_fetch_assoc(mysqli_stmt_get_result($takenWeekStmt));
mysqli_stmt_close($takenWeekStmt);
$stats[1]['value'] = (string) $takenWeekRow['c'];

$missedStmt = mysqli_prepare($con, "
    SELECT COUNT(*) AS c
    FROM dose_logs dl
    JOIN medicine_schedules ms ON ms.id = dl.schedule_id
    JOIN medicines m ON m.id = ms.medicine_id
    WHERE m.user_id = ? AND dl.status = 'missed'
");
mysqli_stmt_bind_param($missedStmt, 'i', $userId);
mysqli_stmt_execute($missedStmt);
$missedRow = mysqli_fetch_assoc(mysqli_stmt_get_result($missedStmt));
mysqli_stmt_close($missedStmt);
$stats[2]['value'] = (string) $missedRow['c'];

// --- Today's schedule -------------------------------------------------------
$scheduleStmt = mysqli_prepare($con, "
    SELECT m.name AS medicine, m.dosage, dl.scheduled_for, dl.status
    FROM dose_logs dl
    JOIN medicine_schedules ms ON ms.id = dl.schedule_id
    JOIN medicines m ON m.id = ms.medicine_id
    WHERE m.user_id = ? AND DATE(dl.scheduled_for) = CURDATE()
    ORDER BY dl.scheduled_for ASC
");
mysqli_stmt_bind_param($scheduleStmt, 'i', $userId);
mysqli_stmt_execute($scheduleStmt);
$scheduleResult = mysqli_stmt_get_result($scheduleStmt);

$todaysSchedule = [];
while ($row = mysqli_fetch_assoc($scheduleResult)) {
    $medicineLabel = $row['medicine'] . ($row['dosage'] ? ' ' . $row['dosage'] : '');
    $todaysSchedule[] = [
        'patient'  => $currentUser['name'],
        'medicine' => $medicineLabel,
        'time'     => date('g:i A', strtotime($row['scheduled_for'])),
        'status'   => $row['status'],
    ];
}
mysqli_stmt_close($scheduleStmt);

$statusLabels = [
    'taken'    => ['class' => 'is-taken',    'text' => 'Taken'],
    'upcoming' => ['class' => 'is-upcoming', 'text' => 'Upcoming'],
    'missed'   => ['class' => 'is-missed',   'text' => 'Missed'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare medication dashboard — track doses, schedules, and patient care at a glance.">
    <title>Dashboard · Kare</title>

    <!-- Design System (order matters: tokens > base > components > page) -->
    <link rel="stylesheet" href="../../shared/tokens.css">
    <link rel="stylesheet" href="../../shared/base.css">
    <link rel="stylesheet" href="../../shared/components.css">
    <link rel="stylesheet" href="../../shared/modal/modal.css">
    <link rel="stylesheet" href="home.css">

    <!-- Icons: Phosphor (regular weight) -->
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

    <!-- Font: Poppins (same as login/signup) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../shared/modal/modal.php'; ?>

    <div class="mr-app-shell" data-mr-app-shell>

        <?php include __DIR__ . '/../../shared/user/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../shared/user/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">
                                Good afternoon, <strong><?= htmlspecialchars(explode(' ', $currentUser['name'])[0]) ?></strong>
                            </div>
                            <div class="mr-layout-header-sub">Here's what needs your attention today.</div>
                        </div>
                        <button type="button" class="mr-btn">
                            <i class="ph ph-plus"></i> Add reminder
                        </button>
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

                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 4">
                        <div class="mr-card-heading">Today's schedule</div>
                        <a href="schedule/schedule.php" class="mr-card-link">View full schedule &rarr;</a>
                    </div>

                    <div class="mr-table-wrap" data-mr-scroll-entry style="--index: 5">
                        <table class="mr-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Medicine</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todaysSchedule)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align:center; color: var(--text-muted); padding: 24px 12px;">
                                            No doses scheduled for today yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($todaysSchedule as $row): $s = $statusLabels[$row['status']]; ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['patient']) ?></td>
                                            <td><?= htmlspecialchars($row['medicine']) ?></td>
                                            <td><?= htmlspecialchars($row['time']) ?></td>
                                            <td><span class="mr-status <?= $s['class'] ?>"><?= $s['text'] ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </section>

            </main>
        </div>
    </div>

    <script src="home.js"></script>
    <script src="../../shared/modal/modal.js"></script>
</body>

</html>