<?php

/**
 * includes/navbar.php
 * -----------------------------------------------------------------------
 * Reusable top navbar: mobile sidebar toggle, search, notifications,
 * and an avatar button that opens a popover (profile options + logout).
 *
 * EXPECTS (set these before including, or wire up to your session/auth):
 *   $currentUser = [
 *     'name'   => 'Reji Mathew',
 *     'email'  => 'reji@example.com',
 *     'avatar' => 'assets/img/avatar.jpg', // optional, falls back to initial
 *   ];
 * -----------------------------------------------------------------------
 */

if (!isset($currentUser)) {
    // Fallback so the component never breaks if included on its own.
    $currentUser = [
        'name'  => $_SESSION['name']  ?? 'Guest Caretaker',
        'email' => $_SESSION['email'] ?? '',
        'avatar' => null,
    ];
}

$mrInitial = strtoupper(substr(trim($currentUser['name']), 0, 1) ?: 'U');

// See shared/user/sidebar.php for an explanation of $mrRootBase.
if (!isset($mrRootBase)) {
    $mrRootBase = '../../';
}
?>
<header class="mr-navbar">
    <button type="button" class="mr-sidebar-toggle" data-mr-sidebar-toggle aria-label="Toggle navigation">
        <i class="ph ph-list"></i>
    </button>

    <form class="mr-search" action="<?= htmlspecialchars($mrRootBase . 'pages/user/search.php') ?>" method="get" role="search">
        <i class="ph ph-magnifying-glass"></i>
        <input
            type="search"
            name="q"
            placeholder="Search patients, medicines, prescriptions"
            autocomplete="off">
    </form>

    <div class="mr-navbar-spacer"></div>

    <div class="mr-navbar-actions">
        <button type="button" class="mr-icon-btn" aria-label="Notifications">
            <i class="ph ph-bell"></i>
            <span class="mr-dot"></span>
        </button>

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

                <a href="<?= htmlspecialchars($mrRootBase . 'pages/user/profile/profile.php') ?>" class="mr-popover-item" role="menuitem">
                    <i class="ph ph-user"></i> View profile
                </a>
                <a href="<?= htmlspecialchars($mrRootBase . 'pages/user/settings.php') ?>" class="mr-popover-item" role="menuitem">
                    <i class="ph ph-gear-six"></i> Account settings
                </a>
                <a href="<?= htmlspecialchars($mrRootBase . 'pages/user/tickets.php?new=1') ?>" class="mr-popover-item" role="menuitem">
                    <i class="ph ph-lifebuoy"></i> Contact support
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