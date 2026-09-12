-- 11_notifications.sql
-- Adds in-app notifications (README §6 "Not built" -> Working notifications).
-- One row per notification event, shown via the navbar bell dropdown for
-- both patients and doctors (and admins, for consistency). No schema
-- changes to any other table; nothing here touches notify_email/notify_sms
-- (those remain untouched preference toggles, per README §6).

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(50) NOT NULL,        -- e.g. 'connection_request', 'message', 'prescription_request', 'report_reply'
  body VARCHAR(255) NOT NULL,       -- short human-readable text shown in the dropdown
  link VARCHAR(255) NULL,           -- root-relative path (e.g. 'pages/user/messages/messages.php'), no leading slash
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT notifications_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_user_unread (user_id, read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
