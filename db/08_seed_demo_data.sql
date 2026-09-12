-- 08_seed_demo_data.sql
-- -----------------------------------------------------------------------
-- Realistic demo data for the seeded accounts (README §9), so the app
-- doesn't look empty on a fresh sandbox. Idempotent-ish: uses INSERT
-- IGNORE / DELETE-then-INSERT for the demo-account rows it owns, so it's
-- safe to re-run. Apply after 07_planned_additions.sql.
--
-- Covers: testpatient (id=3) and kohai (id=4) with medicines, schedules,
-- dose_logs (including deliberately overdue "upcoming" doses so the
-- app's future missed-dose display logic has something to show),
-- prescriptions, doctor_connections, messages, a support report, and a
-- doctor's private note. govind (id=1) already had ad-hoc data from
-- manual testing and is left untouched.
-- -----------------------------------------------------------------------

-- --- testpatient (id=3) -------------------------------------------------

DELETE FROM medicines WHERE user_id = 3;

INSERT INTO medicines (id, user_id, name, dosage, notes, is_active, created_at, updated_at) VALUES
  (101, 3, 'Metformin', '500mg', 'Take with breakfast', 1, NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 20 DAY),
  (102, 3, 'Amlodipine', '5mg', 'For blood pressure', 1, NOW() - INTERVAL 20 DAY, NOW() - INTERVAL 20 DAY),
  (103, 3, 'Vitamin D3', '60000 IU', 'Weekly dose', 1, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY);

DELETE FROM medicine_schedules WHERE medicine_id IN (101, 102, 103);

INSERT INTO medicine_schedules (id, medicine_id, time_of_day, is_active, created_at) VALUES
  (201, 101, '08:00:00', 1, NOW() - INTERVAL 20 DAY),
  (202, 101, '20:00:00', 1, NOW() - INTERVAL 20 DAY),
  (203, 102, '09:00:00', 1, NOW() - INTERVAL 20 DAY),
  (204, 103, '10:00:00', 1, NOW() - INTERVAL 10 DAY);

DELETE FROM dose_logs WHERE schedule_id IN (201, 202, 203, 204);

-- 7 days of history: mostly taken, a few missed, so adherence % and the
-- reports calendar/charts have real variety to render.
INSERT INTO dose_logs (schedule_id, scheduled_for, status, taken_at, snooze_count, created_at) VALUES
  (201, CURDATE() - INTERVAL 6 DAY + INTERVAL 8 HOUR,  'taken',  CURDATE() - INTERVAL 6 DAY + INTERVAL 8 HOUR + INTERVAL 5 MINUTE, 0, NOW()),
  (202, CURDATE() - INTERVAL 6 DAY + INTERVAL 20 HOUR, 'taken',  CURDATE() - INTERVAL 6 DAY + INTERVAL 20 HOUR + INTERVAL 10 MINUTE, 0, NOW()),
  (203, CURDATE() - INTERVAL 6 DAY + INTERVAL 9 HOUR,  'taken',  CURDATE() - INTERVAL 6 DAY + INTERVAL 9 HOUR, 0, NOW()),

  (201, CURDATE() - INTERVAL 5 DAY + INTERVAL 8 HOUR,  'taken',  CURDATE() - INTERVAL 5 DAY + INTERVAL 8 HOUR, 0, NOW()),
  (202, CURDATE() - INTERVAL 5 DAY + INTERVAL 20 HOUR, 'missed', NULL, 0, NOW()),
  (203, CURDATE() - INTERVAL 5 DAY + INTERVAL 9 HOUR,  'taken',  CURDATE() - INTERVAL 5 DAY + INTERVAL 9 HOUR, 0, NOW()),

  (201, CURDATE() - INTERVAL 4 DAY + INTERVAL 8 HOUR,  'taken',  CURDATE() - INTERVAL 4 DAY + INTERVAL 8 HOUR, 0, NOW()),
  (202, CURDATE() - INTERVAL 4 DAY + INTERVAL 20 HOUR, 'taken',  CURDATE() - INTERVAL 4 DAY + INTERVAL 20 HOUR, 0, NOW()),
  (203, CURDATE() - INTERVAL 4 DAY + INTERVAL 9 HOUR,  'missed', NULL, 0, NOW()),

  (201, CURDATE() - INTERVAL 3 DAY + INTERVAL 8 HOUR,  'taken',  CURDATE() - INTERVAL 3 DAY + INTERVAL 8 HOUR, 0, NOW()),
  (202, CURDATE() - INTERVAL 3 DAY + INTERVAL 20 HOUR, 'taken',  CURDATE() - INTERVAL 3 DAY + INTERVAL 20 HOUR, 0, NOW()),
  (203, CURDATE() - INTERVAL 3 DAY + INTERVAL 9 HOUR,  'taken',  CURDATE() - INTERVAL 3 DAY + INTERVAL 9 HOUR, 0, NOW()),

  (201, CURDATE() - INTERVAL 2 DAY + INTERVAL 8 HOUR,  'taken',  CURDATE() - INTERVAL 2 DAY + INTERVAL 8 HOUR, 0, NOW()),
  (202, CURDATE() - INTERVAL 2 DAY + INTERVAL 20 HOUR, 'taken',  CURDATE() - INTERVAL 2 DAY + INTERVAL 20 HOUR, 0, NOW()),
  (203, CURDATE() - INTERVAL 2 DAY + INTERVAL 9 HOUR,  'taken',  CURDATE() - INTERVAL 2 DAY + INTERVAL 9 HOUR, 0, NOW()),
  (204, CURDATE() - INTERVAL 2 DAY + INTERVAL 10 HOUR, 'taken',  CURDATE() - INTERVAL 2 DAY + INTERVAL 10 HOUR, 0, NOW()),

  (201, CURDATE() - INTERVAL 1 DAY + INTERVAL 8 HOUR,  'taken',  CURDATE() - INTERVAL 1 DAY + INTERVAL 8 HOUR, 0, NOW()),
  (202, CURDATE() - INTERVAL 1 DAY + INTERVAL 20 HOUR, 'missed', NULL, 0, NOW()),
  (203, CURDATE() - INTERVAL 1 DAY + INTERVAL 9 HOUR,  'taken',  CURDATE() - INTERVAL 1 DAY + INTERVAL 9 HOUR, 0, NOW()),

  -- Today: one already taken, one still due later today (upcoming),
  -- and one deliberately in the past-but-still-'upcoming' — this is the
  -- "overdue, never marked" case described in README §3.4 / to-do #6.
  (201, CURDATE() + INTERVAL 8 HOUR,  'taken',    CURDATE() + INTERVAL 8 HOUR, 0, NOW()),
  (203, CURDATE() + INTERVAL 9 HOUR,  'upcoming', NULL, 0, NOW()),
  (202, CURDATE() + INTERVAL 20 HOUR, 'upcoming', NULL, 0, NOW());

-- --- kohai (id=4) — lighter dataset, mostly upcoming/untouched --------

DELETE FROM medicines WHERE user_id = 4;

INSERT INTO medicines (id, user_id, name, dosage, notes, is_active, created_at, updated_at) VALUES
  (104, 4, 'Atorvastatin', '10mg', 'Nightly, for cholesterol', 1, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY);

DELETE FROM medicine_schedules WHERE medicine_id = 104;

INSERT INTO medicine_schedules (id, medicine_id, time_of_day, is_active, created_at) VALUES
  (205, 104, '21:00:00', 1, NOW() - INTERVAL 5 DAY);

DELETE FROM dose_logs WHERE schedule_id = 205;

INSERT INTO dose_logs (schedule_id, scheduled_for, status, taken_at, snooze_count, created_at) VALUES
  (205, CURDATE() - INTERVAL 2 DAY + INTERVAL 21 HOUR, 'taken', CURDATE() - INTERVAL 2 DAY + INTERVAL 21 HOUR, 0, NOW()),
  (205, CURDATE() - INTERVAL 1 DAY + INTERVAL 21 HOUR, 'taken', CURDATE() - INTERVAL 1 DAY + INTERVAL 21 HOUR, 0, NOW()),
  (205, CURDATE() + INTERVAL 21 HOUR, 'upcoming', NULL, 0, NOW());

-- --- doctor_connections: testpatient <-> all 3 demo doctors -------------

DELETE FROM doctor_connections WHERE patient_id = 3;

INSERT INTO doctor_connections (id, patient_id, doctor_id, status, message, requested_at, responded_at) VALUES
  (101, 3, 101, 'accepted', 'Hi Dr. Menon, I would like to check in about my medication schedule.', NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 14 DAY),
  (102, 3, 102, 'accepted', 'Referred by my GP for a cardiology follow-up.', NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 9 DAY),
  (103, 3, 103, 'pending', 'Would like a second opinion on my thyroid results.', NOW() - INTERVAL 1 DAY, NULL);

-- --- messages on the two accepted threads --------------------------------

DELETE FROM messages WHERE connection_id IN (101, 102);

INSERT INTO messages (connection_id, sender_id, body, created_at, read_at) VALUES
  (101, 3,   'Hi Dr. Menon, I have a question about my Metformin dosage.', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY + INTERVAL 2 HOUR),
  (101, 101, 'Sure, go ahead — what''s on your mind?', NOW() - INTERVAL 3 DAY + INTERVAL 2 HOUR, NOW() - INTERVAL 3 DAY + INTERVAL 3 HOUR),
  (101, 3,   'I sometimes feel dizzy about an hour after the evening dose.', NOW() - INTERVAL 3 DAY + INTERVAL 3 HOUR, NOW() - INTERVAL 3 DAY + INTERVAL 4 HOUR),
  (101, 101, 'That can happen if it''s taken on an empty stomach — try pairing it with a light snack and let me know if it continues.', NOW() - INTERVAL 3 DAY + INTERVAL 4 HOUR, NULL),

  (102, 102, 'Welcome! I have your referral notes — let''s start with your recent blood pressure readings.', NOW() - INTERVAL 8 DAY, NOW() - INTERVAL 8 DAY + INTERVAL 1 HOUR),
  (102, 3,   'They''ve been around 130/85 most mornings.', NOW() - INTERVAL 8 DAY + INTERVAL 1 HOUR, NOW() - INTERVAL 8 DAY + INTERVAL 2 HOUR),
  (102, 102, 'Good, that''s trending in the right direction. Keep logging it for another two weeks.', NOW() - INTERVAL 7 DAY, NULL);

-- --- one prescription on file --------------------------------------------

DELETE FROM prescriptions WHERE user_id = 3;

INSERT INTO prescriptions (user_id, title, doctor_name, notes, file_path, file_original_name, uploaded_at) VALUES
  (3, 'Diabetes management plan', 'Dr. Anjali Menon', 'Initial prescription after diagnosis', 'assets/uploads/prescriptions/3/demo-placeholder.pdf', 'diabetes-plan.pdf', NOW() - INTERVAL 15 DAY);

-- --- a support ticket, replied to by admin -------------------------------

DELETE FROM reports WHERE user_id = 3;

INSERT INTO reports (user_id, subject, message, status, admin_reply, replied_at, created_at, updated_at) VALUES
  (3, 'Reminder notification not showing', 'My 8am reminder for Metformin didn''t show a browser notification today, even though notify_email is on.', 'resolved', 'Thanks for flagging this — email/SMS sending isn''t wired up yet in this build, only the preference is saved. A working notification system is planned; see the project to-do list.', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 2 DAY);

-- --- doctor's private note on the patient --------------------------------

DELETE FROM doctor_patient_notes WHERE doctor_id = 101 AND patient_id = 3;

INSERT INTO doctor_patient_notes (doctor_id, patient_id, note, updated_at) VALUES
  (101, 3, 'Reports occasional dizziness ~1hr after evening Metformin dose. Advised taking with a light snack. Revisit if it persists past two weeks.', NOW() - INTERVAL 3 DAY);

