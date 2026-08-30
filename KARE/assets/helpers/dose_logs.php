<?php

/**
 * assets/helpers/dose_logs.php
 * -----------------------------------------------------------------------
 * ensure_todays_dose_logs() makes sure every active schedule for a user
 * has a dose_log row for today, defaulting to 'upcoming'. Called at the
 * top of any page that reads today's doses (home.php, schedule.php) so
 * a freshly-added medicine shows up immediately without needing a cron
 * job to have run first.
 *
 * This does NOT auto-mark anything as 'missed' — real missed-dose
 * detection (flagged in CHANGELOG.md as a future cron-based feature)
 * is a separate concern from simply making sure today's rows exist.
 * -----------------------------------------------------------------------
 */

function ensure_todays_dose_logs(mysqli $con, int $userId): void
{
    $findStmt = mysqli_prepare($con, "
        SELECT ms.id AS schedule_id, ms.time_of_day
        FROM medicine_schedules ms
        JOIN medicines m ON m.id = ms.medicine_id
        WHERE m.user_id = ?
          AND m.is_active = 1
          AND ms.is_active = 1
          AND NOT EXISTS (
              SELECT 1 FROM dose_logs dl
              WHERE dl.schedule_id = ms.id
                AND DATE(dl.scheduled_for) = CURDATE()
          )
    ");
    mysqli_stmt_bind_param($findStmt, 'i', $userId);
    mysqli_stmt_execute($findStmt);
    $result = mysqli_stmt_get_result($findStmt);

    $missing = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $missing[] = $row;
    }
    mysqli_stmt_close($findStmt);

    if (empty($missing)) {
        return;
    }

    $insertStmt = mysqli_prepare($con, "
        INSERT INTO dose_logs (schedule_id, scheduled_for, status)
        VALUES (?, CONCAT(CURDATE(), ' ', ?), 'upcoming')
    ");
    foreach ($missing as $row) {
        mysqli_stmt_bind_param($insertStmt, 'is', $row['schedule_id'], $row['time_of_day']);
        mysqli_stmt_execute($insertStmt);
    }
    mysqli_stmt_close($insertStmt);
}
