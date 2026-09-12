-- 09_prescription_requests.sql
-- -----------------------------------------------------------------------
-- Adds the prescription request/update workflow (to-do items #9, #13, #14):
--   - A patient can request a prescription update from a connected doctor.
--   - A doctor can also proactively offer/request to update a patient's
--     prescription (requested_by tracks who started it).
--   - A doctor can attach a fee before fulfilling ("nothing is free" —
--     to-do #9). This is a simulated fee/payment flag, not a real payment
--     gateway, consistent with this project's plain-text-password,
--     no-external-integrations scope.
--   - Fulfilling a request uploads a new prescription file (same
--     validation as the patient's own upload) and marks it as the
--     patient's one "current" prescription (to-do #13).
-- Apply after 08_seed_demo_data.sql.
-- -----------------------------------------------------------------------

ALTER TABLE prescriptions
  ADD COLUMN issued_by ENUM('patient', 'doctor') NOT NULL DEFAULT 'patient' AFTER user_id,
  ADD COLUMN is_current TINYINT(1) NOT NULL DEFAULT 0 AFTER issued_by;

CREATE TABLE IF NOT EXISTS prescription_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  connection_id INT NOT NULL,
  requested_by ENUM('patient', 'doctor') NOT NULL,
  message VARCHAR(255) NOT NULL,
  status ENUM('pending', 'fulfilled', 'declined') NOT NULL DEFAULT 'pending',
  fee_amount DECIMAL(8,2) NULL,
  fee_paid TINYINT(1) NOT NULL DEFAULT 0,
  fulfilled_prescription_id INT NULL,
  doctor_note VARCHAR(255) NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responded_at DATETIME NULL,
  FOREIGN KEY (connection_id) REFERENCES doctor_connections(id) ON DELETE CASCADE,
  FOREIGN KEY (fulfilled_prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
