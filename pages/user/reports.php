<?php

/**
 * reports.php
 * -----------------------------------------------------------------------
 * Personal medication-adherence analytics for the logged-in patient.
 * Distinct from pages/user/report/ ("Report an Issue" — a support
 * ticket to the admin); this is a read-only view of the user's own
 * dose_logs history. No controller needed — nothing here writes data.
 * -----------------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];

$activePage = 'reports';
$mrRootBase = '../../'; // this file lives at pages/user/reports.php

// --- Overall adherence (last 30 days) ---------------------------------------
$summaryStmt = mysqli_prepare($con, "
    SELECT
        COUNT(*) AS total,
        SUM(dl.status = 'taken')   AS taken,
        SUM(dl.status = 'missed')  AS missed,
        SUM(dl.status = 'upcoming') AS upcoming
    FROM dose_logs dl
    JOIN medicine_schedules ms ON ms.id = dl.schedule_id
    JOIN medicines m ON m.id = ms.medicine_id
    WHERE m.user_id = ? AND dl.scheduled_for >= (CURDATE() - INTERVAL 30 DAY)
");
mysqli_stmt_bind_param($summaryStmt, 'i', $userId);
mysqli_stmt_execute($summaryStmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($summaryStmt));
mysqli_stmt_close($summaryStmt);

$total   = (int) ($summary['total'] ?? 0);
$taken   = (int) ($summary['taken'] ?? 0);
$missed  = (int) ($summary['missed'] ?? 0);
$upcoming = (int) ($summary['upcoming'] ?? 0);
$countedDoses = $taken + $missed; // adherence excludes doses still upcoming
$adherenceRate = $countedDoses > 0 ? round(($taken / $countedDoses) * 100) : null;

// --- Per-medicine breakdown ---------------------------------------------------
$byMedicine = [];
$medStmt = mysqli_prepare($con, "
    SELECT
        m.id, m.name, m.dosage,
        SUM(dl.status = 'taken')  AS taken,
        SUM(dl.status = 'missed') AS missed
    FROM medicines m
    JOIN medicine_schedules ms ON ms.medicine_id = m.id
    JOIN dose_logs dl ON dl.schedule_id = ms.id
    WHERE m.user_id = ? AND dl.scheduled_for >= (CURDATE() - INTERVAL 30 DAY)
      AND dl.status IN ('taken', 'missed')
    GROUP BY m.id, m.name, m.dosage
    ORDER BY m.name ASC
");
mysqli_stmt_bind_param($medStmt, 'i', $userId);
mysqli_stmt_execute($medStmt);
$medResult = mysqli_stmt_get_result($medStmt);
while ($row = mysqli_fetch_assoc($medResult)) {
    $t = (int) $row['taken'];
    $m = (int) $row['missed'];
    $row['rate'] = ($t + $m) > 0 ? round(($t / ($t + $m)) * 100) : 0;
    $byMedicine[] = $row;
}
mysqli_stmt_close($medStmt);

// --- Recent history (last 20 taken/missed doses) -----------------------------
$history = [];
$histStmt = mysqli_prepare($con, "
    SELECT m.name, m.dosage, dl.scheduled_for, dl.status
    FROM dose_logs dl
    JOIN medicine_schedules ms ON ms.id = dl.schedule_id
    JOIN medicines m ON m.id = ms.medicine_id
    WHERE m.user_id = ? AND dl.status IN ('taken', 'missed')
    ORDER BY dl.scheduled_for DESC
    LIMIT 20
");
mysqli_stmt_bind_param($histStmt, 'i', $userId);
mysqli_stmt_execute($histStmt);
$histResult = mysqli_stmt_get_result($histStmt);
while ($row = mysqli_fetch_assoc($histResult)) {
    $history[] = $row;
}
mysqli_stmt_close($histStmt);

$statusLabels = [
    'taken'  => ['class' => 'is-taken',  'text' => 'Taken'],
    'missed' => ['class' => 'is-missed', 'text' => 'Missed'],
];

// --- Calendar data: dose status per day for the selected month ---------------
// Reuses the same dose_logs join used everywhere else, just grouped by day
// instead of listed flat. Supports navigating months via ?month=YYYY-MM.
$monthParam = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = date('Y-m');
}
$monthStart = $monthParam . '-01';
$monthLabel = date('F Y', strtotime($monthStart));
$prevMonth = date('Y-m', strtotime($monthStart . ' -1 month'));
$nextMonth = date('Y-m', strtotime($monthStart . ' +1 month'));

$calendarDays = []; // 'YYYY-MM-DD' => ['taken' => n, 'missed' => n, 'upcoming' => n]
$calStmt = mysqli_prepare($con, "
    SELECT DATE(dl.scheduled_for) AS day, dl.status, COUNT(*) AS c
    FROM dose_logs dl
    JOIN medicine_schedules ms ON ms.id = dl.schedule_id
    JOIN medicines m ON m.id = ms.medicine_id
    WHERE m.user_id = ? AND DATE(dl.scheduled_for) BETWEEN ? AND LAST_DAY(?)
    GROUP BY DATE(dl.scheduled_for), dl.status
");
mysqli_stmt_bind_param($calStmt, 'iss', $userId, $monthStart, $monthStart);
mysqli_stmt_execute($calStmt);
$calResult = mysqli_stmt_get_result($calStmt);
while ($row = mysqli_fetch_assoc($calResult)) {
    $calendarDays[$row['day']][$row['status']] = (int) $row['c'];
}
mysqli_stmt_close($calStmt);

$daysInMonth = (int) date('t', strtotime($monthStart));
$firstWeekday = (int) date('w', strtotime($monthStart)); // 0 (Sun) - 6 (Sat)
$todayStr = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare reports — your medication adherence over the last 30 days.">
    <title>Reports · Kare</title>

    <link rel="stylesheet" href="../../shared/tokens.css">
    <link rel="stylesheet" href="../../shared/base.css">
    <link rel="stylesheet" href="../../shared/components.css">
    <link rel="stylesheet" href="../../shared/modal/modal.css">
    <link rel="stylesheet" href="reports.css">

    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

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
                            <div class="mr-layout-header-heading">Your <strong>Reports</strong></div>
                            <div class="mr-layout-header-sub">Medication adherence over the last 30 days.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <div class="mr-stat-grid">
                        <div class="mr-stat-card" data-mr-scroll-entry style="--index: 0">
                            <div class="mr-stat-card-top">
                                <span class="mr-stat-icon"><i class="ph ph-percent"></i></span>
                            </div>
                            <div class="mr-stat-value"><?= $adherenceRate !== null ? $adherenceRate . '%' : '&mdash;' ?></div>
                            <div class="mr-stat-label">Adherence rate</div>
                        </div>
                        <div class="mr-stat-card" data-mr-scroll-entry style="--index: 1">
                            <div class="mr-stat-card-top">
                                <span class="mr-stat-icon"><i class="ph ph-check-circle"></i></span>
                            </div>
                            <div class="mr-stat-value"><?= $taken ?></div>
                            <div class="mr-stat-label">Doses taken</div>
                        </div>
                        <div class="mr-stat-card" data-mr-scroll-entry style="--index: 2">
                            <div class="mr-stat-card-top">
                                <span class="mr-stat-icon"><i class="ph ph-warning-circle"></i></span>
                            </div>
                            <div class="mr-stat-value"><?= $missed ?></div>
                            <div class="mr-stat-label">Doses missed</div>
                        </div>
                        <div class="mr-stat-card" data-mr-scroll-entry style="--index: 3">
                            <div class="mr-stat-card-top">
                                <span class="mr-stat-icon"><i class="ph ph-clock"></i></span>
                            </div>
                            <div class="mr-stat-value"><?= $upcoming ?></div>
                            <div class="mr-stat-label">Still upcoming</div>
                        </div>
                    </div>

                    <div class="mr-divider"></div>

                    <!-- Per-medicine breakdown -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 4">
                        <div class="mr-card-heading">By medicine</div>
                    </div>

                    <?php if (empty($byMedicine)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 5">
                            <i class="ph ph-chart-line"></i>
                            <p>No dose history in the last 30 days yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-medicine-rate-list" data-mr-scroll-entry style="--index: 5">
                            <?php foreach ($byMedicine as $row): ?>
                                <div class="mr-medicine-rate-item">
                                    <div class="mr-medicine-rate-header">
                                        <span><?= htmlspecialchars($row['name'] . ($row['dosage'] ? ' ' . $row['dosage'] : '')) ?></span>
                                        <span class="mr-field-hint"><?= $row['rate'] ?>% &middot; <?= (int) $row['taken'] ?> taken / <?= (int) $row['missed'] ?> missed</span>
                                    </div>
                                    <div class="mr-rate-bar">
                                        <div class="mr-rate-bar-fill" style="width: <?= (int) $row['rate'] ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mr-divider"></div>

                    <!-- Recent history -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 6">
                        <div class="mr-card-heading">Recent history</div>
                        <div class="mr-view-toggle">
                            <button type="button" class="mr-view-toggle-btn is-active" data-mr-view-btn="table">Table</button>
                            <button type="button" class="mr-view-toggle-btn" data-mr-view-btn="calendar">Calendar</button>
                        </div>
                    </div>

                    <div data-mr-view-panel="table">
                    <?php if (empty($history)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 7">
                            <i class="ph ph-clock-counter-clockwise"></i>
                            <p>Nothing recorded yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-table-wrap" data-mr-scroll-entry style="--index: 7">
                            <table class="mr-table">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Scheduled for</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $row): $s = $statusLabels[$row['status']]; ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['name'] . ($row['dosage'] ? ' ' . $row['dosage'] : '')) ?></td>
                                            <td><?= htmlspecialchars(date('d M, g:i A', strtotime($row['scheduled_for']))) ?></td>
                                            <td><span class="mr-status <?= $s['class'] ?>"><?= $s['text'] ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    </div>

                    <div data-mr-view-panel="calendar" hidden>
                        <div class="mr-calendar-nav">
                            <a href="reports.php?month=<?= $prevMonth ?>" class="mr-icon-btn" title="Previous month"><i class="ph ph-caret-left"></i></a>
                            <span class="mr-calendar-month-label"><?= htmlspecialchars($monthLabel) ?></span>
                            <a href="reports.php?month=<?= $nextMonth ?>" class="mr-icon-btn" title="Next month"><i class="ph ph-caret-right"></i></a>
                        </div>

                        <div class="mr-calendar-grid">
                            <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $wd): ?>
                                <div class="mr-calendar-weekday"><?= $wd ?></div>
                            <?php endforeach; ?>

                            <?php for ($i = 0; $i < $firstWeekday; $i++): ?>
                                <div class="mr-calendar-cell is-empty"></div>
                            <?php endfor; ?>

                            <?php for ($day = 1; $day <= $daysInMonth; $day++):
                                $dateStr = sprintf('%s-%02d', $monthParam, $day);
                                $dayData = $calendarDays[$dateStr] ?? [];
                                $taken = $dayData['taken'] ?? 0;
                                $missed = $dayData['missed'] ?? 0;
                                $upcoming = $dayData['upcoming'] ?? 0;
                            ?>
                                <div class="mr-calendar-cell<?= $dateStr === $todayStr ? ' is-today' : '' ?>">
                                    <span class="mr-calendar-daynum"><?= $day ?></span>
                                    <?php if ($taken || $missed || $upcoming): ?>
                                        <span class="mr-calendar-dots">
                                            <?php if ($taken): ?><span class="mr-calendar-dot is-taken" title="<?= $taken ?> taken"></span><?php endif; ?>
                                            <?php if ($missed): ?><span class="mr-calendar-dot is-missed" title="<?= $missed ?> missed"></span><?php endif; ?>
                                            <?php if ($upcoming): ?><span class="mr-calendar-dot is-upcoming" title="<?= $upcoming ?> upcoming"></span><?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="mr-calendar-legend">
                            <span><span class="mr-calendar-dot is-taken"></span> Taken</span>
                            <span><span class="mr-calendar-dot is-missed"></span> Missed</span>
                            <span><span class="mr-calendar-dot is-upcoming"></span> Upcoming</span>
                        </div>
                    </div>

                </section>

            </main>
        </div>
    </div>

    <script src="../../shared/modal/modal.js"></script>
    <script src="reports.js"></script>
</body>

</html>
