-- 10_seed_prescription_requests.sql
-- -----------------------------------------------------------------------
-- Demo data for the prescription request/update workflow added in
-- 09_prescription_requests.sql (to-do #9/#13/#14). Apply last, after
-- 09_prescription_requests.sql. One fulfilled example (marks prescription
-- id=1 as testpatient's current prescription, with a paid fee) and one
-- still-pending example, so the new pages/*/prescriptions/ request
-- workflow has something to show on a fresh install instead of looking
-- empty.
-- -----------------------------------------------------------------------

DELETE FROM prescription_requests WHERE connection_id IN (101, 102);

UPDATE prescriptions SET is_current = 1 WHERE id = 1 AND user_id = 3;

INSERT INTO prescription_requests (connection_id, requested_by, message, status, fee_amount, fee_paid, fulfilled_prescription_id, requested_at, responded_at) VALUES
  (101, 'patient', 'Could you review my dosage and confirm the current prescription is still correct?', 'fulfilled', 10.00, 1, 1, NOW() - INTERVAL 12 DAY, NOW() - INTERVAL 11 DAY),
  (102, 'patient', 'I would like an updated prescription reflecting my latest blood pressure readings.', 'pending', NULL, 0, NULL, NOW() - INTERVAL 1 DAY, NULL);
