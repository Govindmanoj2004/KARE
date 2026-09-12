<?php

/**
 * shared/admin/sidebar.php
 * -----------------------------------------------------------------------
 * Sidebar for the admin-facing area. Same conventions as
 * shared/doctor/sidebar.php: set $activePage + optionally $mrRootBase
 * before including.
 * -----------------------------------------------------------------------
 */

if (!isset($activePage)) {
    $activePage = '';
}

if (!isset($mrRootBase)) {
    $mrRootBase = '../../';
}

// Real badge counts (pending reports, unverified doctors) when a DB
// connection + session are already in scope — same pattern as the
// patient/doctor sidebars.
$mrOpenReports = 0;
$mrUnverifiedDoctors = 0;
if (isset($con) && isset($_SESSION['user_id'])) {
    $mrReportsStmt = mysqli_prepare($con, "SELECT COUNT(*) AS c FROM reports WHERE status = 'open'");
    if ($mrReportsStmt) {
        mysqli_stmt_execute($mrReportsStmt);
        $mrOpenReports = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($mrReportsStmt))['c'] ?? 0);
        mysqli_stmt_close($mrReportsStmt);
    }

    $mrDoctorsStmt = mysqli_prepare($con, "SELECT COUNT(*) AS c FROM users WHERE role = 'doctor' AND is_verified = 0");
    if ($mrDoctorsStmt) {
        mysqli_stmt_execute($mrDoctorsStmt);
        $mrUnverifiedDoctors = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($mrDoctorsStmt))['c'] ?? 0);
        mysqli_stmt_close($mrDoctorsStmt);
    }
}

$mrNavGroups = [
    [
        'label' => 'Overview',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'href' => 'home.php'],
        ],
    ],
    [
        'label' => 'Management',
        'items' => [
            ['key' => 'users',   'label' => 'Users',   'icon' => 'ph-users-three', 'href' => 'users/users.php', 'badge' => $mrUnverifiedDoctors > 0 ? $mrUnverifiedDoctors : null],
            ['key' => 'reports', 'label' => 'Reports',  'icon' => 'ph-flag',        'href' => 'reports/reports.php', 'badge' => $mrOpenReports > 0 ? $mrOpenReports : null],
            ['key' => 'payments', 'label' => 'Financial Stats', 'icon' => 'ph-chart-line',    'href' => 'payments/payments.php'],
        ],
    ],
    [
        'label' => 'Support',
        'items' => [
            ['key' => 'account', 'label' => 'Account', 'icon' => 'ph-gear-six', 'href' => 'account/account.php'],
        ],
    ],
];
?>
<aside class="mr-sidebar" data-mr-sidebar>
    <div class="mr-sidebar-brand">
        <span class="mr-sidebar-brand-name">Kare</span>
    </div>

    <nav>
        <?php foreach ($mrNavGroups as $group): ?>
            <div class="mr-sidebar-section-label"><?= htmlspecialchars($group['label']) ?></div>
            <ul class="mr-sidebar-nav">
                <?php foreach ($group['items'] as $item): ?>
                    <li>
                        <a
                            href="<?= htmlspecialchars($mrRootBase . 'pages/admin/' . $item['href']) ?>"
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
        <a href="<?= htmlspecialchars($mrRootBase . 'auth/logout.php') ?>" class="mr-nav-item"
            data-mr-confirm
            data-mr-confirm-action="<?= htmlspecialchars($mrRootBase . 'auth/logout.php') ?>"
            data-mr-confirm-method="post"
            data-mr-confirm-title="Log out of Kare?"
            data-mr-confirm-message="You'll need to sign in again to access the admin portal."
            data-mr-confirm-label="Log out"
            data-mr-confirm-icon="ph-sign-out"
            data-mr-confirm-variant="danger">
            <i class="ph ph-sign-out"></i>
            <span>Log out</span>
        </a>
    </div>
</aside>
