<?php

/**
 * shared/doctor/sidebar.php
 * -----------------------------------------------------------------------
 * Sidebar for the doctor-facing area. Same conventions as
 * shared/user/sidebar.php: set $activePage + optionally $mrRootBase
 * before including. Nav hrefs are built as
 * $mrRootBase . 'pages/doctor/' . $item['href'].
 * -----------------------------------------------------------------------
 */

if (!isset($activePage)) {
    $activePage = '';
}

if (!isset($mrRootBase)) {
    $mrRootBase = '../../';
}

// Real pending-request and unread-message counts for badges, when a DB
// connection + session are already in scope (see shared/user/sidebar.php
// for the same pattern).
$mrPendingRequests = 0;
$mrUnreadMessages = 0;
if (isset($con) && isset($_SESSION['user_id'])) {
    $mrDoctorId = (int) $_SESSION['user_id'];

    $mrPendingStmt = mysqli_prepare($con, "SELECT COUNT(*) AS c FROM doctor_connections WHERE doctor_id = ? AND status = 'pending'");
    if ($mrPendingStmt) {
        mysqli_stmt_bind_param($mrPendingStmt, 'i', $mrDoctorId);
        mysqli_stmt_execute($mrPendingStmt);
        $mrPendingRequests = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($mrPendingStmt))['c'] ?? 0);
        mysqli_stmt_close($mrPendingStmt);
    }

    $mrUnreadStmt = mysqli_prepare($con, "
        SELECT COUNT(*) AS c
        FROM messages m
        JOIN doctor_connections dc ON dc.id = m.connection_id
        WHERE dc.doctor_id = ? AND m.sender_id != ? AND m.read_at IS NULL
    ");
    if ($mrUnreadStmt) {
        mysqli_stmt_bind_param($mrUnreadStmt, 'ii', $mrDoctorId, $mrDoctorId);
        mysqli_stmt_execute($mrUnreadStmt);
        $mrUnreadMessages = (int) (mysqli_fetch_assoc(mysqli_stmt_get_result($mrUnreadStmt))['c'] ?? 0);
        mysqli_stmt_close($mrUnreadStmt);
    }
}

$mrNavGroups = [
    [
        'label' => 'Overview',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'ph-squares-four', 'href' => 'home.php'],
            ['key' => 'requests',  'label' => 'Requests',   'icon' => 'ph-user-plus',    'href' => 'requests/requests.php', 'badge' => $mrPendingRequests > 0 ? $mrPendingRequests : null],
        ],
    ],
    [
        'label' => 'Care',
        'items' => [
            ['key' => 'patients', 'label' => 'My Patients', 'icon' => 'ph-users',            'href' => 'patients/patients.php'],
            ['key' => 'messages', 'label' => 'Messages',    'icon' => 'ph-chat-circle-dots', 'href' => 'messages/messages.php', 'badge' => $mrUnreadMessages > 0 ? $mrUnreadMessages : null],
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
                            href="<?= htmlspecialchars($mrRootBase . 'pages/doctor/' . $item['href']) ?>"
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
            data-mr-confirm-message="You'll need to sign in again to access your dashboard."
            data-mr-confirm-label="Log out"
            data-mr-confirm-icon="ph-sign-out"
            data-mr-confirm-variant="danger">
            <i class="ph ph-sign-out"></i>
            <span>Log out</span>
        </a>
    </div>
</aside>
