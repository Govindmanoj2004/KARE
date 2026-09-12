<?php

/**
 * users.php
 * -----------------------------------------------------------------------
 * All users (patients + doctors + admins), with search (?q=, also fed by
 * the navbar search), role filter (?role=), and status filter
 * (?status=). Each row: quick status-change action appropriate to its
 * current status, plus a verify/unverify toggle for doctor rows.
 * -----------------------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../../../assets/connection/Connection.php';
require_once __DIR__ . '/../../../assets/helpers/auth.php';

$mrRootBase = '../../../';
require_role('admin', $mrRootBase);

$activePage = 'users';
$adminId = (int) $_SESSION['user_id'];

$currentUser = [
    'name'  => $_SESSION['name']  ?? 'Admin',
    'email' => $_SESSION['email'] ?? '',
    'avatar' => null,
];

$query = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$allowedRoles = ['patient', 'doctor', 'admin'];
$allowedStatuses = ['active', 'suspended', 'deactivated'];

$sql = "SELECT id, name, email, phone, role, status, is_verified, created_at FROM users WHERE 1=1";
$params = [];
$types = '';

if ($query !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ?)';
    $like = '%' . $query . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if (in_array($roleFilter, $allowedRoles, true)) {
    $sql .= ' AND role = ?';
    $params[] = $roleFilter;
    $types .= 's';
}
if (in_array($statusFilter, $allowedStatuses, true)) {
    $sql .= ' AND status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}
$sql .= ' ORDER BY created_at DESC';

$stmt = mysqli_prepare($con, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}
mysqli_stmt_close($stmt);

$statusBadge = [
    'active'      => 'is-taken',
    'suspended'   => 'is-missed',
    'deactivated' => 'is-upcoming',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare admin — manage users.">
    <title>Users · Kare Admin</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="../../../shared/toast/toast.css">
    <link rel="stylesheet" href="users.css">

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
                            <div class="mr-layout-header-heading">Manage <strong>Users</strong></div>
                            <div class="mr-layout-header-sub">
                                <?= $query !== '' ? 'Results for “' . htmlspecialchars($query) . '”' : 'All patients, doctors, and admins.' ?>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <!-- Filters -->
                    <form method="get" class="mr-filter-row" data-mr-scroll-entry style="--index: 0">
                        <?php if ($query !== ''): ?>
                            <input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>">
                        <?php endif; ?>
                        <select name="role" class="mr-input mr-filter-select" onchange="this.form.submit()">
                            <option value="">All roles</option>
                            <?php foreach ($allowedRoles as $r): ?>
                                <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= ucfirst($r) ?>s</option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" class="mr-input mr-filter-select" onchange="this.form.submit()">
                            <option value="">All statuses</option>
                            <?php foreach ($allowedStatuses as $s): ?>
                                <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($query !== '' || $roleFilter !== '' || $statusFilter !== ''): ?>
                            <a href="users.php" class="mr-btn-secondary">Clear filters</a>
                        <?php endif; ?>
                    </form>

                    <?php if (empty($users)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 1">
                            <i class="ph ph-users-three"></i>
                            <p>No users match these filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="mr-table-wrap" data-mr-scroll-entry style="--index: 1">
                            <table class="mr-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($u['name']) ?>
                                                <?php if ((int) $u['id'] === $adminId): ?>
                                                    <span class="mr-field-hint">(you)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <?= ucfirst($u['role']) ?>
                                                <?php if ($u['role'] === 'doctor'): ?>
                                                    <?php if ($u['is_verified']): ?>
                                                        <span class="mr-badge is-success">Verified</span>
                                                    <?php else: ?>
                                                        <span class="mr-badge">Unverified</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="mr-status <?= $statusBadge[$u['status']] ?>"><?= ucfirst($u['status']) ?></span></td>
                                            <td>
                                                <div class="mr-user-actions">
                                                    <?php if ((int) $u['id'] !== $adminId): ?>
                                                        <?php if ($u['status'] === 'active'): ?>
                                                            <form action="users_controller.php" method="post">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                                                <input type="hidden" name="status" value="suspended">
                                                                <button type="submit" class="mr-btn-secondary">Suspend</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form action="users_controller.php" method="post">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                                                <input type="hidden" name="status" value="active">
                                                                <button type="submit" class="mr-btn-secondary">Reactivate</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    <?php endif; ?>

                                                    <?php if ($u['role'] === 'doctor'): ?>
                                                        <form action="users_controller.php" method="post">
                                                            <input type="hidden" name="action" value="toggle_verified">
                                                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                                            <button type="submit" class="mr-btn-secondary">
                                                                <?= $u['is_verified'] ? 'Unverify' : 'Verify' ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </section>

            </main>
        </div>
    </div>

    <script src="../../../shared/toast/toast.js"></script>
    <script src="../../../shared/modal/modal.js"></script>
    <script src="../../../shared/notifications/notifications.js"></script>
    <script src="users.js"></script>
</body>

</html>
