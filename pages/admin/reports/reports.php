<?php

/**
 * reports.php
 * -----------------------------------------------------------------------
 * Two-pane layout: list of support tickets (filterable by status) on the
 * left, selected ticket's detail + reply form on the right — same shape
 * as the messages pages' conversation-list/thread split. Replying here
 * writes straight into the columns pages/user/report/report.php already
 * knows how to render (admin_reply, replied_at), so no patient-side
 * changes were needed for this to "just work".
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

$mrRootBase = '../../../';
require_role('admin', $mrRootBase);

$activePage = 'reports';

$currentUser = [
    'name'  => $_SESSION['name']  ?? 'Admin',
    'email' => $_SESSION['email'] ?? '',
    'avatar' => null,
];

$statusFilter = $_GET['status'] ?? 'open';
$allowedStatuses = ['open', 'in_progress', 'resolved', 'closed'];

$sql = "
    SELECT r.id, r.subject, r.message, r.status, r.admin_reply, r.replied_at, r.created_at,
           u.name AS user_name, u.email AS user_email
    FROM reports r
    JOIN users u ON u.id = r.user_id
";
$params = [];
$types = '';
if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= ' WHERE r.status = ?';
    $params[] = $statusFilter;
    $types = 's';
}
$sql .= ' ORDER BY r.created_at DESC';

$stmt = mysqli_prepare($con, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reports = [];
while ($row = mysqli_fetch_assoc($result)) {
    $reports[] = $row;
}
mysqli_stmt_close($stmt);

// --- Selected report ----------------------------------------------------------
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selected = null;
foreach ($reports as $r) {
    if ((int) $r['id'] === $selectedId) {
        $selected = $r;
        break;
    }
}
if (!$selected && !empty($reports)) {
    $selected = $reports[0];
    $selectedId = (int) $selected['id'];
}

$statusLabels = [
    'open'        => 'Open',
    'in_progress' => 'In progress',
    'resolved'    => 'Resolved',
    'closed'      => 'Closed',
];
$statusBadgeClass = [
    'open'        => 'is-missed',
    'in_progress' => 'is-upcoming',
    'resolved'    => 'is-taken',
    'closed'      => 'is-taken',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare admin — support reports.">
    <title>Reports · Kare Admin</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="reports.css">

    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../../shared/toast/toast.php'; ?>
    <?php include __DIR__ . '/../../../shared/modal/modal.php'; ?>

    <div class="mr-app-shell" data-mr-app-shell>

        <?php include __DIR__ . '/../../../shared/admin/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../../shared/admin/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">Support <strong>Reports</strong></div>
                            <div class="mr-layout-header-sub">Reply to issues patients and doctors have reported.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card mr-reports-card">

                    <div class="mr-filter-row">
                        <?php foreach (['open' => 'Open', 'in_progress' => 'In progress', 'resolved' => 'Resolved', 'closed' => 'Closed', '' => 'All'] as $key => $label): ?>
                            <a href="reports.php?status=<?= $key ?>" class="mr-filter-pill<?= $statusFilter === $key ? ' is-active' : '' ?>"><?= $label ?></a>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($reports)): ?>
                        <div class="mr-schedule-empty">
                            <i class="ph ph-flag"></i>
                            <p>No reports match this filter.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-reports-layout">

                            <nav class="mr-report-list">
                                <?php foreach ($reports as $r): ?>
                                    <a href="reports.php?id=<?= (int) $r['id'] ?>&status=<?= htmlspecialchars($statusFilter) ?>"
                                        class="mr-report-item<?= (int) $r['id'] === $selectedId ? ' is-active' : '' ?>">
                                        <div class="mr-report-item-top">
                                            <span class="mr-report-subject"><?= htmlspecialchars($r['subject']) ?></span>
                                            <span class="mr-status <?= $statusBadgeClass[$r['status']] ?>"><?= $statusLabels[$r['status']] ?></span>
                                        </div>
                                        <div class="mr-field-hint"><?= htmlspecialchars($r['user_name']) ?> &middot; <?= htmlspecialchars(date('d M Y', strtotime($r['created_at']))) ?></div>
                                    </a>
                                <?php endforeach; ?>
                            </nav>

                            <div class="mr-report-detail">
                                <?php if ($selected): ?>
                                    <div class="mr-report-detail-header">
                                        <div>
                                            <div class="mr-medicine-card-name"><?= htmlspecialchars($selected['subject']) ?></div>
                                            <div class="mr-field-hint">
                                                <?= htmlspecialchars($selected['user_name']) ?> (<?= htmlspecialchars($selected['user_email']) ?>)
                                                &middot; <?= htmlspecialchars(date('d M Y, g:i A', strtotime($selected['created_at']))) ?>
                                            </div>
                                        </div>
                                        <span class="mr-status <?= $statusBadgeClass[$selected['status']] ?>"><?= $statusLabels[$selected['status']] ?></span>
                                    </div>

                                    <p class="mr-report-message"><?= nl2br(htmlspecialchars($selected['message'])) ?></p>

                                    <?php if (!empty($selected['admin_reply'])): ?>
                                        <div class="mr-report-reply-existing">
                                            <div class="mr-field-hint">
                                                Your reply &middot; <?= htmlspecialchars(date('d M Y, g:i A', strtotime($selected['replied_at']))) ?>
                                            </div>
                                            <p><?= nl2br(htmlspecialchars($selected['admin_reply'])) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <form action="reports_controller.php" method="post" class="mr-form mr-reply-form">
                                        <input type="hidden" name="action" value="reply_report">
                                        <input type="hidden" name="report_id" value="<?= (int) $selected['id'] ?>">
                                        <div class="mr-field">
                                            <label class="mr-label" for="admin_reply">
                                                <?= !empty($selected['admin_reply']) ? 'Send another reply' : 'Reply' ?>
                                            </label>
                                            <textarea class="mr-input mr-textarea" id="admin_reply" name="admin_reply" rows="4" required></textarea>
                                        </div>
                                        <div class="mr-field">
                                            <label class="mr-label" for="status">Set status to</label>
                                            <select class="mr-input mr-filter-select" id="status" name="status">
                                                <?php foreach ($statusLabels as $key => $label): ?>
                                                    <option value="<?= $key ?>" <?= $selected['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mr-form-actions">
                                            <button type="submit" class="mr-btn"><i class="ph ph-paper-plane-tilt"></i> Send reply</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </section>

            </main>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="../../../shared/notifications/notifications.js"></script>
    <script src="reports.js"></script>
</body>

</html>
