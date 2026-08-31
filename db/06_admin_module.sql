-- =====================================================================
-- Admin module — no new tables/columns needed. `users.is_verified` is
-- reused for doctor verification, and `reports.admin_reply`/`replied_at`
-- (already in 04_reports_and_meds.sql) were sitting unused until now.
-- This migration only seeds the first admin account, since there was
-- previously no way to create one (signup only ever creates patients).
-- =====================================================================

INSERT IGNORE INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `status`, `is_verified`)
VALUES
  (201, 'Kare Admin', 'admin@kare-demo.test', '+919812345001', 'admin1234', 'admin', 'active', 1);
