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
$stmt = mysqli_prepare($con, 'SELECT id, title, doctor_name, notes, file_path, file_original_name, uploaded_at
                               FROM prescriptions WHERE user_id = ? ORDER BY uploaded_at DESC');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $prescriptions[] = $row;
}
mysqli_stmt_close($stmt);

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

                    <!-- Upload form -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 0">
                        <div class="mr-card-heading">Upload a prescription</div>
                    </div>

                    <form action="prescriptions_controller.php" method="post" class="mr-form" enctype="multipart/form-data" data-mr-scroll-entry style="--index: 1">
                        <input type="hidden" name="action" value="upload_prescription">

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
                                <span class="mr-field-hint">PDF, JPG, or PNG — 2MB max.</span>
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
                                        <div class="mr-medicine-card-name"><?= htmlspecialchars($p['title']) ?></div>
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

                </section>

            </main>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="prescriptions.js"></script>
</body>

</html>
