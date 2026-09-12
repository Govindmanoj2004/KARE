<?php

/**
 * pages/doctor/prescriptions/prescriptions.php
 * -----------------------------------------------------------------------
 * Prescription request workflow (to-do #9/#13/#14), doctor side:
 *   - Requests from connected patients asking for a prescription update.
 *     Fulfilling one uploads a new prescription file for that patient
 *     (same validation as the patient's own upload) and marks it as
 *     their current prescription. A fee can optionally be attached —
 *     recorded, not gated behind a real payment processor (out of scope
 *     for this project, see README §7/§8).
 *   - This doctor's own outgoing requests (asking a patient to update
 *     their prescription on file) and their status.
 *   - A small form to start a new outgoing request to a connected patient.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

$mrRootBase = '../../../';
require_role('doctor', $mrRootBase);

$activePage = 'prescriptions';
$doctorId = (int) $_SESSION['user_id'];

$currentUser = [
    'name'  => $_SESSION['name']  ?? 'Doctor',
    'email' => $_SESSION['email'] ?? '',
    'avatar' => null,
];

// --- Connected (accepted) patients, for the "ask for an update" form -------
$patients = [];
$patStmt = mysqli_prepare($con, "
    SELECT dc.id AS connection_id, u.id AS patient_id, u.name
    FROM doctor_connections dc
    JOIN users u ON u.id = dc.patient_id
    WHERE dc.doctor_id = ? AND dc.status = 'accepted'
    ORDER BY u.name ASC
");
mysqli_stmt_bind_param($patStmt, 'i', $doctorId);
mysqli_stmt_execute($patStmt);
$patResult = mysqli_stmt_get_result($patStmt);
while ($row = mysqli_fetch_assoc($patResult)) {
    $patients[] = $row;
}
mysqli_stmt_close($patStmt);

// --- Requests FROM patients, needing this doctor's action -------------------
$incoming = [];
$inStmt = mysqli_prepare($con, "
    SELECT pr.*, u.name AS patient_name
    FROM prescription_requests pr
    JOIN doctor_connections dc ON dc.id = pr.connection_id
    JOIN users u ON u.id = dc.patient_id
    WHERE dc.doctor_id = ? AND pr.requested_by = 'patient'
    ORDER BY (pr.status = 'pending') DESC, pr.requested_at DESC
    LIMIT 30
");
mysqli_stmt_bind_param($inStmt, 'i', $doctorId);
mysqli_stmt_execute($inStmt);
$inResult = mysqli_stmt_get_result($inStmt);
while ($row = mysqli_fetch_assoc($inResult)) {
    $incoming[] = $row;
}
mysqli_stmt_close($inStmt);

// --- Requests this doctor sent TO patients, with their status ---------------
$outgoing = [];
$outStmt = mysqli_prepare($con, "
    SELECT pr.*, u.name AS patient_name
    FROM prescription_requests pr
    JOIN doctor_connections dc ON dc.id = pr.connection_id
    JOIN users u ON u.id = dc.patient_id
    WHERE dc.doctor_id = ? AND pr.requested_by = 'doctor'
    ORDER BY pr.requested_at DESC
    LIMIT 30
");
mysqli_stmt_bind_param($outStmt, 'i', $doctorId);
mysqli_stmt_execute($outStmt);
$outResult = mysqli_stmt_get_result($outStmt);
while ($row = mysqli_fetch_assoc($outResult)) {
    $outgoing[] = $row;
}
mysqli_stmt_close($outStmt);

$statusLabels = [
    'pending'   => ['class' => 'is-upcoming', 'text' => 'Pending'],
    'fulfilled' => ['class' => 'is-taken',    'text' => 'Fulfilled'],
    'declined'  => ['class' => 'is-missed',   'text' => 'Declined'],
];

$formOld = $_SESSION['prescription_request_old'] ?? null;
unset($_SESSION['prescription_request_old']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare — prescription requests with your patients.">
    <title>Prescriptions · Kare</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="prescriptions.css">

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
                            <div class="mr-layout-header-heading">Prescription <strong>Requests</strong></div>
                            <div class="mr-layout-header-sub">Fulfill update requests from your patients, or ask one for an update.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 0">
                        <div class="mr-card-heading">Requests from patients</div>
                    </div>

                    <?php if (empty($incoming)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 1">
                            <i class="ph ph-file-text"></i>
                            <p>No prescription requests yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-presc-request-list" data-mr-scroll-entry style="--index: 1">
                            <?php foreach ($incoming as $req): $s = $statusLabels[$req['status']]; ?>
                                <div class="mr-presc-request-card">
                                    <div class="mr-presc-request-main">
                                        <div class="mr-medicine-card-name"><?= htmlspecialchars($req['patient_name']) ?></div>
                                        <div class="mr-field-hint">&ldquo;<?= htmlspecialchars($req['message']) ?>&rdquo;</div>
                                        <div class="mr-field-hint">
                                            Requested <?= htmlspecialchars(date('d M Y, g:i A', strtotime($req['requested_at']))) ?>
                                            <?php if ($req['fee_amount'] !== null): ?>
                                                &middot; Fee: $<?= number_format((float) $req['fee_amount'], 2) ?>
                                                <?= $req['fee_paid'] ? '<span class="mr-status is-taken">Paid</span>' : '<span class="mr-status is-upcoming">Unpaid</span>' ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mr-presc-request-side">
                                        <span class="mr-status <?= $s['class'] ?>"><?= $s['text'] ?></span>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <div class="mr-presc-request-actions">
                                                <button type="button" class="mr-btn-secondary" data-mr-fulfill-btn
                                                    data-request-id="<?= (int) $req['id'] ?>"
                                                    data-patient-name="<?= htmlspecialchars($req['patient_name']) ?>">
                                                    <i class="ph ph-check"></i> Fulfill
                                                </button>
                                                <button type="button" class="mr-icon-btn"
                                                    data-mr-confirm
                                                    data-mr-confirm-action="prescriptions_controller.php?action=decline_request&request_id=<?= (int) $req['id'] ?>"
                                                    data-mr-confirm-method="post"
                                                    data-mr-confirm-title="Decline this request?"
                                                    data-mr-confirm-message="The patient will see this request as declined."
                                                    data-mr-confirm-label="Decline"
                                                    data-mr-confirm-variant="danger"
                                                    title="Decline">
                                                    <i class="ph ph-x"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mr-divider"></div>

                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 2">
                        <div class="mr-card-heading">Ask a patient for an update</div>
                    </div>

                    <?php if (empty($patients)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 3">
                            <i class="ph ph-users"></i>
                            <p>Connect with a patient first to request a prescription update.</p>
                        </div>
                    <?php else: ?>
                        <form class="mr-form-grid" action="prescriptions_controller.php" method="post" data-mr-scroll-entry style="--index: 3">
                            <input type="hidden" name="action" value="request_update">
                            <div class="mr-field">
                                <label class="mr-label" for="patient_id">Patient</label>
                                <select class="mr-input" id="patient_id" name="connection_id" required>
                                    <?php foreach ($patients as $p): ?>
                                        <option value="<?= (int) $p['connection_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mr-field is-full">
                                <label class="mr-label" for="message">Message</label>
                                <input class="mr-input" type="text" id="message" name="message" maxlength="255" required
                                    placeholder="e.g. Please upload your latest prescription for review."
                                    value="<?= htmlspecialchars($formOld['message'] ?? '') ?>">
                            </div>
                            <div class="mr-form-actions">
                                <button type="submit" class="mr-btn"><i class="ph ph-paper-plane-tilt"></i> Send request</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (!empty($outgoing)): ?>
                        <div class="mr-divider"></div>
                        <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 4">
                            <div class="mr-card-heading">Your sent requests</div>
                        </div>
                        <div class="mr-table-wrap" data-mr-scroll-entry style="--index: 5">
                            <table class="mr-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Message</th>
                                        <th>Sent</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($outgoing as $req): $s = $statusLabels[$req['status']]; ?>
                                        <tr>
                                            <td><?= htmlspecialchars($req['patient_name']) ?></td>
                                            <td><?= htmlspecialchars($req['message']) ?></td>
                                            <td><?= htmlspecialchars(date('d M Y', strtotime($req['requested_at']))) ?></td>
                                            <td><span class="mr-status <?= $s['class'] ?>"><?= $s['text'] ?></span></td>
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

    <!-- Fulfill-request dialog: upload the new prescription + optional fee -->
    <div class="mr-modal-overlay" data-mr-fulfill-overlay aria-hidden="true">
        <div class="mr-modal">
            <form action="prescriptions_controller.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="fulfill_request">
                <input type="hidden" name="request_id" value="" data-mr-fulfill-request-id>

                <div class="mr-modal-title">Fulfill request from <span data-mr-fulfill-patient-name></span></div>
                <p class="mr-modal-message">Upload the new prescription. This becomes the patient's current prescription.</p>

                <div class="mr-field" style="margin: 16px 0;">
                    <label class="mr-label" for="fulfill_title">Title</label>
                    <input class="mr-input" type="text" id="fulfill_title" name="title" maxlength="150" required placeholder="e.g. Updated diabetes prescription">
                </div>
                <div class="mr-field" style="margin-bottom: 16px;">
                    <label class="mr-label" for="fulfill_fee">Fee (optional, USD)</label>
                    <input class="mr-input" type="number" id="fulfill_fee" name="fee_amount" min="0" step="0.01" placeholder="Leave blank if there's no charge">
                </div>
                <div class="mr-field" style="margin-bottom: 16px;">
                    <label class="mr-label" for="fulfill_file">File (PDF, JPG, or PNG — 2MB max)</label>
                    <input class="mr-input" type="file" id="fulfill_file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <div class="mr-modal-actions">
                    <button type="button" class="mr-btn-secondary" data-mr-fulfill-cancel>Cancel</button>
                    <button type="submit" class="mr-btn">Send prescription</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="../../../shared/notifications/notifications.js"></script>
    <script src="prescriptions.js"></script>
</body>

</html>
