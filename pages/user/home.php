<?php

/**
 * home.php
 * -----------------------------------------------------------------------
 * Home dashboard for a logged-in patient/caretaker user.
 * Demonstrates how sidebar.php and navbar.php are wired into a page.
 * -----------------------------------------------------------------------
 */
session_start();

// --- Auth guard (replace with your real check) --------------------------
// if (!isset($_SESSION['user_id'])) { header('Location: ../../auth/login/index.html'); exit; }

// --- DB connection (uncomment once queries below are wired to it) -------
// require_once __DIR__ . '/../../assets/connection/Connection.php';

// --- Data this page needs ------------------------------------------------
// In production these come from MySQL (users, schedules, dose_logs, etc.)
$activePage = 'dashboard';
$mrRootBase = '../../'; // this file lives at pages/user/home.php

$currentUser = [
    'name'   => $_SESSION['name']  ?? 'Reji Mathew',
    'email'  => $_SESSION['email'] ?? 'reji.mathew@example.com',
    'avatar' => null, // e.g. 'assets/img/users/12.jpg'
];

$stats = [
    ['icon' => 'ph-pill',            'value' => '4',  'label' => "Doses due today"],
    ['icon' => 'ph-check-circle',    'value' => '12', 'label' => 'Taken this week'],
    ['icon' => 'ph-warning-circle',  'value' => '1',  'label' => 'Missed doses'],
    ['icon' => 'ph-users-three',     'value' => '3',  'label' => 'Patients in your care'],
];

$todaysSchedule = [
    ['patient' => 'Annamma Thomas', 'medicine' => 'Metformin 500mg',  'time' => '8:00 AM',  'status' => 'taken'],
    ['patient' => 'Annamma Thomas', 'medicine' => 'Amlodipine 5mg',   'time' => '1:00 PM',  'status' => 'upcoming'],
    ['patient' => 'Joseph Kurian',  'medicine' => 'Atorvastatin 10mg', 'time' => '9:00 PM',  'status' => 'upcoming'],
    ['patient' => 'Joseph Kurian',  'medicine' => 'Aspirin 75mg',     'time' => '8:00 AM',  'status' => 'missed'],
];

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
                        <a href="schedule.php" class="mr-card-link">View full schedule &rarr;</a>
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
                                <?php foreach ($todaysSchedule as $row): $s = $statusLabels[$row['status']]; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['patient']) ?></td>
                                        <td><?= htmlspecialchars($row['medicine']) ?></td>
                                        <td><?= htmlspecialchars($row['time']) ?></td>
                                        <td><span class="mr-status <?= $s['class'] ?>"><?= $s['text'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
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