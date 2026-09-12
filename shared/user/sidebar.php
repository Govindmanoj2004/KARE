<?php

/**
 * includes/sidebar.php
 * -----------------------------------------------------------------------
 * Reusable page-navigation sidebar for the caretaker/patient area.
 *
 * USAGE
 *   Before including this file, set:
 *     $activePage = 'dashboard';   // must match one of the 'key' values below
 *
 *   <?php $activePage = 'dashboard'; include __DIR__ . '/includes/sidebar.php'; ?>
 *
 * ADDING A NEW SIDEBAR ITEM
 *   Just add another array entry to $mrNavGroups below — nothing else in
 *   this file needs to change. 'badge' is optional (e.g. unread count).
 * -----------------------------------------------------------------------
 */

if (!isset($activePage)) {
    $activePage = '';
}

// Relative path from the CURRENT file's folder back to the project root.
// Pages directly in pages/user/ (e.g. home.php) should set '../../'.
// Pages nested one level deeper (e.g. pages/user/profile/profile.php)
// should set '../../../', and so on. Defaults to the common case.
if (!isset($mrRootBase)) {
    $mrRootBase = '../../';
}

// ---------------------------------------------------------------------
// Nav data. In production, badges (e.g. unread messages, open tickets)
// would be populated from the database rather than hard-coded.
// ---------------------------------------------------------------------
// Real unread-message count for the sidebar badge, when possible.
// Falls back to no badge if the DB connection isn't in scope yet (some
// pages include sidebar.php before requiring Connection.php).
$mrUnreadMessages = 0;
if (isset($con) && isset($_SESSION['user_id'])) {
    $mrUnreadStmt = mysqli_prepare($con, "
        SELECT COUNT(*) AS c
        FROM messages m
        JOIN doctor_connections dc ON dc.id = m.connection_id
        WHERE dc.patient_id = ? AND m.sender_id != ? AND m.read_at IS NULL
    ");
    if ($mrUnreadStmt) {
        $mrSessionUserId = (int) $_SESSION['user_id'];
        mysqli_stmt_bind_param($mrUnreadStmt, 'ii', $mrSessionUserId, $mrSessionUserId);
        mysqli_stmt_execute($mrUnreadStmt);
        $mrUnreadRow = mysqli_fetch_assoc(mysqli_stmt_get_result($mrUnreadStmt));
        $mrUnreadMessages = (int) ($mrUnreadRow['c'] ?? 0);
        mysqli_stmt_close($mrUnreadStmt);
    }
}

$mrNavGroups = [
    [
        'label' => 'Overview',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Dashboard',  'icon' => 'ph-squares-four', 'href' => 'home.php'],
            ['key' => 'schedule',  'label' => 'Schedule',    'icon' => 'ph-clock',        'href' => 'schedule/schedule.php'],
        ],
    ],
    [
        'label' => 'Care',
        'items' => [
            ['key' => 'prescriptions', 'label' => 'Prescriptions', 'icon' => 'ph-file-text',    'href' => 'prescriptions/prescriptions.php'],
            ['key' => 'doctors',       'label' => 'Doctors',       'icon' => 'ph-stethoscope',  'href' => 'doctors/doctors.php'],
            ['key' => 'messages',      'label' => 'Messages',      'icon' => 'ph-chat-circle-dots', 'href' => 'messages/messages.php', 'badge' => $mrUnreadMessages > 0 ? $mrUnreadMessages : null],
            ['key' => 'payments',      'label' => 'Payments',      'icon' => 'ph-receipt',      'href' => 'payments/payments.php'],
        ],
    ],
    [
        'label' => 'Support',
        'items' => [
            ['key' => 'reports',  'label' => 'Reports',        'icon' => 'ph-chart-line',  'href' => 'reports.php'],
            ['key' => 'report',  'label' => 'Report Issue', 'icon' => 'ph-flag',    'href' => 'report/report.php'],
            ['key' => 'settings', 'label' => 'Settings',       'icon' => 'ph-gear-six',    'href' => 'settings/settings.php'],
        ],
    ],
];
?>
<aside class="mr-sidebar" data-mr-sidebar>
    <div class="mr-sidebar-brand">
        <!-- <span class="mr-sidebar-brand-mark">
            <img src="../../assets/svg/logo.svg" alt="" width="18" height="18">
        </span> -->
        <span class="mr-sidebar-brand-name">Kare</span>
    </div>

    <nav>
        <?php foreach ($mrNavGroups as $group): ?>
            <div class="mr-sidebar-section-label"><?= htmlspecialchars($group['label']) ?></div>
            <ul class="mr-sidebar-nav">
                <?php foreach ($group['items'] as $item): ?>
                    <li>
                        <a
                            href="<?= htmlspecialchars($mrRootBase . 'pages/user/' . $item['href']) ?>"
                            class="mr-nav-item<?= $activePage === $item['key'] ? ' is-active' : '' ?>"
                            <?= $activePage === $item['key'] ? 'aria-current="page"' : '' ?>>
                            <i class="ph <?= htmlspecialchars($item['icon']) ?>"></i>
                            <span><?= htmlspecialchars($item['label']) ?></span>
                            <?php if (!empty($item['badge'])): ?>
                                <span class="mr-nav-badge"><?= (int) $item['badge'] ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </nav>

    <div class="mr-sidebar-footer">
        <a href="<?= htmlspecialchars($mrRootBase . 'pages/user/help.php') ?>" class="mr-nav-item">
            <i class="ph ph-question"></i>
            <span>Help &amp; FAQ</span>
        </a>
    </div>
</aside>