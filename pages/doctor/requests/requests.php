<?php

/**
 * requests.php
 * -----------------------------------------------------------------------
 * List of pending connection requests addressed to this doctor, each
 * with Accept/Decline. Declined requests aren't shown again here (the
 * patient sees "Declined" on their Doctors page and can't re-request —
 * see pages/user/doctors/doctors_controller.php's uniqueness check).
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

$mrRootBase = '../../../';
require_role('doctor', $mrRootBase);

$activePage = 'requests';
$doctorId = (int) $_SESSION['user_id'];

$currentUser = [
    'name'  => $_SESSION['name']  ?? 'Doctor',
    'email' => $_SESSION['email'] ?? '',
    'avatar' => null,
];

$requests = [];
$stmt = mysqli_prepare($con, "
    SELECT dc.id, dc.message, dc.requested_at, u.name, u.email, u.phone
    FROM doctor_connections dc
    JOIN users u ON u.id = dc.patient_id
    WHERE dc.doctor_id = ? AND dc.status = 'pending'
    ORDER BY dc.requested_at ASC
");
mysqli_stmt_bind_param($stmt, 'i', $doctorId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $requests[] = $row;
}
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare — pending connection requests.">
    <title>Requests · Kare for Doctors</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="requests.css">

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
                            <div class="mr-layout-header-heading">Connection <strong>Requests</strong></div>
                            <div class="mr-layout-header-sub">Patients asking to connect with you.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <?php if (empty($requests)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 0">
                            <i class="ph ph-user-plus"></i>
                            <p>No pending requests right now.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-request-list" data-mr-scroll-entry style="--index: 0">
                            <?php foreach ($requests as $r): ?>
                                <article class="mr-request-card">
                                    <div class="mr-request-card-header">
                                        <span class="mr-profile-avatar mr-request-avatar"><?= htmlspecialchars(strtoupper(substr($r['name'], 0, 1))) ?></span>
                                        <div class="mr-request-main">
                                            <div class="mr-medicine-card-name"><?= htmlspecialchars($r['name']) ?></div>
                                            <div class="mr-field-hint">
                                                <?= htmlspecialchars($r['email']) ?>
                                                <?php if (!empty($r['phone'])): ?> &middot; <?= htmlspecialchars($r['phone']) ?><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mr-field-hint"><?= htmlspecialchars(date('d M Y', strtotime($r['requested_at']))) ?></div>
                                    </div>

                                    <?php if (!empty($r['message'])): ?>
                                        <p class="mr-request-message"><?= nl2br(htmlspecialchars($r['message'])) ?></p>
                                    <?php endif; ?>

                                    <div class="mr-request-actions">
                                        <form action="requests_controller.php" method="post">
                                            <input type="hidden" name="action" value="respond_request">
                                            <input type="hidden" name="connection_id" value="<?= (int) $r['id'] ?>">
                                            <input type="hidden" name="decision" value="accepted">
                                            <button type="submit" class="mr-btn"><i class="ph ph-check"></i> Accept</button>
                                        </form>
                                        <form action="requests_controller.php" method="post">
                                            <input type="hidden" name="action" value="respond_request">
                                            <input type="hidden" name="connection_id" value="<?= (int) $r['id'] ?>">
                                            <input type="hidden" name="decision" value="declined">
                                            <button type="submit" class="mr-btn-secondary"><i class="ph ph-x"></i> Decline</button>
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
    <script src="../../../shared/notifications/notifications.js"></script>
    <script src="requests.js"></script>
</body>

</html>
