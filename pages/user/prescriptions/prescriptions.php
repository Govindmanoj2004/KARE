<?php

/**
 * prescriptions.php
 * -----------------------------------------------------------------------
 * Upload and browse prescription files (PDF/JPG/PNG, 2MB max).
 * No OCR/text-extraction here — that's flagged as a separate, not-yet-
 * built feature in CHANGELOG.md; this page just stores and lists files.
 *
 * Files live under assets/uploads/prescriptions/{user_id}/{random}.{ext}
 * and are linked to directly rather than through an access-gated PHP
 * streaming script — the random 32-hex-char filename is effectively
 * unguessable, an accepted tradeoff for this project's scope (a
 * production system would stream through a script that re-checks
 * session ownership on every request instead).
 * -----------------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];

$activePage = 'prescriptions';
$mrRootBase = '../../../'; // this file lives at pages/user/prescriptions/prescriptions.php

$prescriptions = [];
$stmt = mysqli_prepare($con, 'SELECT id, issued_by, is_current, title, doctor_name, notes, file_path, file_original_name, uploaded_at
                               FROM prescriptions WHERE user_id = ? ORDER BY is_current DESC, uploaded_at DESC');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $prescriptions[] = $row;
}
mysqli_stmt_close($stmt);

// The one "current" prescription, if any (to-do #13) — shown as a callout
// above the full list, which still contains it too.
$currentPrescription = null;
foreach ($prescriptions as $p) {
    if ($p['is_current']) {
        $currentPrescription = $p;
        break;
    }
}

// --- Connected (accepted) doctors, for the "request an update" form --------
$connectedDoctors = [];
$docStmt = mysqli_prepare($con, "
    SELECT dc.id AS connection_id, u.name
    FROM doctor_connections dc
    JOIN users u ON u.id = dc.doctor_id
    WHERE dc.patient_id = ? AND dc.status = 'accepted'
    ORDER BY u.name ASC
");
mysqli_stmt_bind_param($docStmt, 'i', $userId);
mysqli_stmt_execute($docStmt);
$docResult = mysqli_stmt_get_result($docStmt);
while ($row = mysqli_fetch_assoc($docResult)) {
    $connectedDoctors[] = $row;
}
mysqli_stmt_close($docStmt);

// --- Requests FROM this patient (asking a doctor for an update) ------------
$sentRequests = [];
$sentStmt = mysqli_prepare($con, "
    SELECT pr.*, u.name AS doctor_name
    FROM prescription_requests pr
    JOIN doctor_connections dc ON dc.id = pr.connection_id
    JOIN users u ON u.id = dc.doctor_id
    WHERE dc.patient_id = ? AND pr.requested_by = 'patient'
    ORDER BY pr.requested_at DESC
    LIMIT 20
");
mysqli_stmt_bind_param($sentStmt, 'i', $userId);
mysqli_stmt_execute($sentStmt);
$sentResult = mysqli_stmt_get_result($sentStmt);
while ($row = mysqli_fetch_assoc($sentResult)) {
    $sentRequests[] = $row;
}
mysqli_stmt_close($sentStmt);

// --- Requests FROM a doctor asking THIS patient for an update --------------
$incomingAsks = [];
$askStmt = mysqli_prepare($con, "
    SELECT pr.*, u.name AS doctor_name
    FROM prescription_requests pr
    JOIN doctor_connections dc ON dc.id = pr.connection_id
    JOIN users u ON u.id = dc.doctor_id
    WHERE dc.patient_id = ? AND pr.requested_by = 'doctor' AND pr.status = 'pending'
    ORDER BY pr.requested_at DESC
");
mysqli_stmt_bind_param($askStmt, 'i', $userId);
mysqli_stmt_execute($askStmt);
$askResult = mysqli_stmt_get_result($askStmt);
while ($row = mysqli_fetch_assoc($askResult)) {
    $incomingAsks[] = $row;
}
mysqli_stmt_close($askStmt);

$requestStatusLabels = [
    'pending'   => ['class' => 'is-upcoming', 'text' => 'Pending'],
    'fulfilled' => ['class' => 'is-taken',    'text' => 'Fulfilled'],
    'declined'  => ['class' => 'is-missed',   'text' => 'Declined'],
];

$requestOld = $_SESSION['prescription_request_old'] ?? null;
unset($_SESSION['prescription_request_old']);


$formOld = $_SESSION['prescription_old'] ?? null;
unset($_SESSION['prescription_old']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare prescriptions — upload and keep track of your prescriptions.">
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

        <?php include __DIR__ . '/../../../shared/user/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../../shared/user/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">Your <strong>Prescriptions</strong></div>
                            <div class="mr-layout-header-sub">Keep copies of your prescriptions in one place.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <?php if ($currentPrescription): ?>
                        <div class="mr-current-prescription" data-mr-scroll-entry style="--index: 0">
                            <span class="mr-prescription-icon">
                                <i class="ph <?= strtolower(pathinfo($currentPrescription['file_path'], PATHINFO_EXTENSION)) === 'pdf' ? 'ph-file-pdf' : 'ph-image' ?>"></i>
                            </span>
                            <div class="mr-current-prescription-main">
                                <div class="mr-field-hint">CURRENT PRESCRIPTION</div>
                                <div class="mr-medicine-card-name"><?= htmlspecialchars($currentPrescription['title']) ?></div>
                                <div class="mr-field-hint">
                                    <?php if (!empty($currentPrescription['doctor_name'])): ?>
                                        <?= htmlspecialchars($currentPrescription['doctor_name']) ?> &middot;
                                    <?php endif; ?>
                                    <?= $currentPrescription['issued_by'] === 'doctor' ? 'Issued by your doctor' : 'Uploaded by you' ?>
                                    &middot; <?= htmlspecialchars(date('d M Y', strtotime($currentPrescription['uploaded_at']))) ?>
                                </div>
                            </div>
                            <a class="mr-btn-secondary" href="../../../<?= htmlspecialchars($currentPrescription['file_path']) ?>" target="_blank" rel="noopener">
                                <i class="ph ph-eye"></i> View
                            </a>
                        </div>
                        <div class="mr-divider"></div>
                    <?php endif; ?>

                    <!-- Upload form -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 0">
                        <div class="mr-card-heading">Upload a prescription</div>
                    </div>

                    <form action="prescriptions_controller.php" method="post" class="mr-form" enctype="multipart/form-data" data-mr-scroll-entry style="--index: 1" data-mr-upload-form>
                        <input type="hidden" name="action" value="upload_prescription">
                        <input type="hidden" name="fulfill_request_id" value="" data-mr-fulfill-ask-id>

                        <div class="mr-form-grid">
                            <div class="mr-field">
                                <label class="mr-label" for="title">Title</label>
                                <input class="mr-input" type="text" id="title" name="title" maxlength="150"
                                    placeholder="e.g. Dr. Menon — Aug checkup" value="<?= htmlspecialchars($formOld['title'] ?? '') ?>" required>
                            </div>

                            <div class="mr-field">
                                <label class="mr-label" for="doctor_name">Doctor's name</label>
                                <input class="mr-input" type="text" id="doctor_name" name="doctor_name" maxlength="150"
                                    placeholder="Optional" value="<?= htmlspecialchars($formOld['doctor_name'] ?? '') ?>">
                            </div>

                            <div class="mr-field is-full">
                                <label class="mr-label" for="notes">Notes</label>
                                <input class="mr-input" type="text" id="notes" name="notes" maxlength="255"
                                    placeholder="Optional" value="<?= htmlspecialchars($formOld['notes'] ?? '') ?>">
                            </div>

                            <div class="mr-field is-full">
                                <label class="mr-label" for="file">File</label>
                                <input class="mr-input" type="file" id="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <span class="mr-field-hint" data-mr-upload-hint>PDF, JPG, or PNG — 2MB max.</span>
                            </div>
                        </div>

                        <div class="mr-form-actions">
                            <button type="submit" class="mr-btn"><i class="ph ph-upload-simple"></i> Upload</button>
                        </div>
                    </form>

                    <div class="mr-divider"></div>

                    <!-- Prescription list -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 2">
                        <div class="mr-card-heading">Your prescriptions</div>
                    </div>

                    <?php if (empty($prescriptions)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 3">
                            <i class="ph ph-file-text"></i>
                            <p>You haven't uploaded any prescriptions yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-prescription-list" data-mr-scroll-entry style="--index: 3">
                            <?php foreach ($prescriptions as $p): ?>
                                <?php $ext = strtolower(pathinfo($p['file_path'], PATHINFO_EXTENSION)); ?>
                                <article class="mr-prescription-card">
                                    <span class="mr-prescription-icon">
                                        <i class="ph <?= $ext === 'pdf' ? 'ph-file-pdf' : 'ph-image' ?>"></i>
                                    </span>
                                    <div class="mr-prescription-main">
                                        <div class="mr-medicine-card-name">
                                            <?= htmlspecialchars($p['title']) ?>
                                            <?php if ($p['is_current']): ?>
                                                <span class="mr-status is-taken">Current</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mr-field-hint">
                                            <?php if (!empty($p['doctor_name'])): ?>
                                                <?= htmlspecialchars($p['doctor_name']) ?> &middot;
                                            <?php endif; ?>
                                            Uploaded <?= htmlspecialchars(date('d M Y', strtotime($p['uploaded_at']))) ?>
                                        </div>
                                        <?php if (!empty($p['notes'])): ?>
                                            <div class="mr-field-hint"><?= htmlspecialchars($p['notes']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mr-medicine-card-actions">
                                        <a class="mr-btn-secondary" href="../../../<?= htmlspecialchars($p['file_path']) ?>" target="_blank" rel="noopener">
                                            <i class="ph ph-eye"></i> View
                                        </a>
                                        <a class="mr-icon-btn" href="../../../<?= htmlspecialchars($p['file_path']) ?>" download="<?= htmlspecialchars($p['file_original_name']) ?>" title="Download">
                                            <i class="ph ph-download-simple"></i>
                                        </a>
                                        <button type="button" class="mr-icon-btn"
                                            data-mr-confirm
                                            data-mr-confirm-action="prescriptions_controller.php?action=delete_prescription&prescription_id=<?= (int) $p['id'] ?>"
                                            data-mr-confirm-method="post"
                                            data-mr-confirm-title="Remove this prescription?"
                                            data-mr-confirm-message="The uploaded file will be permanently deleted."
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

                    <div class="mr-divider"></div>

                    <!-- Prescription requests (to-do #9/#13/#14) -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 4">
                        <div class="mr-card-heading">Prescription requests</div>
                        <?php if (!empty($connectedDoctors)): ?>
                            <button type="button" class="mr-btn-secondary" data-mr-request-update-btn>
                                <i class="ph ph-paper-plane-tilt"></i> Request an update
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($incomingAsks)): ?>
                        <div class="mr-presc-request-list" data-mr-scroll-entry style="--index: 5">
                            <?php foreach ($incomingAsks as $ask): ?>
                                <div class="mr-presc-request-card">
                                    <div class="mr-presc-request-main">
                                        <div class="mr-medicine-card-name"><?= htmlspecialchars($ask['doctor_name']) ?> is asking for an update</div>
                                        <div class="mr-field-hint">&ldquo;<?= htmlspecialchars($ask['message']) ?>&rdquo;</div>
                                        <div class="mr-field-hint">Requested <?= htmlspecialchars(date('d M Y', strtotime($ask['requested_at']))) ?></div>
                                    </div>
                                    <div class="mr-presc-request-actions">
                                        <button type="button" class="mr-btn-secondary" data-mr-fulfill-ask-btn data-request-id="<?= (int) $ask['id'] ?>">
                                            <i class="ph ph-upload-simple"></i> Upload update
                                        </button>
                                        <button type="button" class="mr-icon-btn"
                                            data-mr-confirm
                                            data-mr-confirm-action="prescriptions_controller.php?action=decline_ask&request_id=<?= (int) $ask['id'] ?>"
                                            data-mr-confirm-method="post"
                                            data-mr-confirm-title="Dismiss this request?"
                                            data-mr-confirm-message="Your doctor will see this as declined."
                                            data-mr-confirm-label="Dismiss"
                                            data-mr-confirm-variant="danger"
                                            title="Dismiss">
                                            <i class="ph ph-x"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($sentRequests) && empty($incomingAsks)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 5">
                            <i class="ph ph-paper-plane-tilt"></i>
                            <p><?= empty($connectedDoctors) ? 'Connect with a doctor to request a prescription update.' : 'No requests yet.' ?></p>
                        </div>
                    <?php elseif (!empty($sentRequests)): ?>
                        <div class="mr-presc-request-list" data-mr-scroll-entry style="--index: 6">
                            <?php foreach ($sentRequests as $req): $s = $requestStatusLabels[$req['status']]; ?>
                                <div class="mr-presc-request-card">
                                    <div class="mr-presc-request-main">
                                        <div class="mr-medicine-card-name"><?= htmlspecialchars($req['doctor_name']) ?></div>
                                        <div class="mr-field-hint">&ldquo;<?= htmlspecialchars($req['message']) ?>&rdquo;</div>
                                        <div class="mr-field-hint">
                                            Sent <?= htmlspecialchars(date('d M Y', strtotime($req['requested_at']))) ?>
                                            <?php if ($req['fee_amount'] !== null): ?>
                                                &middot; Fee: $<?= number_format((float) $req['fee_amount'], 2) ?>
                                                <?= $req['fee_paid'] ? '<span class="mr-status is-taken">Paid</span>' : '<span class="mr-status is-upcoming">Unpaid</span>' ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mr-presc-request-side">
                                        <span class="mr-status <?= $s['class'] ?>"><?= $s['text'] ?></span>
                                        <?php if ($req['fee_amount'] !== null && !$req['fee_paid']): ?>
                                            <form action="prescriptions_controller.php" method="post">
                                                <input type="hidden" name="action" value="pay_fee">
                                                <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                                                <button type="submit" class="mr-btn-secondary">
                                                    <i class="ph ph-credit-card"></i> Mark as paid
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </section>

            </main>
        </div>
    </div>

    <!-- Request-update dialog -->
    <div class="mr-modal-overlay" data-mr-request-update-overlay aria-hidden="true">
        <div class="mr-modal">
            <form action="prescriptions_controller.php" method="post">
                <input type="hidden" name="action" value="request_update">

                <div class="mr-modal-title">Request a prescription update</div>
                <p class="mr-modal-message">Choose a connected doctor and describe what you need.</p>

                <div class="mr-field" style="margin: 16px 0;">
                    <label class="mr-label" for="request_connection_id">Doctor</label>
                    <select class="mr-input" id="request_connection_id" name="connection_id" required>
                        <?php foreach ($connectedDoctors as $d): ?>
                            <option value="<?= (int) $d['connection_id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mr-field" style="margin-bottom: 16px;">
                    <label class="mr-label" for="request_message">Message</label>
                    <textarea class="mr-input mr-textarea" id="request_message" name="message" rows="3" required
                        placeholder="e.g. My dosage needs to change, please issue an updated prescription."><?= htmlspecialchars($requestOld['message'] ?? '') ?></textarea>
                </div>

                <div class="mr-modal-actions">
                    <button type="button" class="mr-btn-secondary" data-mr-request-update-cancel>Cancel</button>
                    <button type="submit" class="mr-btn">Send request</button>
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
