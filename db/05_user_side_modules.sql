-- =====================================================================
-- Remaining user-side modules: settings prefs, prescriptions,
-- doctor directory/connections, messaging.
-- =====================================================================

-- Notification preferences + doctor-only specialty field on users.
ALTER TABLE `users`
  ADD COLUMN `notify_email` tinyint(1) NOT NULL DEFAULT 1 AFTER `district_id`,
  ADD COLUMN `notify_sms` tinyint(1) NOT NULL DEFAULT 0 AFTER `notify_email`,
  ADD COLUMN `specialty` varchar(100) DEFAULT NULL AFTER `notify_sms`;

-- One row per prescription a patient has uploaded.
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `doctor_name` varchar(150) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_original_name` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_prescriptions_user_id` (`user_id`),
  CONSTRAINT `fk_prescriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A patient's connection request to a doctor (and its lifecycle).
CREATE TABLE IF NOT EXISTS `doctor_connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `status` enum('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `message` varchar(255) DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_patient_doctor` (`patient_id`, `doctor_id`),
  KEY `idx_connections_doctor_id` (`doctor_id`),
  CONSTRAINT `fk_connections_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_connections_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages, one thread per accepted doctor_connections row.
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `connection_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_messages_connection_id` (`connection_id`),
  CONSTRAINT `fk_messages_connection` FOREIGN KEY (`connection_id`) REFERENCES `doctor_connections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo doctor accounts (signup only ever creates role='patient', so
-- there's otherwise no way to populate the doctor directory in dev).
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `status`, `is_verified`, `specialty`)
VALUES
  (101, 'Dr. Anjali Menon', 'anjali.menon@kare-demo.test', '+919812345601', 'doctor1234', 'doctor', 'active', 1, 'General Physician'),
  (102, 'Dr. Rahul Nair', 'rahul.nair@kare-demo.test', '+919812345602', 'doctor1234', 'doctor', 'active', 1, 'Cardiologist'),
  (103, 'Dr. Sara Thomas', 'sara.thomas@kare-demo.test', '+919812345603', 'doctor1234', 'doctor', 'active', 1, 'Endocrinologist');
