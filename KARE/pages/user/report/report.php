<?php

/**
 * report.php
 * -----------------------------------------------------------------------
 * Logged-in user's "Report an issue" page.
 *   - Submit a new report (subject + message) to the admin.
 *   - See all reports they've filed, each with its status and — once
 *     the admin module answers it — the admin's reply and reply time.
 * Posts to report_controller.php using the same PRG + toast pattern as
 * the rest of the app.
 * -----------------------------------------------------------------------
 */
session_start();

// --- Auth guard -----------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];

$activePage = 'report';
$mrRootBase = '../../../'; // this file lives at pages/user/report/report.php

// --- Fetch this user's reports, most recent first --------------------------
$reports = [];
$stmt = mysqli_prepare($con, 'SELECT id, subject, message, status, admin_reply, replied_at, created_at
                               FROM reports
                               WHERE user_id = ?
                               ORDER BY created_at DESC');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $reports[] = $row;
}
mysqli_stmt_close($stmt);

// Repopulate the form with old input after a failed submission.
$formOld = $_SESSION['report_old'] ?? null;
unset($_SESSION['report_old']);

$subjectValue = $formOld['subject'] ?? '';
$messageValue = $formOld['message'] ?? '';

// --- Small helpers for the status badge ------------------------------------
$statusLabels = [
    'open'        => 'Open',
    'in_progress' => 'In Progress',
    'resolved'    => 'Resolved',
    'closed'      => 'Closed',
];

$statusBadgeClass = [
    'open'        => 'is-open',
    'in_progress' => 'is-progress',
    'resolved'    => 'is-success',
    'closed'      => 'is-closed',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare reports — file an issue and track admin replies.">
    <title>Report an Issue · Kare</title>

    <!-- Design System (order matters: tokens > base > components > page) -->
    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="report.css">

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
                            <div class="mr-layout-header-heading">Report an <strong>Issue</strong></div>
                            <div class="mr-layout-header-sub">Let the admin know if something's wrong — track replies here.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <!-- New report form -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 1">
                        <div class="mr-card-heading">Submit a new report</div>
                    </div>

                    <form action="report_controller.php" method="post" class="mr-form" data-mr-scroll-entry style="--index: 2">
                        <input type="hidden" name="action" value="create_report">

                        <div class="mr-field">
                            <label class="mr-label" for="subject">Subject</label>
                            <input class="mr-input" type="text" id="subject" name="subject" maxlength="150"
                                placeholder="Short summary of the issue" value="<?= htmlspecialchars($subjectValue) ?>" required>
                        </div>

                        <div class="mr-field">
                            <label class="mr-label" for="message">Details</label>
                            <textarea class="mr-input mr-textarea" id="message" name="message" rows="5"
                                placeholder="Describe what happened, what you expected, and any steps to reproduce it."
                                required><?= htmlspecialchars($messageValue) ?></textarea>
                        </div>

                        <div class="mr-form-actions">
                            <button type="submit" class="mr-btn"><i class="ph ph-paper-plane-tilt"></i> Submit report</button>
                        </div>
                    </form>

                    <div class="mr-divider"></div>

                    <!-- Report history -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 3">
                        <div class="mr-card-heading">Your reports</div>
                    </div>

                    <?php if (empty($reports)): ?>
                        <div class="mr-report-empty" data-mr-scroll-entry style="--index: 4">
                            <i class="ph ph-clipboard-text"></i>
                            <p>You haven't reported anything yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-report-list" data-mr-scroll-entry style="--index: 4">
                            <?php foreach ($reports as $i => $report): ?>
                                <?php
                                $status = $report['status'];
                                $badgeClass = $statusBadgeClass[$status] ?? 'is-open';
                                $badgeLabel = $statusLabels[$status] ?? ucfirst($status);
                                ?>
                                <article class="mr-report-item">
                                    <div class="mr-report-item-header">
                                        <div class="mr-report-item-subject"><?= htmlspecialchars($report['subject']) ?></div>
                                        <span class="mr-badge <?= $badgeClass ?>"><?= htmlspecialchars($badgeLabel) ?></span>
                                    </div>

                                    <div class="mr-report-item-meta">
                                        <i class="ph ph-clock"></i>
                                        Filed <?= htmlspecialchars(date('d M Y, g:i A', strtotime($report['created_at']))) ?>
                                    </div>

                                    <p class="mr-report-item-message"><?= nl2br(htmlspecialchars($report['message'])) ?></p>

                                    <?php if (!empty($report['admin_reply'])): ?>
                                        <div class="mr-report-reply">
                                            <div class="mr-report-reply-header">
                                                <i class="ph ph-shield-check"></i>
                                                <span>Admin reply</span>
                                                <?php if (!empty($report['replied_at'])): ?>
                                                    <span class="mr-report-reply-time">
                                                        <?= htmlspecialchars(date('d M Y, g:i A', strtotime($report['replied_at']))) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p><?= nl2br(htmlspecialchars($report['admin_reply'])) ?></p>
                                        </div>
                                    <?php endif; ?>
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
    <script src="report.js"></script>
</body>

</html>