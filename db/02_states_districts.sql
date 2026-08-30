-- =====================================================================
-- States & Districts reference tables (required by profile.php,
-- profile_controller.php, get_districts.php — present in code but
-- missing from the provided backup dump).
-- =====================================================================

CREATE TABLE IF NOT EXISTS `states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_states_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `districts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `state_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_districts_state_id` (`state_id`),
  CONSTRAINT `fk_districts_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users gains nullable FKs to state/district (design.md §8)
ALTER TABLE `users`
  ADD COLUMN `state_id` int(11) DEFAULT NULL AFTER `phone_verified_at`,
  ADD COLUMN `district_id` int(11) DEFAULT NULL AFTER `state_id`,
  ADD KEY `idx_users_state_id` (`state_id`),
  ADD KEY `idx_users_district_id` (`district_id`),
  ADD CONSTRAINT `fk_users_state` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_district` FOREIGN KEY (`district_id`) REFERENCES `districts` (`id`) ON DELETE SET NULL;
