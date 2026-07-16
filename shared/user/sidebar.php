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
$mrNavGroups = [
    [
        'label' => 'Overview',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Dashboard',  'icon' => 'ph-squares-four', 'href' => 'home.php'],
            ['key' => 'schedule',  'label' => 'Schedule',    'icon' => 'ph-clock',        'href' => 'schedule.php'],
            ['key' => 'patients',  'label' => 'My Patients', 'icon' => 'ph-users',        'href' => 'patients.php'],
        ],
    ],
    [
        'label' => 'Care',
        'items' => [
            ['key' => 'prescriptions', 'label' => 'Prescriptions', 'icon' => 'ph-file-text',    'href' => 'prescriptions.php'],
            ['key' => 'doctors',       'label' => 'Doctors',       'icon' => 'ph-stethoscope',  'href' => 'doctors.php'],
            ['key' => 'messages',      'label' => 'Messages',      'icon' => 'ph-chat-circle-dots', 'href' => 'messages.php', 'badge' => 3],
        ],
    ],
    [
        'label' => 'Support',
        'items' => [
            ['key' => 'reports',  'label' => 'Reports',        'icon' => 'ph-chart-line',  'href' => 'reports.php'],
            ['key' => 'tickets',  'label' => 'Support Tickets', 'icon' => 'ph-lifebuoy',    'href' => 'tickets.php'],
            ['key' => 'settings', 'label' => 'Settings',       'icon' => 'ph-gear-six',    'href' => 'settings.php'],
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