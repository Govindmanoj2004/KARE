<?php

/**
 * patients.php
 * -----------------------------------------------------------------------
 * List of this doctor's connected (accepted) patients. For each: basic
 * contact info, a 30-day adherence rate (read-only — reuses the same
 * shape of query as pages/user/reports.php, just scoped to the patient),
 * and an expandable view of today's doses. A doctor can message or
 * disconnect from a patient here.
 *
 * Supports ?q= from the navbar search, filtering by patient name/email.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

$mrRootBase = '../../../';
require_role('doctor', $mrRootBase);

$activePage = 'patients';
$doctorId = (int) $_SESSION['user_id'];

$currentUser = [
    'name'  => $_SESSION['name']  ?? 'Doctor',
    'email' => $_SESSION['email'] ?? '',
    'avatar' => null,
];

$query = trim($_GET['q'] ?? '');

// --- Connected patients -------------------------------------------------------
$sql = "
    SELECT dc.id AS connection_id, u.id AS patient_id, u.name, u.email, u.phone
    FROM doctor_connections dc
    JOIN users u ON u.id = dc.patient_id
    WHERE dc.doctor_id = ? AND dc.status = 'accepted'
";
$params = [$doctorId];
$types = 'i';

if ($query !== '') {
    $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $query . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
$sql .= ' ORDER BY u.name ASC';

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$patients = [];
while ($row = mysqli_fetch_assoc($result)) {
    $patients[] = $row;
}
mysqli_stmt_close($stmt);

// --- Per-patient adherence (last 30 days) + today's doses --------------------
// Small doctor patient-lists make N+1 queries here an acceptable
// tradeoff for readability at this project's scope.
foreach ($patients as &$p) {    $adherenceStmt = mysqli_prepare($con, "
        SELECT SUM(dl.status = 'taken') AS taken, SUM(dl.status = 'missed') AS missed
        FROM dose_logs dl
        JOIN medicine_schedules ms ON ms.id = dl.schedule_id
        JOIN medicines m ON m.id = ms.medicine_id
        WHERE m.user_id = ? AND dl.scheduled_for >= (CURDATE() - INTERVAL 30 DAY)
          AND dl.status IN ('taken', 'missed')
    ");
    mysqli_stmt_bind_param($adherenceStmt, 'i', $p['patient_id']);
    mysqli_stmt_execute($adherenceStmt);
    $a = mysqli_fetch_assoc(mysqli_stmt_get_result($adherenceStmt));
    mysqli_stmt_close($adherenceStmt);
    $taken = (int) ($a['taken'] ?? 0);
    $missed = (int) ($a['missed'] ?? 0);
    $p['adherence'] = ($taken + $missed) > 0 ? round(($taken / ($taken + $missed)) * 100) : null;

    $todayStmt = mysqli_prepare($con, "
        SELECT m.name, m.dosage, dl.scheduled_for, dl.status
        FROM dose_logs dl
        JOIN medicine_schedules ms ON ms.id = dl.schedule_id
        JOIN medicines m ON m.id = ms.medicine_id
        WHERE m.user_id = ? AND DATE(dl.scheduled_for) = CURDATE()
        ORDER BY dl.scheduled_for ASC
    ");
    mysqli_stmt_bind_param($todayStmt, 'i', $p['patient_id']);
    mysqli_stmt_execute($todayStmt);
    $todayResult = mysqli_stmt_get_result($todayStmt);
    $p['today'] = [];
    while ($row = mysqli_fetch_assoc($todayResult)) {
        $p['today'][] = $row;
    }
    mysqli_stmt_close($todayStmt);

    // Private note this doctor has on this patient (never shown to the patient).
    $noteStmt = mysqli_prepare($con, 'SELECT note, updated_at FROM doctor_patient_notes WHERE doctor_id = ? AND patient_id = ? LIMIT 1');
    mysqli_stmt_bind_param($noteStmt, 'ii', $doctorId, $p['patient_id']);
    mysqli_stmt_execute($noteStmt);
    $noteRow = mysqli_fetch_assoc(mysqli_stmt_get_result($noteStmt));
    mysqli_stmt_close($noteStmt);
    $p['note'] = $noteRow['note'] ?? '';
    $p['note_updated_at'] = $noteRow['updated_at'] ?? null;
}
unset($p);

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
    <meta name="description" content="Kare — your connected patients.">
    <title>My Patients · Kare for Doctors</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="patients.css">

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
                            <div class="mr-layout-header-heading">My <strong>Patients</strong></div>
                            <div class="mr-layout-header-sub">
                                <?= $query !== '' ? 'Results for “' . htmlspecialchars($query) . '”' : "Patients you're connected with." ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <?php if (empty($patients)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 0">
                            <i class="ph ph-users-three"></i>
                            <p><?= $query !== '' ? 'No patients match “' . htmlspecialchars($query) . '”.' : "You don't have any connected patients yet." ?></p>
                        </div>
                    <?php else: ?>
                        <div class="mr-patient-list" data-mr-scroll-entry style="--index: 0">
                            <?php foreach ($patients as $i => $p): ?>
                                <article class="mr-patient-card">
                                    <div class="mr-patient-card-header">
                                        <span class="mr-profile-avatar mr-request-avatar"><?= htmlspecialchars(strtoupper(substr($p['name'], 0, 1))) ?></span>
                                        <div class="mr-request-main">
                                            <div class="mr-medicine-card-name"><?= htmlspecialchars($p['name']) ?></div>
                                            <div class="mr-field-hint">
                                                <?= htmlspecialchars($p['email']) ?>
                                                <?php if (!empty($p['phone'])): ?> &middot; <?= htmlspecialchars($p['phone']) ?><?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($p['adherence'] !== null): ?>
                                            <span class="mr-badge <?= $p['adherence'] >= 80 ? 'is-success' : '' ?>"><?= $p['adherence'] ?>% adherence</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mr-patient-actions">
                                        <button type="button" class="mr-btn-secondary" data-mr-toggle-today>
                                            <i class="ph ph-calendar-check"></i> Today's doses
                                        </button>
                                        <button type="button" class="mr-btn-secondary" data-mr-toggle-notes>
                                            <i class="ph ph-note-pencil"></i> Notes<?= $p['note'] !== '' ? ' •' : '' ?>
                                        </button>
                                        <a class="mr-btn-secondary" href="../messages/messages.php?connection_id=<?= (int) $p['connection_id'] ?>">
                                            <i class="ph ph-chat-circle-dots"></i> Message
                                        </a>
                                        <button type="button" class="mr-icon-btn"
                                            data-mr-confirm
                                            data-mr-confirm-action="patients_controller.php?action=disconnect_patient&connection_id=<?= (int) $p['connection_id'] ?>"
                                            data-mr-confirm-method="post"
                                            data-mr-confirm-title="Disconnect from <?= htmlspecialchars($p['name']) ?>?"
                                            data-mr-confirm-message="You'll lose access to their schedule and message history."
                                            data-mr-confirm-label="Disconnect"
                                            data-mr-confirm-icon="ph-link-break"
                                            data-mr-confirm-variant="danger"
                                            title="Disconnect">
                                            <i class="ph ph-link-break"></i>
                                        </button>
                                    </div>

                                    <div class="mr-patient-today" data-mr-today-panel hidden>
                                        <?php if (empty($p['today'])): ?>
                                            <p class="mr-field-hint">No doses scheduled for today.</p>
                                        <?php else: ?>
                                            <table class="mr-table">
                                                <thead>
                                                    <tr>
                                                        <th>Medicine</th>
                                                        <th>Time</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($p['today'] as $dose): $s = $statusLabels[$dose['status']]; ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($dose['name'] . ($dose['dosage'] ? ' ' . $dose['dosage'] : '')) ?></td>
                                                            <td><?= htmlspecialchars(date('g:i A', strtotime($dose['scheduled_for']))) ?></td>
                                                            <td><span class="mr-status <?= $s['class'] ?>"><?= $s['text'] ?></span></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mr-patient-notes" data-mr-notes-panel hidden>
                                        <form action="patients_controller.php" method="post" class="mr-form">
                                            <input type="hidden" name="action" value="save_note">
                                            <input type="hidden" name="patient_id" value="<?= (int) $p['patient_id'] ?>">
                                            <textarea class="mr-input mr-textarea" name="note" rows="3"
                                                placeholder="Private notes only you can see — not shared with the patient."><?= htmlspecialchars($p['note']) ?></textarea>
                                            <div class="mr-form-actions">
                                                <button type="submit" class="mr-btn-secondary"><i class="ph ph-check"></i> Save note</button>
                                                <?php if (!empty($p['note_updated_at'])): ?>
                                                    <span class="mr-field-hint">Last updated <?= htmlspecialchars(date('d M Y, g:i A', strtotime($p['note_updated_at']))) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </form>
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
    <script src="patients.js"></script>
</body>

</html>
