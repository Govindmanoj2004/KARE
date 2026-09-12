<?php

/**
 * account.php
 * -----------------------------------------------------------------------
 * Combines the patient side's profile.php + settings.php into one page
 * for doctors: edit details (name, phone, specialty), change password,
 * notification preferences, and account deactivation.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

$mrRootBase = '../../../';
require_role('doctor', $mrRootBase);

$activePage = 'account';
$doctorId = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare($con, 'SELECT name, email, phone, specialty, notify_email, notify_sms, created_at FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $doctorId);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$currentUser = [
    'name'  => $doctor['name']  ?? ($_SESSION['name'] ?? 'Doctor'),
    'email' => $doctor['email'] ?? ($_SESSION['email'] ?? ''),
    'avatar' => null,
];

$formOld = $_SESSION['account_old'] ?? null;
unset($_SESSION['account_old']);

$nameValue = $formOld['name'] ?? $doctor['name'];
$phoneValue = $formOld['phone'] ?? $doctor['phone'];
$specialtyValue = $formOld['specialty'] ?? $doctor['specialty'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare — your doctor account.">
    <title>Account · Kare for Doctors</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="account.css">

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
                            <div class="mr-layout-header-heading">Your <strong>Account</strong></div>
                            <div class="mr-layout-header-sub">Profile, password, and notification preferences.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <div class="mr-profile-header" data-mr-scroll-entry style="--index: 0">
                        <span class="mr-profile-avatar"><?= htmlspecialchars(strtoupper(substr($currentUser['name'], 0, 1))) ?></span>
                        <div>
                            <div class="mr-medicine-card-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                            <div class="mr-field-hint"><?= htmlspecialchars($currentUser['email']) ?></div>
                            <span class="mr-badge is-success">Doctor</span>
                            <?php if (!empty($doctor['specialty'])): ?>
                                <span class="mr-badge"><?= htmlspecialchars($doctor['specialty']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mr-divider"></div>

                    <!-- Edit details -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 1">
                        <div class="mr-card-heading">Edit details</div>
                    </div>

                    <form action="account_controller.php" method="post" class="mr-form" data-mr-scroll-entry style="--index: 2">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mr-form-grid">
                            <div class="mr-field">
                                <label class="mr-label" for="name">Full name</label>
                                <input class="mr-input" type="text" id="name" name="name" value="<?= htmlspecialchars($nameValue) ?>" required>
                            </div>
                            <div class="mr-field">
                                <label class="mr-label" for="phone">Phone</label>
                                <input class="mr-input" type="text" id="phone" name="phone" value="<?= htmlspecialchars($phoneValue) ?>" required>
                            </div>
                            <div class="mr-field is-full">
                                <label class="mr-label" for="specialty">Specialty</label>
                                <input class="mr-input" type="text" id="specialty" name="specialty" placeholder="e.g. Cardiologist" value="<?= htmlspecialchars($specialtyValue ?? '') ?>">
                            </div>
                        </div>
                        <div class="mr-form-actions">
                            <button type="submit" class="mr-btn"><i class="ph ph-check"></i> Save changes</button>
                        </div>
                    </form>

                    <div class="mr-divider"></div>

                    <!-- Change password -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 3">
                        <div class="mr-card-heading">Change password</div>
                    </div>

                    <form action="account_controller.php" method="post" class="mr-form" data-mr-scroll-entry style="--index: 4">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mr-form-grid">
                            <div class="mr-field is-full">
                                <label class="mr-label" for="current_password">Current password</label>
                                <input class="mr-input" type="password" id="current_password" name="current_password" required>
                            </div>
                            <div class="mr-field">
                                <label class="mr-label" for="new_password">New password</label>
                                <input class="mr-input" type="password" id="new_password" name="new_password" minlength="8" required>
                            </div>
                            <div class="mr-field">
                                <label class="mr-label" for="confirm_password">Confirm new password</label>
                                <input class="mr-input" type="password" id="confirm_password" name="confirm_password" minlength="8" required>
                            </div>
                        </div>
                        <div class="mr-form-actions">
                            <button type="submit" class="mr-btn-secondary"><i class="ph ph-lock-key"></i> Update password</button>
                        </div>
                    </form>

                    <div class="mr-divider"></div>

                    <!-- Notification preferences -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 5">
                        <div class="mr-card-heading">Notification preferences</div>
                    </div>

                    <form action="account_controller.php" method="post" class="mr-form" data-mr-scroll-entry style="--index: 6">
                        <input type="hidden" name="action" value="update_notifications">
                        <label class="mr-toggle-row">
                            <span>
                                <span class="mr-toggle-label">Email notifications</span>
                                <span class="mr-field-hint">New connection requests and messages</span>
                            </span>
                            <input type="checkbox" name="notify_email" class="mr-toggle" <?= !empty($doctor['notify_email']) ? 'checked' : '' ?>>
                        </label>
                        <label class="mr-toggle-row">
                            <span>
                                <span class="mr-toggle-label">SMS notifications</span>
                                <span class="mr-field-hint">Get a text for new connection requests</span>
                            </span>
                            <input type="checkbox" name="notify_sms" class="mr-toggle" <?= !empty($doctor['notify_sms']) ? 'checked' : '' ?>>
                        </label>
                        <div class="mr-form-actions">
                            <button type="submit" class="mr-btn"><i class="ph ph-check"></i> Save preferences</button>
                        </div>
                    </form>

                    <div class="mr-divider"></div>

                    <!-- Danger zone -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 7">
                        <div class="mr-card-heading" style="color: var(--danger);">Danger zone</div>
                    </div>

                    <div class="mr-danger-zone" data-mr-scroll-entry style="--index: 8">
                        <p class="mr-field-hint">
                            Deactivating your account signs you out and prevents further logins until support
                            reactivates it. Your patients' data is kept, not deleted.
                        </p>
                        <form action="account_controller.php" method="post" class="mr-form mr-deactivate-form" data-mr-deactivate-form>
                            <input type="hidden" name="action" value="deactivate_account">
                            <div class="mr-field">
                                <label class="mr-label" for="deactivate_password">Confirm your password to continue</label>
                                <input class="mr-input" type="password" id="deactivate_password" name="confirm_password" required>
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
    <script src="../../../shared/notifications/notifications.js"></script>
    <script src="account.js"></script>
</body>

</html>
