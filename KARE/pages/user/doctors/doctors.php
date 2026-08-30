<?php

/**
 * doctors.php
 * -----------------------------------------------------------------------
 * Doctor directory for the logged-in patient:
 *   - browse active doctors (name, specialty)
 *   - send a connection request, with an optional message
 *   - see pending/accepted status per doctor, cancel a pending request
 *     or disconnect from an accepted one
 *   - jump to Messages for an accepted connection
 * -----------------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];

$activePage = 'doctors';
$mrRootBase = '../../../'; // this file lives at pages/user/doctors/doctors.php

// --- All active doctors, with this patient's connection (if any) ------------
$doctors = [];
$stmt = mysqli_prepare($con, "
    SELECT u.id, u.name, u.specialty,
           dc.id AS connection_id, dc.status AS connection_status
    FROM users u
    LEFT JOIN doctor_connections dc ON dc.doctor_id = u.id AND dc.patient_id = ?
    WHERE u.role = 'doctor' AND u.status = 'active'
    ORDER BY u.name ASC
");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $doctors[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare doctors — find and connect with a doctor.">
    <title>Doctors · Kare</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="doctors.css">

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
                            <div class="mr-layout-header-heading">Find a <strong>Doctor</strong></div>
                            <div class="mr-layout-header-sub">Connect with a doctor to share your schedule and message them directly.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <?php if (empty($doctors)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 0">
                            <i class="ph ph-stethoscope"></i>
                            <p>No doctors are available right now.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-doctor-list" data-mr-scroll-entry style="--index: 0">
                            <?php foreach ($doctors as $i => $doc): ?>
                                <article class="mr-doctor-card">
                                    <div class="mr-profile-avatar mr-doctor-avatar">
                                        <?= htmlspecialchars(strtoupper(substr($doc['name'], 0, 1))) ?>
                                    </div>
                                    <div class="mr-doctor-main">
                                        <div class="mr-medicine-card-name"><?= htmlspecialchars($doc['name']) ?></div>
                                        <?php if (!empty($doc['specialty'])): ?>
                                            <div class="mr-field-hint"><?= htmlspecialchars($doc['specialty']) ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mr-doctor-actions">
                                        <?php if ($doc['connection_status'] === 'accepted'): ?>
                                            <span class="mr-badge is-success">Connected</span>
                                            <a class="mr-btn-secondary" href="../messages/messages.php?connection_id=<?= (int) $doc['connection_id'] ?>">
                                                <i class="ph ph-chat-circle-dots"></i> Message
                                            </a>
                                            <button type="button" class="mr-icon-btn"
                                                data-mr-confirm
                                                data-mr-confirm-action="doctors_controller.php?action=cancel_connection&connection_id=<?= (int) $doc['connection_id'] ?>"
                                                data-mr-confirm-method="post"
                                                data-mr-confirm-title="Disconnect from <?= htmlspecialchars($doc['name']) ?>?"
                                                data-mr-confirm-message="You'll lose access to your message history with this doctor."
                                                data-mr-confirm-label="Disconnect"
                                                data-mr-confirm-icon="ph-link-break"
                                                data-mr-confirm-variant="danger"
                                                title="Disconnect">
                                                <i class="ph ph-link-break"></i>
                                            </button>
                                        <?php elseif ($doc['connection_status'] === 'pending'): ?>
                                            <span class="mr-badge">Request pending</span>
                                            <button type="button" class="mr-icon-btn"
                                                data-mr-confirm
                                                data-mr-confirm-action="doctors_controller.php?action=cancel_connection&connection_id=<?= (int) $doc['connection_id'] ?>"
                                                data-mr-confirm-method="post"
                                                data-mr-confirm-title="Cancel this request?"
                                                data-mr-confirm-message="Your pending request to <?= htmlspecialchars($doc['name']) ?> will be withdrawn."
                                                data-mr-confirm-label="Cancel request"
                                                data-mr-confirm-icon="ph-x"
                                                title="Cancel request">
                                                <i class="ph ph-x"></i>
                                            </button>
                                        <?php elseif ($doc['connection_status'] === 'declined'): ?>
                                            <span class="mr-badge">Declined</span>
                                        <?php else: ?>
                                            <button type="button" class="mr-btn-secondary"
                                                data-mr-request-doctor
                                                data-doctor-id="<?= (int) $doc['id'] ?>"
                                                data-doctor-name="<?= htmlspecialchars($doc['name']) ?>">
                                                <i class="ph ph-plus"></i> Connect
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </section>

            </main>
        </div>
    </div>

    <!-- Connect-request dialog -->
    <div class="mr-modal-overlay" data-mr-request-overlay aria-hidden="true">
        <div class="mr-modal" role="dialog" aria-modal="true">
            <form action="doctors_controller.php" method="post">
                <input type="hidden" name="action" value="request_connection">
                <input type="hidden" name="doctor_id" value="" data-mr-request-doctor-id>

                <div class="mr-modal-title">Connect with <span data-mr-request-doctor-name></span></div>
                <p class="mr-modal-message">Send an optional note along with your request.</p>

                <div class="mr-field" style="margin: 16px 0;">
                    <textarea class="mr-input mr-textarea" name="message" rows="3" placeholder="e.g. I'd like you to review my medication schedule."></textarea>
                </div>

                <div class="mr-modal-actions">
                    <button type="button" class="mr-btn-secondary" data-mr-request-cancel>Cancel</button>
                    <button type="submit" class="mr-btn">Send request</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="doctors.js"></script>
</body>

</html>
