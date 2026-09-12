-- 13_seed_demo_payments.sql
-- -----------------------------------------------------------------------
-- Demo data for the `payments` table (12_consultation_payments.sql), so
-- a fresh install's Payments pages (patient history, doctor earnings,
-- admin oversight) aren't empty -- same rationale as 08_seed_demo_data.sql.
--
-- Reuses testpatient's (id=3) existing connections to the two now-paid
-- demo doctors (102 = Dr. Rahul Nair, $25; 103 = Dr. Sara Thomas, $40,
-- see 08_seed_demo_data.sql) and adds one new connection + payment for
-- kohai (id=4) to Dr. Sara Thomas, so more than one patient/doctor pair
-- shows up in the admin's cross-platform view.
--
-- Safe to re-run: deletes-then-inserts its own rows, same convention as
-- 08_seed_demo_data.sql.
-- -----------------------------------------------------------------------

-- --- kohai (id=4) -> Dr. Sara Thomas (103): a new paid connection --------

DELETE FROM doctor_connections WHERE id = 104;

INSERT INTO doctor_connections (id, patient_id, doctor_id, status, message, requested_at, responded_at) VALUES
  (104, 4, 103, 'accepted', 'Would like a nutrition consult around my cholesterol medication.', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 2 DAY);

-- --- payments: testpatient -> Dr. Rahul Nair (already-accepted connection 102) ---
-- --- payments: testpatient -> Dr. Sara Thomas (still-pending connection 103) ----
-- --- payments: kohai       -> Dr. Sara Thomas (new connection 104) -------------

DELETE FROM payments WHERE id IN (301, 302, 303);

INSERT INTO payments (id, payer_id, payee_id, type, reference_id, amount, status, method, paid_at, created_at) VALUES
  (301, 3, 102, 'consultation', 102, 25.00, 'paid', 'simulated', NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY),
  (302, 3, 103, 'consultation', 103, 40.00, 'paid', 'simulated', NOW() - INTERVAL 1 DAY,  NOW() - INTERVAL 1 DAY),
  (303, 4, 103, 'consultation', 104, 40.00, 'paid', 'simulated', NOW() - INTERVAL 3 DAY,  NOW() - INTERVAL 3 DAY);
