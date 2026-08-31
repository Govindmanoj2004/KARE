-- =====================================================================
-- Planned additions (README §12):
--   - Snooze a dose: needs a cap so a dose can't be pushed forever
--   - Private notes on a patient: needs a new table
-- "Remember me", calendar view, and message seen-indicator all reuse
-- existing columns/tables — no schema changes needed for those three.
-- =====================================================================

ALTER TABLE `dose_logs`
  ADD COLUMN `snooze_count` tinyint(1) NOT NULL DEFAULT 0 AFTER `taken_at`;

CREATE TABLE IF NOT EXISTS `doctor_patient_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `note` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doctor_patient` (`doctor_id`, `patient_id`),
  KEY `idx_notes_patient_id` (`patient_id`),
  CONSTRAINT `fk_notes_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notes_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
