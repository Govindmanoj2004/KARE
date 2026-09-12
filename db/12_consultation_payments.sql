-- 12_consultation_payments.sql
-- -----------------------------------------------------------------------
-- Adds a doctor-settable consultation fee and a general `payments` ledger.
--
-- Context: prescription_requests.fee_amount/fee_paid (09_prescription_requests.sql)
-- already implements a *simulated* per-request fee, but it's a pair of
-- columns bolted onto one workflow, with no ledger, no history, and no
-- receipt. This migration generalizes that idea:
--
--   - users.consultation_fee: a doctor-only field (nullable, like
--     `specialty`) the doctor sets on their Account page. NULL/0 means
--     "no fee" -- connecting to that doctor stays free, unchanged from
--     today's behavior (backward compatible).
--   - payments: one row per simulated payment, patient -> doctor. Used
--     first for the "pay-to-connect" consultation fee (patient pays
--     before a doctor_connections row is even created), and could later
--     also carry prescription-request fees under `type = 'prescription_request'`
--     without a schema change (existing prescription_requests.fee_* columns
--     are left untouched for this pass to avoid touching a working flow).
--
-- Still simulated, per README §7/§8: no real gateway, no card processing.
-- The "payment" is just a row written with status='paid' the moment the
-- user clicks Pay -- same spirit as the existing pay_fee action.
-- -----------------------------------------------------------------------

ALTER TABLE users
    ADD COLUMN consultation_fee DECIMAL(8,2) NULL DEFAULT NULL AFTER specialty;

CREATE TABLE IF NOT EXISTS payments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    payer_id      INT NOT NULL,                              -- the patient who paid
    payee_id      INT NOT NULL,                               -- the doctor who was paid
    type          ENUM('consultation', 'prescription_request') NOT NULL DEFAULT 'consultation',
    reference_id  INT NULL,                                   -- e.g. doctor_connections.id once created
    amount        DECIMAL(8,2) NOT NULL,
    status        ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    method        VARCHAR(50) NOT NULL DEFAULT 'simulated',
    paid_at       DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_payer FOREIGN KEY (payer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_payee FOREIGN KEY (payee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo data: give two of the three seeded demo doctors a consultation fee
-- so the pay-to-connect flow has something to exercise out of the box.
-- Dr. Anjali Menon stays free (tests the "no fee -> free connect" path
-- still works unchanged).
UPDATE users SET consultation_fee = 25.00 WHERE email = 'rahul.nair@kare-demo.test';
UPDATE users SET consultation_fee = 40.00 WHERE email = 'sara.thomas@kare-demo.test';
