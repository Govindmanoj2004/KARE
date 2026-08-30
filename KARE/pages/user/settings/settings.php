<?php

/**
 * settings.php
 * -----------------------------------------------------------------------
 * Account settings: notification preferences + deactivate account.
 * Re-queries the users table fresh (same convention as profile.php)
 * rather than trusting session data for anything beyond name/email.
 * -----------------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];

$activePage = 'settings';
$mrRootBase = '../../../'; // this file lives at pages/user/settings/settings.php

$stmt = mysqli_prepare($con, 'SELECT name, email, notify_email, notify_sms, created_at FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$currentUser = [
    'name'   => $user['name'] ?? ($_SESSION['name'] ?? 'Guest Caretaker'),
    'email'  => $user['email'] ?? ($_SESSION['email'] ?? ''),
    'avatar' => null,
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare account settings — notification preferences and account controls.">
    <title>Settings · Kare</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="settings.css">

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
                            <div class="mr-layout-header-heading">Account <strong>Settings</strong></div>
                            <div class="mr-layout-header-sub">Manage how Kare reaches you, and your account itself.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <!-- Notification preferences -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 0">
                        <div class="mr-card-heading">Notification preferences</div>
                    </div>

                    <form action="settings_controller.php" method="post" class="mr-form" data-mr-scroll-entry style="--index: 1">
                        <input type="hidden" name="action" value="update_notifications">

                        <label class="mr-toggle-row">
                            <span>
                                <span class="mr-toggle-label">Email reminders</span>
                                <span class="mr-field-hint">Get dose and account updates at <?= htmlspecialchars($currentUser['email']) ?></span>
                            </span>
                            <input type="checkbox" name="notify_email" class="mr-toggle" <?= !empty($user['notify_email']) ? 'checked' : '' ?>>
                        </label>

                        <label class="mr-toggle-row">
                            <span>
                                <span class="mr-toggle-label">SMS reminders</span>
                                <span class="mr-field-hint">Get a text message when a dose is due</span>
                            </span>
                            <input type="checkbox" name="notify_sms" class="mr-toggle" <?= !empty($user['notify_sms']) ? 'checked' : '' ?>>
                        </label>

                        <div class="mr-form-actions">
                            <button type="submit" class="mr-btn"><i class="ph ph-check"></i> Save preferences</button>
                        </div>
                    </form>

                    <div class="mr-divider"></div>

                    <!-- Account info -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 2">
                        <div class="mr-card-heading">Account</div>
                    </div>

                    <div class="mr-field-hint" data-mr-scroll-entry style="--index: 3; margin-bottom: 20px;">
                        <?= htmlspecialchars($currentUser['name']) ?> &middot; <?= htmlspecialchars($currentUser['email']) ?>
                        <?php if (!empty($user['created_at'])): ?>
                            &middot; Member since <?= htmlspecialchars(date('d M Y', strtotime($user['created_at']))) ?>
                        <?php endif; ?>
                    </div>

                    <a href="../profile/profile.php" class="mr-btn-secondary" data-mr-scroll-entry style="--index: 4; margin-bottom: 8px;">
                        <i class="ph ph-user"></i> Edit profile details
                    </a>

                    <div class="mr-divider"></div>

                    <!-- Danger zone -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 5">
                        <div class="mr-card-heading" style="color: var(--danger);">Danger zone</div>
                    </div>

                    <div class="mr-danger-zone" data-mr-scroll-entry style="--index: 6">
                        <p class="mr-field-hint">
                            Deactivating your account signs you out and prevents further logins until support
                            reactivates it. Your data is kept, not deleted.
                        </p>

                        <form action="settings_controller.php" method="post" class="mr-form mr-deactivate-form" data-mr-deactivate-form>
                            <input type="hidden" name="action" value="deactivate_account">
                            <div class="mr-field">
                                <label class="mr-label" for="confirm_password">Confirm your password to continue</label>
                                <input class="mr-input" type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="mr-btn is-danger-btn">
                                <i class="ph ph-warning"></i> Deactivate account
                            </button>
                        </form>
                    </div>

                </section>

            </main>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="settings.js"></script>
</body>

</html>
