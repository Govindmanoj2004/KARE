<?php

/**
 * shared/doctor/navbar.php
 * -----------------------------------------------------------------------
 * Top navbar for the doctor-facing area. Same conventions as
 * shared/user/navbar.php ($currentUser, $mrRootBase), trimmed to what a
 * doctor needs: search their patients, and an avatar popover with
 * Account + Log out (no separate "view profile"/"contact support" —
 * those are patient-side concepts, folded into one Account page here).
 * -----------------------------------------------------------------------
 */

if (!isset($currentUser)) {
    $currentUser = [
        'name'  => $_SESSION['name']  ?? 'Doctor',
        'email' => $_SESSION['email'] ?? '',
        'avatar' => null,
    ];
}

$mrInitial = strtoupper(substr(trim($currentUser['name']), 0, 1) ?: 'D');

if (!isset($mrRootBase)) {
    $mrRootBase = '../../';
}
?>
<header class="mr-navbar">
    <button type="button" class="mr-sidebar-toggle" data-mr-sidebar-toggle aria-label="Toggle navigation">
        <i class="ph ph-list"></i>
    </button>

    <form class="mr-search" action="<?= htmlspecialchars($mrRootBase . 'pages/doctor/patients/patients.php') ?>" method="get" role="search">
        <i class="ph ph-magnifying-glass"></i>
        <input
            type="search"
            name="q"
            placeholder="Search your patients"
            autocomplete="off">
    </form>

    <div class="mr-navbar-spacer"></div>

    <div class="mr-navbar-actions">
        <div class="mr-notif-wrap" data-mr-notif-wrap
            data-mr-notif-fetch="<?= htmlspecialchars($mrRootBase . 'shared/notifications/notifications_fetch.php') ?>"
            data-mr-notif-mark="<?= htmlspecialchars($mrRootBase . 'shared/notifications/notifications_controller.php') ?>"
            data-mr-root-base="<?= htmlspecialchars($mrRootBase) ?>">
            <button type="button" class="mr-icon-btn" data-mr-notif-btn aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                <i class="ph ph-bell"></i>
                <span class="mr-dot" data-mr-notif-dot hidden></span>
            </button>
            <div class="mr-popover mr-notif-popover" role="menu">
                <div class="mr-notif-header">
                    <span>Notifications</span>
                    <button type="button" class="mr-notif-mark-all" data-mr-notif-mark-all>Mark all read</button>
                </div>
                <div class="mr-popover-divider"></div>
                <div class="mr-notif-list" data-mr-notif-list>
                    <div class="mr-notif-empty">Loading&hellip;</div>
                </div>
            </div>
        </div>

        <div class="mr-avatar-wrap" data-mr-avatar-wrap>
            <button
                type="button"
                class="mr-avatar-btn"
                data-mr-avatar-btn
                aria-haspopup="true"
                aria-expanded="false">
                <?php if (!empty($currentUser['avatar'])): ?>
                    <img class="mr-avatar-img" src="<?= htmlspecialchars($currentUser['avatar']) ?>" alt="">
                <?php else: ?>
                    <span class="mr-avatar-initial">
                        <?= htmlspecialchars($mrInitial) ?>
                    </span>
                <?php endif; ?>
                <i class="ph ph-caret-down mr-caret"></i>
            </button>

            <div class="mr-popover" role="menu">
                <div class="mr-popover-header">
                    <?php if (!empty($currentUser['avatar'])): ?>
                        <img class="mr-avatar-img" src="<?= htmlspecialchars($currentUser['avatar']) ?>" alt="">
                    <?php else: ?>
                        <span class="mr-avatar-initial mr-avatar-initial--lg">
                            <?= htmlspecialchars($mrInitial) ?>
                        </span>
                    <?php endif; ?>
                    <div>
                        <div class="mr-popover-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                        <?php if (!empty($currentUser['email'])): ?>
                            <div class="mr-popover-email"><?= htmlspecialchars($currentUser['email']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mr-popover-divider"></div>

                <a href="<?= htmlspecialchars($mrRootBase . 'pages/doctor/account/account.php') ?>" class="mr-popover-item" role="menuitem">
                    <i class="ph ph-gear-six"></i> Account
                </a>

                <div class="mr-popover-divider"></div>

                <button
                    type="button"
                    class="mr-popover-item is-danger"
                    role="menuitem"
                    data-mr-confirm
                    data-mr-confirm-action="<?= htmlspecialchars($mrRootBase . 'auth/logout.php') ?>"
                    data-mr-confirm-method="post"
                    data-mr-confirm-title="Log out of Kare?"
                    data-mr-confirm-message="You'll need to sign in again to access your dashboard."
                    data-mr-confirm-label="Log out"
                    data-mr-confirm-icon="ph-sign-out"
                    data-mr-confirm-variant="danger">
                    <i class="ph ph-sign-out"></i> Log out
                </button>
            </div>
        </div>
    </div>
</header>
