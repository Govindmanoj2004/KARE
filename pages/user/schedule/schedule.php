<?php

/**
 * schedule.php
 * -----------------------------------------------------------------------
 * Logged-in user's medicine schedule management page.
 *   - Add a medicine with one or more daily reminder times.
 *   - Edit / delete an existing medicine (edit reuses the add form via JS).
 *   - See today's doses and mark each as taken or missed.
 * Posts to schedule_controller.php using the same PRG + toast pattern as
 * the rest of the app.
 * -----------------------------------------------------------------------
 */
session_start();

// --- Auth guard -------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/dose_logs.php';

$userId = (int) $_SESSION['user_id'];

$activePage = 'schedule';
$mrRootBase = '../../../'; // this file lives at pages/user/schedule/schedule.php

ensure_todays_dose_logs($con, $userId);

// --- Fetch this user's medicines, each with its reminder times -------------
$medicines = [];
$medStmt = mysqli_prepare($con, 'SELECT id, name, dosage, notes FROM medicines WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC');
mysqli_stmt_bind_param($medStmt, 'i', $userId);
mysqli_stmt_execute($medStmt);
$medResult = mysqli_stmt_get_result($medStmt);
while ($row = mysqli_fetch_assoc($medResult)) {
    $row['times'] = [];
    $medicines[$row['id']] = $row;
}
mysqli_stmt_close($medStmt);

if (!empty($medicines)) {
    $timeStmt = mysqli_prepare($con, "
        SELECT medicine_id, time_of_day
        FROM medicine_schedules
        WHERE medicine_id IN (" . implode(',', array_fill(0, count($medicines), '?')) . ")
          AND is_active = 1
        ORDER BY time_of_day ASC
    ");
    $ids = array_keys($medicines);
    $types = str_repeat('i', count($ids));
    mysqli_stmt_bind_param($timeStmt, $types, ...$ids);
    mysqli_stmt_execute($timeStmt);
    $timeResult = mysqli_stmt_get_result($timeStmt);
    while ($row = mysqli_fetch_assoc($timeResult)) {
        $medicines[$row['medicine_id']]['times'][] = substr($row['time_of_day'], 0, 5); // "HH:MM"
    }
    mysqli_stmt_close($timeStmt);
}

// --- Today's doses, earliest first ------------------------------------------
$todaysDoses = [];
$doseStmt = mysqli_prepare($con, "
    SELECT dl.id AS dose_log_id, m.name, m.dosage, dl.scheduled_for, dl.status, dl.snooze_count
    FROM dose_logs dl
    JOIN medicine_schedules ms ON ms.id = dl.schedule_id
    JOIN medicines m ON m.id = ms.medicine_id
    WHERE m.user_id = ? AND DATE(dl.scheduled_for) = CURDATE()
    ORDER BY dl.scheduled_for ASC
");
mysqli_stmt_bind_param($doseStmt, 'i', $userId);
mysqli_stmt_execute($doseStmt);
$doseResult = mysqli_stmt_get_result($doseStmt);
while ($row = mysqli_fetch_assoc($doseResult)) {
    $todaysDoses[] = $row;
}
mysqli_stmt_close($doseStmt);

$statusLabels = [
    'taken'    => ['class' => 'is-taken',    'text' => 'Taken'],
    'upcoming' => ['class' => 'is-upcoming', 'text' => 'Upcoming'],
    'missed'   => ['class' => 'is-missed',   'text' => 'Missed'],
];

// Repopulate the add-medicine form with old input after a failed submission.
$formOld = $_SESSION['schedule_old'] ?? null;
unset($_SESSION['schedule_old']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare schedule — manage your medicines and today's doses.">
    <title>Schedule · Kare</title>

    <!-- Design System (order matters: tokens > base > components > page) -->
    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="schedule.css">

    <!-- Icons: Phosphor (regular weight) -->
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

    <!-- Font: Poppins (same as login/signup) -->
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
                            <div class="mr-layout-header-heading">Your <strong>Schedule</strong></div>
                            <div class="mr-layout-header-sub">Manage your medicines and track today's doses.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <!-- Today's doses -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 0">
                        <div class="mr-card-heading">Today's doses</div>
                    </div>

                    <?php if (empty($todaysDoses)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 1">
                            <i class="ph ph-pill"></i>
                            <p>No doses scheduled for today. Add a medicine below to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-table-wrap" data-mr-scroll-entry style="--index: 1">
                            <table class="mr-table">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($todaysDoses as $dose):
                                        // A dose still marked 'upcoming' but whose time has already
                                        // passed displays as "Missed" (to-do #6) — this is a display-only
                                        // computed status; the stored value stays 'upcoming' until the
                                        // patient/doctor explicitly acts on it (see README §3.4 on why
                                        // there's no automated status change, only automated display).
                                        $isOverdue = $dose['status'] === 'upcoming' && strtotime($dose['scheduled_for']) < time();
                                        $s = $isOverdue ? $statusLabels['missed'] : $statusLabels[$dose['status']];
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($dose['name'] . ($dose['dosage'] ? ' ' . $dose['dosage'] : '')) ?></td>
                                            <td><?= htmlspecialchars(date('g:i A', strtotime($dose['scheduled_for']))) ?></td>
                                            <td><span class="mr-status <?= $s['class'] ?>"><?= $s['text'] ?></span></td>
                                            <td>
                                                <?php if ($dose['status'] === 'upcoming'): ?>
                                                    <div class="mr-dose-actions">
                                                        <form action="schedule_controller.php" method="post">
                                                            <input type="hidden" name="action" value="mark_dose">
                                                            <input type="hidden" name="dose_log_id" value="<?= (int) $dose['dose_log_id'] ?>">
                                                            <input type="hidden" name="status" value="taken">
                                                            <button type="submit" class="mr-dose-btn is-taken-btn" title="Mark as taken">
                                                                <i class="ph ph-check"></i>
                                                            </button>
                                                        </form>
                                                        <form action="schedule_controller.php" method="post">
                                                            <input type="hidden" name="action" value="mark_dose">
                                                            <input type="hidden" name="dose_log_id" value="<?= (int) $dose['dose_log_id'] ?>">
                                                            <input type="hidden" name="status" value="missed">
                                                            <button type="submit" class="mr-dose-btn is-missed-btn" title="Mark as missed">
                                                                <i class="ph ph-x"></i>
                                                            </button>
                                                        </form>
                                                        <?php if ((int) $dose['snooze_count'] < 3): ?>
                                                            <form action="schedule_controller.php" method="post" class="mr-snooze-form">
                                                                <input type="hidden" name="action" value="snooze_dose">
                                                                <input type="hidden" name="dose_log_id" value="<?= (int) $dose['dose_log_id'] ?>">
                                                                <select name="minutes" class="mr-snooze-select" title="Snooze">
                                                                    <option value="15">Snooze 15m</option>
                                                                    <option value="30">Snooze 30m</option>
                                                                    <option value="60">Snooze 1h</option>
                                                                </select>
                                                                <button type="submit" class="mr-dose-btn is-snooze-btn" title="Snooze this dose">
                                                                    <i class="ph ph-alarm"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="mr-field-hint">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mr-divider"></div>

                    <!-- Add / edit medicine form -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 2">
                        <div class="mr-card-heading" data-mr-form-heading>Add a medicine</div>
                    </div>

                    <form action="schedule_controller.php" method="post" class="mr-form" data-mr-scroll-entry style="--index: 3" data-mr-medicine-form>
                        <input type="hidden" name="action" value="create_medicine" data-mr-form-action>
                        <input type="hidden" name="medicine_id" value="" data-mr-form-medicine-id>

                        <div class="mr-form-grid">
                            <div class="mr-field">
                                <label class="mr-label" for="name">Medicine name</label>
                                <input class="mr-input" type="text" id="name" name="name" maxlength="150"
                                    placeholder="e.g. Metformin" value="<?= htmlspecialchars($formOld['name'] ?? '') ?>" required data-mr-form-name>
                            </div>

                            <div class="mr-field">
                                <label class="mr-label" for="dosage">Dosage</label>
                                <input class="mr-input" type="text" id="dosage" name="dosage" maxlength="100"
                                    placeholder="e.g. 500mg" value="<?= htmlspecialchars($formOld['dosage'] ?? '') ?>" data-mr-form-dosage>
                            </div>

                            <div class="mr-field is-full">
                                <label class="mr-label" for="notes">Notes</label>
                                <input class="mr-input" type="text" id="notes" name="notes" maxlength="255"
                                    placeholder="e.g. Take with breakfast" value="<?= htmlspecialchars($formOld['notes'] ?? '') ?>" data-mr-form-notes>
                            </div>
                        </div>

                        <div class="mr-field is-full">
                            <label class="mr-label">Reminder times</label>
                            <div class="mr-time-list" data-mr-time-list>
                                <!-- Populated by schedule.js (makeTimeRow) so there's one
                                     source of truth for the hour/minute/AM-PM picker markup. -->
                            </div>
                            <button type="button" class="mr-btn-secondary mr-add-time-btn" data-mr-add-time>
                                <i class="ph ph-plus"></i> Add another time
                            </button>
                        </div>

                        <div class="mr-form-actions">
                            <button type="submit" class="mr-btn" data-mr-form-submit>
                                <i class="ph ph-plus"></i> Add medicine
                            </button>
                            <button type="button" class="mr-btn-secondary" data-mr-form-cancel style="display:none">
                                Cancel edit
                            </button>
                        </div>
                    </form>

                    <div class="mr-divider"></div>

                    <!-- Medicine list -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 4">
                        <div class="mr-card-heading">Your medicines</div>
                    </div>

                    <?php if (empty($medicines)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 5">
                            <i class="ph ph-first-aid-kit"></i>
                            <p>You haven't added any medicines yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-medicine-list" data-mr-scroll-entry style="--index: 5">
                            <?php foreach ($medicines as $med): ?>
                                <article class="mr-medicine-card">
                                    <div class="mr-medicine-card-main">
                                        <div class="mr-medicine-card-name"><?= htmlspecialchars($med['name']) ?></div>
                                        <?php if (!empty($med['dosage'])): ?>
                                            <div class="mr-medicine-card-dosage"><?= htmlspecialchars($med['dosage']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($med['notes'])): ?>
                                            <div class="mr-field-hint"><?= htmlspecialchars($med['notes']) ?></div>
                                        <?php endif; ?>
                                        <div class="mr-medicine-times">
                                            <?php foreach ($med['times'] as $t): ?>
                                                <span class="mr-badge"><?= htmlspecialchars(date('g:i A', strtotime($t))) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="mr-medicine-card-actions">
                                        <button type="button" class="mr-btn-secondary"
                                            data-mr-edit-medicine
                                            data-medicine-id="<?= (int) $med['id'] ?>"
                                            data-name="<?= htmlspecialchars($med['name']) ?>"
                                            data-dosage="<?= htmlspecialchars($med['dosage'] ?? '') ?>"
                                            data-notes="<?= htmlspecialchars($med['notes'] ?? '') ?>"
                                            data-times="<?= htmlspecialchars(implode(',', $med['times'])) ?>">
                                            <i class="ph ph-pencil-simple"></i> Edit
                                        </button>
                                        <button type="button" class="mr-icon-btn"
                                            data-mr-confirm
                                            data-mr-confirm-action="schedule_controller.php?action=delete_medicine&medicine_id=<?= (int) $med['id'] ?>"
                                            data-mr-confirm-method="post"
                                            data-mr-confirm-title="Remove this medicine?"
                                            data-mr-confirm-message="This will also remove its reminder times and dose history. This can't be undone."
                                            data-mr-confirm-label="Remove"
                                            data-mr-confirm-icon="ph-trash"
                                            data-mr-confirm-variant="danger"
                                            title="Delete">
                                            <i class="ph ph-trash"></i>
                                        </button>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </section>

            </main>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="../../../shared/notifications/notifications.js"></script>
    <script src="schedule.js"></script>
</body>

</html>
