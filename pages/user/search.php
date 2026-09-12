<?php

/**
 * search.php
 * -----------------------------------------------------------------------
 * Backs the navbar's search form (navbar.php: GET ?q=...). Searches only
 * the logged-in user's own medicines and prescriptions — a simple LIKE
 * match, no full-text index needed at this scale. Results link straight
 * to Schedule / Prescriptions rather than duplicating those pages' UI.
 * -----------------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];

$activePage = ''; // search isn't a sidebar item
$mrRootBase = '../../'; // this file lives at pages/user/search.php

$query = trim($_GET['q'] ?? '');

$medicineResults = [];
$prescriptionResults = [];

if ($query !== '') {
    $like = '%' . $query . '%';

    $medStmt = mysqli_prepare($con, "
        SELECT id, name, dosage, notes
        FROM medicines
        WHERE user_id = ? AND is_active = 1 AND (name LIKE ? OR notes LIKE ?)
        ORDER BY name ASC
        LIMIT 20
    ");
    mysqli_stmt_bind_param($medStmt, 'iss', $userId, $like, $like);
    mysqli_stmt_execute($medStmt);
    $medResult = mysqli_stmt_get_result($medStmt);
    while ($row = mysqli_fetch_assoc($medResult)) {
        $medicineResults[] = $row;
    }
    mysqli_stmt_close($medStmt);

    $rxStmt = mysqli_prepare($con, "
        SELECT id, title, doctor_name, uploaded_at
        FROM prescriptions
        WHERE user_id = ? AND (title LIKE ? OR doctor_name LIKE ?)
        ORDER BY uploaded_at DESC
        LIMIT 20
    ");
    mysqli_stmt_bind_param($rxStmt, 'iss', $userId, $like, $like);
    mysqli_stmt_execute($rxStmt);
    $rxResult = mysqli_stmt_get_result($rxStmt);
    while ($row = mysqli_fetch_assoc($rxResult)) {
        $prescriptionResults[] = $row;
    }
    mysqli_stmt_close($rxStmt);
}

$hasResults = !empty($medicineResults) || !empty($prescriptionResults);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare search results.">
    <title><?= $query !== '' ? 'Search: ' . htmlspecialchars($query) . ' · Kare' : 'Search · Kare' ?></title>

    <link rel="stylesheet" href="../../shared/tokens.css">
    <link rel="stylesheet" href="../../shared/base.css">
    <link rel="stylesheet" href="../../shared/components.css">
    <link rel="stylesheet" href="../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="search.css">

    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../shared/modal/modal.php'; ?>

    <div class="mr-app-shell" data-mr-app-shell>

        <?php include __DIR__ . '/../../shared/user/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../shared/user/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">
                                <?php if ($query !== ''): ?>
                                    Results for <strong>&ldquo;<?= htmlspecialchars($query) ?>&rdquo;</strong>
                                <?php else: ?>
                                    <strong>Search</strong>
                                <?php endif; ?>
                            </div>
                            <div class="mr-layout-header-sub">Searches your medicines and prescriptions.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">

                    <?php if ($query === ''): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 0">
                            <i class="ph ph-magnifying-glass"></i>
                            <p>Type something in the search bar above to get started.</p>
                        </div>
                    <?php elseif (!$hasResults): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 0">
                            <i class="ph ph-magnifying-glass"></i>
                            <p>No matches for &ldquo;<?= htmlspecialchars($query) ?>&rdquo;.</p>
                        </div>
                    <?php else: ?>

                        <?php if (!empty($medicineResults)): ?>
                            <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 0">
                                <div class="mr-card-heading">Medicines</div>
                            </div>
                            <div class="mr-medicine-list" data-mr-scroll-entry style="--index: 1">
                                <?php foreach ($medicineResults as $med): ?>
                                    <a class="mr-medicine-card mr-search-result" href="schedule/schedule.php">
                                        <div>
                                            <div class="mr-medicine-card-name"><?= htmlspecialchars($med['name']) ?></div>
                                            <?php if (!empty($med['dosage'])): ?>
                                                <div class="mr-field-hint"><?= htmlspecialchars($med['dosage']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <i class="ph ph-caret-right"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($medicineResults) && !empty($prescriptionResults)): ?>
                            <div class="mr-divider"></div>
                        <?php endif; ?>

                        <?php if (!empty($prescriptionResults)): ?>
                            <div class="mr-card-heading-row" data-mr-scroll-entry style="--index: 2">
                                <div class="mr-card-heading">Prescriptions</div>
                            </div>
                            <div class="mr-medicine-list" data-mr-scroll-entry style="--index: 3">
                                <?php foreach ($prescriptionResults as $rx): ?>
                                    <a class="mr-medicine-card mr-search-result" href="prescriptions/prescriptions.php">
                                        <div>
                                            <div class="mr-medicine-card-name"><?= htmlspecialchars($rx['title']) ?></div>
                                            <?php if (!empty($rx['doctor_name'])): ?>
                                                <div class="mr-field-hint"><?= htmlspecialchars($rx['doctor_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <i class="ph ph-caret-right"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>

                </section>

            </main>
        </div>
    </div>

    <script src="../../shared/modal/modal.js"></script>
    <script src="../../shared/notifications/notifications.js"></script>
    <script src="search.js"></script>
</body>

</html>
