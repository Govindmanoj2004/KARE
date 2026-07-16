<?php

/**
 * profile.php
 * -----------------------------------------------------------------------
 * Logged-in user's profile page.
 *   - Shows name / email / phone / role / status pulled fresh from the DB.
 *   - "Edit details" form updates name, email, phone.
 *   - "Change password" form updates the password (plain text — entry-level
 *     project scope, same as the rest of the auth flow).
 * Both forms post to profile_controller.php using the PRG + toast pattern
 * used everywhere else in the app.
 * -----------------------------------------------------------------------
 */
session_start();

// --- Auth guard -----------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

// --- Fetch fresh user data from the DB (session may be stale) -------------
$stmt = mysqli_prepare($con, 'SELECT id, name, email, phone, role, status FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profileUser = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// If the account somehow no longer exists, force a fresh login.
if (!$profileUser) {
    session_destroy();
    header('Location: ../../../auth/login/index.php');
    exit;
}

$activePage = 'profile';
$mrRootBase = '../../../'; // this file lives at pages/user/profile/profile.php

$currentUser = [
    'name'   => $profileUser['name'],
    'email'  => $profileUser['email'],
    'avatar' => null,
];

// Repopulate the edit form with old input after a failed submission.
$formOld = $_SESSION['profile_old'] ?? null;
unset($_SESSION['profile_old']);

$nameValue  = $formOld['name']  ?? $profileUser['name'];
$emailValue = $formOld['email'] ?? $profileUser['email'];
$phoneValue = $formOld['phone'] ?? $profileUser['phone'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare profile — view and update your account details.">
    <title>Your Profile · Kare</title>

    <!-- Design System (order matters: tokens > base > components > page) -->
    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="profile.css">

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
                            <div class="mr-layout-header-heading">Your <strong>Profile</strong></div>
                            <div class="mr-layout-header-sub">View and manage your account details.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <!-- Profile summary -->
                    <div class="mr-profile-header" data-mr-scroll-entry>
                        <div class="mr-profile-avatar">
                            <?= htmlspecialchars(strtoupper(substr(trim($profileUser['name']), 0, 1) ?: 'U')) ?>
                        </div>
                        <div>
                            <div class="mr-profile-name"><?= htmlspecialchars($profileUser['name']) ?></div>
                            <div class="mr-profile-email"><?= htmlspecialchars($profileUser['email']) ?></div>
                            <div class="mr-profile-badges">
                                <span class="mr-badge"><i class="ph ph-identification-badge"></i> <?= htmlspecialchars($profileUser['role']) ?></span>
                                <span class="mr-badge is-success"><i class="ph ph-check-circle"></i> <?= htmlspecialchars($profileUser['status']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mr-divider"></div>

                    <!-- Edit details -->
                    <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 1">
                        <div class="mr-card-heading">Edit details</div>
                    </div>

                    <form action="profile_controller.php" method="post" class="mr-form" data-mr-scroll-entry style="--index: 2">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="mr-form-grid">
                            <div class="mr-field">
                                <label class="mr-label" for="name">Full name</label>
                                <input class="mr-input" type="text" id="name" name="name" value="<?= htmlspecialchars($nameValue) ?>" required>
                            </div>

                            <div class="mr-field">
                                <label class="mr-label" for="email">Email address</label>
                                <input class="mr-input" type="email" id="email" name="email" value="<?= htmlspecialchars($emailValue) ?>" required>
                            </div>

                            <div class="mr-field">
                                <label class="mr-label" for="phone">Phone number</label>
                                <input class="mr-input" type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phoneValue) ?>" required>
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

                    <form action="profile_controller.php" method="post" class="mr-form" data-mr-scroll-entry style="--index: 4">
                        <input type="hidden" name="action" value="change_password">

                        <div class="mr-form-grid">
                            <div class="mr-field is-full">
                                <label class="mr-label" for="current_password">Current password</label>
                                <input class="mr-input" type="password" id="current_password" name="current_password" placeholder="Enter your current password" required>
                            </div>

                            <div class="mr-field">
                                <label class="mr-label" for="new_password">New password</label>
                                <input class="mr-input" type="password" id="new_password" name="new_password" placeholder="At least 8 characters" required>
                            </div>

                            <div class="mr-field">
                                <label class="mr-label" for="confirm_password">Confirm new password</label>
                                <input class="mr-input" type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required>
                            </div>
                        </div>

                        <div class="mr-form-actions">
                            <button type="submit" class="mr-btn-secondary"><i class="ph ph-lock-key"></i> Update password</button>
                        </div>
                    </form>

                </section>

            </main>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="profile.js"></script>
</body>

</html>