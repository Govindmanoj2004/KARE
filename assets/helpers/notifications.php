<?php

/**
 * assets/helpers/notifications.php
 * -----------------------------------------------------------------------
 * In-app notifications (README §6 "Not built" -> Working notifications).
 * A thin write helper used by existing controllers at the moment something
 * notification-worthy happens (a connection request, a new message, a
 * prescription request/fulfillment, an admin reply to a report, etc).
 *
 * Reading/marking-read happens in shared/notifications/ (fetch + controller),
 * included from the navbar on every page. This file only creates rows.
 * -----------------------------------------------------------------------
 */

/**
 * Insert a notification row for a single user.
 *
 * @param mysqli      $con    Open mysqli connection.
 * @param int         $userId The recipient's users.id.
 * @param string      $type   Short machine tag, e.g. 'message', 'connection_request'.
 * @param string      $body   Human-readable text shown in the dropdown (already
 *                            plain text -- the widget escapes it on render).
 * @param string|null $link   Root-relative path (no leading slash), e.g.
 *                            'pages/user/messages/messages.php'. Null if the
 *                            notification has nowhere useful to link to.
 */
function create_notification(mysqli $con, int $userId, string $type, string $body, ?string $link = null): void
{
    $stmt = mysqli_prepare($con, "
        INSERT INTO notifications (user_id, type, body, link, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    mysqli_stmt_bind_param($stmt, 'isss', $userId, $type, $body, $link);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
