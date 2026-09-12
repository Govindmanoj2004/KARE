<?php

/**
 * help.php
 * -----------------------------------------------------------------------
 * Static Help & FAQ page. No database access needed — the content below
 * doubles as user-facing documentation for every module built this
 * session, kept in one place so it's easy to keep in sync.
 * -----------------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login/index.php');
    exit;
}

$activePage = ''; // no sidebar item maps to this page — it's a footer link
$mrRootBase = '../../'; // this file lives at pages/user/help.php

$faqs = [
    [
        'q' => 'How do I add a medicine to my schedule?',
        'a' => 'Go to Schedule, fill in the medicine name, dosage, and one or more reminder times, then click "Add medicine". It will appear in today\'s doses at each of those times.',
    ],
    [
        'q' => "Why isn't a medicine showing up on today's schedule?",
        'a' => 'Doses are generated for each active reminder time the moment you visit the Dashboard or Schedule page. If you just added a medicine, refresh either page and it should appear.',
    ],
    [
        'q' => 'What happens when I mark a dose as taken or missed?',
        'a' => "It updates that dose's status immediately — reflected on both the Dashboard and your Reports page, which tracks your adherence rate over the last 30 days.",
    ],
    [
        'q' => 'How do I connect with a doctor?',
        'a' => 'Go to Doctors, find one in the list, and click "Connect" — you can include an optional note. Once they accept, you can message them from the Messages page.',
    ],
    [
        'q' => 'What file types can I upload as a prescription?',
        'a' => 'PDF, JPG, and PNG files up to 2MB each. Upload them from the Prescriptions page along with a title and optional doctor name/notes.',
    ],
    [
        'q' => 'Can I change my email notification preferences?',
        'a' => 'Yes — go to Settings to toggle email and SMS reminders. (SMS delivery itself isn\'t wired up yet; the preference is saved for when it is.)',
    ],
    [
        'q' => 'How do I deactivate my account?',
        'a' => "On the Settings page, under Danger zone, enter your password and click \"Deactivate account\". You'll be signed out immediately and won't be able to log back in until support reactivates it — your data isn't deleted.",
    ],
    [
        'q' => "I found a bug or something isn't working — what do I do?",
        'a' => 'Use Report Issue in the sidebar (or "Contact support" from your profile menu) to describe what happened. You can track replies to it right there.',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare help & FAQ.">
    <title>Help &amp; FAQ · Kare</title>

    <link rel="stylesheet" href="../../shared/tokens.css">
    <link rel="stylesheet" href="../../shared/base.css">
    <link rel="stylesheet" href="../../shared/components.css">
    <link rel="stylesheet" href="../../shared/modal/modal.css">
    <link rel="stylesheet" href="../../shared/notifications/notifications.css">
    <link rel="stylesheet" href="help.css">

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
                            <div class="mr-layout-header-heading">Help &amp; <strong>FAQ</strong></div>
                            <div class="mr-layout-header-sub">Quick answers about using Kare. Still stuck? Report an issue and we'll follow up.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card">
                    <div class="mr-faq-list" data-mr-scroll-entry style="--index: 0">
                        <?php foreach ($faqs as $i => $faq): ?>
                            <details class="mr-faq-item">
                                <summary>
                                    <span><?= htmlspecialchars($faq['q']) ?></span>
                                    <i class="ph ph-caret-down"></i>
                                </summary>
                                <p><?= htmlspecialchars($faq['a']) ?></p>
                            </details>
                        <?php endforeach; ?>
                    </div>

                    <div class="mr-divider"></div>

                    <div class="mr-help-cta" data-mr-scroll-entry style="--index: 1">
                        <div>
                            <div class="mr-card-heading">Still need help?</div>
                            <div class="mr-field-hint">File a report and track the reply right in the app.</div>
                        </div>
                        <a href="report/report.php" class="mr-btn"><i class="ph ph-flag"></i> Report an issue</a>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <script src="../../shared/modal/modal.js"></script>
    <script src="../../shared/notifications/notifications.js"></script>
    <script src="help.js"></script>
</body>

</html>
