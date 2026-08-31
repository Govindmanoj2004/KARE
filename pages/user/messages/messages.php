<?php

/**
 * messages.php
 * -----------------------------------------------------------------------
 * Simple polling-based chat with a connected doctor.
 *   - Left: list of accepted doctor connections (conversations).
 *   - Right: the selected conversation's messages, oldest first, with a
 *     send box. messages.js polls messages_poll.php every few seconds
 *     for new messages so the thread updates without a full reload.
 * A patient can only see/send into connections that are their own and
 * 'accepted' — enforced again server-side in the controller/poll files,
 * not just here.
 * -----------------------------------------------------------------------
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../../auth/login/index.php');
    exit;
}

require_once __DIR__ . '/../../../assets/connection/Connection.php';

$userId = (int) $_SESSION['user_id'];

$activePage = 'messages';
$mrRootBase = '../../../'; // this file lives at pages/user/messages/messages.php

// --- Accepted conversations for this patient --------------------------------
$conversations = [];
$convStmt = mysqli_prepare($con, "
    SELECT dc.id AS connection_id, u.name AS doctor_name, u.specialty,
           (SELECT body FROM messages m WHERE m.connection_id = dc.id ORDER BY m.id DESC LIMIT 1) AS last_message,
           (SELECT created_at FROM messages m WHERE m.connection_id = dc.id ORDER BY m.id DESC LIMIT 1) AS last_message_at
    FROM doctor_connections dc
    JOIN users u ON u.id = dc.doctor_id
    WHERE dc.patient_id = ? AND dc.status = 'accepted'
    ORDER BY last_message_at IS NULL, last_message_at DESC
");
mysqli_stmt_bind_param($convStmt, 'i', $userId);
mysqli_stmt_execute($convStmt);
$convResult = mysqli_stmt_get_result($convStmt);
while ($row = mysqli_fetch_assoc($convResult)) {
    $conversations[] = $row;
}
mysqli_stmt_close($convStmt);

// --- Selected conversation (default to the first one) -----------------------
$selectedId = isset($_GET['connection_id']) ? (int) $_GET['connection_id'] : 0;
$selected = null;
foreach ($conversations as $c) {
    if ((int) $c['connection_id'] === $selectedId) {
        $selected = $c;
        break;
    }
}
if (!$selected && !empty($conversations)) {
    $selected = $conversations[0];
    $selectedId = (int) $selected['connection_id'];
}

$messages = [];
if ($selected) {
    $msgStmt = mysqli_prepare($con, 'SELECT id, sender_id, body, created_at, read_at FROM messages WHERE connection_id = ? ORDER BY id ASC');
    mysqli_stmt_bind_param($msgStmt, 'i', $selectedId);
    mysqli_stmt_execute($msgStmt);
    $msgResult = mysqli_stmt_get_result($msgStmt);
    while ($row = mysqli_fetch_assoc($msgResult)) {
        $messages[] = $row;
    }
    mysqli_stmt_close($msgStmt);
}
$lastMessageId = !empty($messages) ? (int) end($messages)['id'] : 0;

// Mark incoming messages in this thread as read now that the patient has viewed it.
if ($selected) {
    $markReadStmt = mysqli_prepare($con, "
        UPDATE messages SET read_at = NOW()
        WHERE connection_id = ? AND sender_id != ? AND read_at IS NULL
    ");
    mysqli_stmt_bind_param($markReadStmt, 'ii', $selectedId, $userId);
    mysqli_stmt_execute($markReadStmt);
    mysqli_stmt_close($markReadStmt);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Kare messages — chat with your connected doctor.">
    <title>Messages · Kare</title>

    <link rel="stylesheet" href="../../../shared/tokens.css">
    <link rel="stylesheet" href="../../../shared/base.css">
    <link rel="stylesheet" href="../../../shared/components.css">
    <link rel="stylesheet" href="../../../shared/modal/modal.css">
    <link rel="stylesheet" href="messages.css">

    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '/../../../shared/modal/modal.php'; ?>

    <div class="mr-app-shell" data-mr-app-shell>

        <?php include __DIR__ . '/../../../shared/user/sidebar.php'; ?>

        <div class="mr-main">

            <?php include __DIR__ . '/../../../shared/user/navbar.php'; ?>

            <main class="mr-content">

                <section class="mr-layout-header">
                    <div class="mr-layout-header-row" data-mr-scroll-entry>
                        <div>
                            <div class="mr-layout-header-heading">Your <strong>Messages</strong></div>
                            <div class="mr-layout-header-sub">Chat with a doctor you're connected with.</div>
                        </div>
                    </div>
                </section>

                <section class="mr-layout-card mr-messages-card">

                    <?php if (empty($conversations)): ?>
                        <div class="mr-schedule-empty" data-mr-scroll-entry style="--index: 0">
                            <i class="ph ph-chat-circle-dots"></i>
                            <p>No conversations yet. Connect with a doctor first.</p>
                            <a href="../doctors/doctors.php" class="mr-btn" style="margin-top: 8px;">
                                <i class="ph ph-stethoscope"></i> Find a doctor
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="mr-messages-layout" data-mr-scroll-entry style="--index: 0">

                            <!-- Conversation list -->
                            <nav class="mr-conversation-list">
                                <?php foreach ($conversations as $c): ?>
                                    <a href="messages.php?connection_id=<?= (int) $c['connection_id'] ?>"
                                        class="mr-conversation-item<?= (int) $c['connection_id'] === $selectedId ? ' is-active' : '' ?>">
                                        <span class="mr-profile-avatar mr-conversation-avatar">
                                            <?= htmlspecialchars(strtoupper(substr($c['doctor_name'], 0, 1))) ?>
                                        </span>
                                        <span class="mr-conversation-meta">
                                            <span class="mr-conversation-name"><?= htmlspecialchars($c['doctor_name']) ?></span>
                                            <span class="mr-conversation-preview">
                                                <?= $c['last_message'] ? htmlspecialchars(mb_strimwidth($c['last_message'], 0, 40, '…')) : 'Say hello!' ?>
                                            </span>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </nav>

                            <!-- Thread -->
                            <div class="mr-thread">
                                <?php if ($selected): ?>
                                    <div class="mr-thread-header">
                                        <span class="mr-profile-avatar mr-conversation-avatar">
                                            <?= htmlspecialchars(strtoupper(substr($selected['doctor_name'], 0, 1))) ?>
                                        </span>
                                        <div>
                                            <div class="mr-conversation-name"><?= htmlspecialchars($selected['doctor_name']) ?></div>
                                            <?php if (!empty($selected['specialty'])): ?>
                                                <div class="mr-field-hint"><?= htmlspecialchars($selected['specialty']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="mr-thread-messages" data-mr-thread-messages data-connection-id="<?= (int) $selectedId ?>" data-last-id="<?= $lastMessageId ?>">
                                        <?php if (empty($messages)): ?>
                                            <div class="mr-thread-empty">No messages yet — say hello!</div>
                                        <?php endif; ?>
                                        <?php $lastMineIndex = null; foreach ($messages as $mi => $mm) { if ((int) $mm['sender_id'] === $userId) { $lastMineIndex = $mi; } } ?>
                                        <?php foreach ($messages as $mi => $m): ?>
                                            <div class="mr-bubble <?= (int) $m['sender_id'] === $userId ? 'is-mine' : 'is-theirs' ?>">
                                                <div class="mr-bubble-body"><?= nl2br(htmlspecialchars($m['body'])) ?></div>
                                                <div class="mr-bubble-time">
                                                    <?= htmlspecialchars(date('g:i A', strtotime($m['created_at']))) ?>
                                                    <?php if ($mi === $lastMineIndex && !empty($m['read_at'])): ?>
                                                        <span class="mr-bubble-seen"><i class="ph ph-checks"></i> Seen</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <form action="messages_controller.php" method="post" class="mr-thread-composer" data-mr-composer>
                                        <input type="hidden" name="action" value="send_message">
                                        <input type="hidden" name="connection_id" value="<?= (int) $selectedId ?>">
                                        <input class="mr-input" type="text" name="body" placeholder="Type a message…" autocomplete="off" required maxlength="2000">
                                        <button type="submit" class="mr-btn"><i class="ph ph-paper-plane-tilt"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </section>

            </main>
        </div>
    </div>

    <script src="../../../shared/modal/modal.js"></script>
    <script src="messages.js"></script>
</body>

</html>
