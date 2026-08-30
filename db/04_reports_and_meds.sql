-- =====================================================================
-- reports table (used by pages/user/report/report_controller.php)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `admin_reply` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reports_user_id` (`user_id`),
  KEY `idx_reports_status` (`status`),
  CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Medicine reminder core tables — needed to wire home.php's stat cards
-- and schedule table to real data instead of hard-coded PHP arrays
-- (flagged as pending in CHANGELOG.md "What's next").
-- =====================================================================

-- One row per medicine a patient is tracking.
CREATE TABLE IF NOT EXISTS `medicines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,          -- the patient this medicine belongs to
  `name` varchar(150) NOT NULL,
  `dosage` varchar(100) DEFAULT NULL,  -- e.g. "500mg"
  `notes` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_medicines_user_id` (`user_id`),
  CONSTRAINT `fk_medicines_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per daily reminder time for a medicine (e.g. Metformin at 8:00 AM).
CREATE TABLE IF NOT EXISTS `medicine_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_id` int(11) NOT NULL,
  `time_of_day` time NOT NULL,         -- e.g. 08:00:00
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_schedules_medicine_id` (`medicine_id`),
  CONSTRAINT `fk_schedules_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One row per day/dose — records whether that scheduled dose was taken, missed, or is still upcoming.
CREATE TABLE IF NOT EXISTS `dose_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `scheduled_for` datetime NOT NULL,   -- the actual date+time this dose was due
  `status` enum('upcoming','taken','missed') NOT NULL DEFAULT 'upcoming',
  `taken_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dose_logs_schedule_id` (`schedule_id`),
  KEY `idx_dose_logs_scheduled_for` (`scheduled_for`),
  CONSTRAINT `fk_dose_logs_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `medicine_schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
