# KARE (MediRemind) — Progress Log

A chronological record of what's been built so far. For the _current_ design
system rules and file-by-file reference, see `assets/design.md` — that file
is the living source of truth; this one is a timeline for the project
report / viva.

---

## 1. Design system pivot — "Ocean" theme

Replaced the earlier dark "Wavy.ai" theme with a light, ocean-inspired look:
sky-blue canvas (`#c7e7ff`), white rounded cards, a single accent color
(`#55acee`), and Poppins as the working font stack. All values were moved
into `shared/tokens.css` as CSS custom properties, so no component file
hard-codes a color, radius, or shadow directly.

## 2. Authentication (login / signup)

- `auth/login/` and `auth/signup/` wired to MySQL via `mysqli` prepared
  statements (`auth/login/controller.php`, `auth/signup/controller.php`).
- Server-side validation (name length, email format, phone pattern,
  password length/match, terms acceptance).
- Signup creates a user with `role='patient'`, `status='active'`.
- Passwords stored and compared in **plain text** — a deliberate, accepted
  tradeoff for this project's academic scope, not an oversight.
- On successful login, the session is populated with `user_id`, `name`,
  `email`, and `role` (see §12 of `design.md` for why these exact keys
  matter).

## 3. Toast / flash message component

`shared/toast/` — a reusable post-redirect-get flash system. Any controller
sets `$_SESSION['toast'] = ['type' => ..., 'messages' => [...]]` before
redirecting; `toast.php` renders the container and reads/clears that session
key, `toast.js` animates each message in and auto-dismisses it. Used by
login, signup, and the profile forms.

## 4. Dashboard shell

- `shared/user/sidebar.php` and `shared/user/navbar.php` — reusable,
  data-driven navigation components. Sidebar links come from a plain PHP
  array (`$mrNavGroups`), so adding a page is a one-line change.
- `pages/user/home.php` — the first real page built on the shell: greeting
  header, stat cards, and today's medication schedule table, all currently
  backed by placeholder PHP arrays (DB wiring for this page is still
  pending).
- Scroll-entry fade-in animation system (`[data-mr-scroll-entry]` +
  `IntersectionObserver`) for staggered content reveals.

## 5. Profile page

Added `pages/user/profile.php` (later moved — see §6) with:

- A profile summary (avatar initial, name, email, role/status badges),
  queried fresh from the `users` table by `$_SESSION['user_id']` rather
  than trusting session data.
- An **"Edit details"** form (name, email, phone) — `profile_controller.php`
  re-checks email uniqueness against other users before saving.
- A **"Change password"** form (current / new / confirm) — verifies the
  current password against the DB (plain text, same convention as login)
  before updating.
- Both forms follow the same PRG + toast pattern as login/signup.

**Bug caught along the way:** `home.php` and `navbar.php` were reading
`$_SESSION['user_name']` / `['user_email']`, but `login/controller.php`
actually sets `$_SESSION['name']` / `['email']`. The mismatch meant the
dashboard silently showed placeholder data ("Reji Mathew") instead of the
real logged-in user after every login. Fixed in both files.

## 6. Folder restructure — `pages/user/profile/`

Profile's files were moved from `pages/user/profile.php` into their own
`pages/user/profile/` subfolder. Since `sidebar.php`/`navbar.php` are shared
across pages at different folder depths, their nav links, the logout
action, and the search form could no longer be hard-coded relative paths.

Introduced `$mrRootBase` — a single variable each page sets before
including `sidebar.php`/`navbar.php`, describing the relative path back to
the project root (`'../../'` for pages directly under `pages/user/`,
`'../../../'` for `pages/user/profile/`, and so on). Every link those two
components build is now `$mrRootBase . '...'`, so any future page can be
nested at any depth without breaking shared navigation. Full details in
`design.md` §12.

## 7. Logout confirmation modal

- Built `shared/modal/` — a **generic** reusable confirm dialog, not a
  one-off logout popup. Any button anywhere can trigger it by adding
  `data-mr-confirm` + `data-mr-confirm-action` (and optional title/message/
  label/icon/variant) instead of submitting a form directly.
- Created `auth/logout.php`, which didn't exist before despite being
  referenced by the navbar — it now clears the session/cookie and redirects
  to login.
- Wired the navbar's logout button to open the modal (`variant="danger"`,
  tinting the icon/confirm button red) instead of submitting immediately.
- Wired `modal.php`/`.css`/`.js` into `home.php` and `profile.php`.

Design rationale and full API in `design.md` §13.

## 8. Address fields — state & district on profile

- Added `states` and `districts` reference tables, seeded with all 28 Indian
  states + 8 Union Territories and their ~780 districts (`districts.state_id`
  FKs to `states.id`).
- `users` gained nullable `state_id` / `district_id` columns, FK'd to the new
  tables with `ON DELETE SET NULL` — existing accounts aren't broken by the
  addition; both fields simply show as "not set" until a user picks them.
- `profile.php`'s "Edit details" form gained a State and a District
  `<select>`. The district list is:
  - **server-rendered on page load**, based on the user's saved `state_id`,
    so the page is correct even before any JS runs;
  - **re-fetched via AJAX** (`pages/user/profile/get_districts.php`)
    whenever the state dropdown changes, so switching states always shows
    the right districts without a full page reload.
- `profile_controller.php` validates both fields server-side: a submitted
  district must actually belong to the submitted state, and a district
  can't be saved without a state.

## 9. Issue reporting page (user-facing)

- New `reports` table: `user_id`, `subject`, `message`, `status` (`open` /
  `in_progress` / `resolved` / `closed`), `admin_reply`, `replied_at`, plus
  the usual timestamps. The schema already carries what an admin reply flow
  will need, even though that admin-side UI doesn't exist yet.
- Built `pages/user/report/report.php` + `report_controller.php` — same
  PRG + toast pattern as the profile forms: a form to submit a new report,
  and a list of the user's own past reports showing a status badge, filed
  timestamp, the message, and (once populated) the admin's reply with its
  own timestamp.
- Added a "Report Issue" sidebar entry (`key: report`) alongside an existing
  "Reports" analytics entry (`key: reports`) in `shared/user/sidebar.php`.

**Bugs caught along the way:**

- The page and its controller were first scaffolded as `reports.php` /
  `reports_controller.php` directly under `pages/user/`, then moved into a
  `pages/user/report/` subfolder with singular filenames. The move needed
  three things updated in tandem that are easy to miss: `$mrRootBase`
  (`'../../'` → `'../../../'`, one folder deeper), every hard-coded
  `shared/...`/auth-redirect path at that same new depth, and the
  `<form action>` / `<link>` / `<script>` tags pointing at the renamed
  `report.css` / `report.js` / `report_controller.php` files. The `reports`
  _database table_ name was deliberately left untouched throughout — only
  file references changed.
- `report.php` was setting `$activePage = 'reports'` (left over from before
  the rename), which matched the sidebar's _Reports_ (analytics) item
  instead of its own _Report Issue_ item — so the wrong sidebar link
  highlighted as active. Fixed by setting `$activePage = 'report'` to match
  its actual nav key.

---

## 10. Home dashboard wired to real data + auth-guard fix

- **Bug fix:** `home.php`'s auth guard had been left commented out, making
  the dashboard viewable without logging in. Re-enabled it (redirects to
  `auth/login/index.php`), matching every other authenticated page.
- Added `medicines`, `medicine_schedules`, and `dose_logs` tables (plus
  `db/04_reports_and_meds.sql` for anyone rebuilding the DB from scratch —
  the original backup only had `users`; `states`/`districts`/`reports`
  from §8/§9 above were also missing from the dump and have their own
  migration file, `db/02_states_districts.sql` + `03_seed_states_districts.sql`,
  seeded with 36 states/UTs and 391 real districts).
- `home.php`'s stat cards and today's-schedule table now query these
  tables for the logged-in user instead of using hard-coded PHP arrays.
  Added an empty-state message for users with no medicines yet.
- New `assets/helpers/dose_logs.php` — `ensure_todays_dose_logs()` backfills
  today's `dose_logs` rows for any active schedule that doesn't have one
  yet, so a medicine added mid-day shows up immediately without waiting
  on a cron job. (Real missed-dose *detection* via cron is still a
  separate, not-yet-built feature — this only guarantees the row exists.)

## 11. Medicine & schedule management (`pages/user/schedule/`)

- `schedule.php` + `schedule_controller.php` — full CRUD for a patient's
  medicines and their daily reminder times, plus marking today's doses
  taken/missed. Same PRG + toast pattern as profile/report.
  - **Add a medicine** — name, dosage, notes, and one or more reminder
    times (dynamic add/remove rows via `schedule.js`).
  - **Edit** — the "Edit" button on a medicine card pre-fills the same
    form (via JS) and switches it to `update_medicine` mode. Editing
    replaces the medicine's time list wholesale, which cascades and
    clears dose history for removed times — an accepted simplification,
    called out in a code comment for anyone extending this later.
  - **Delete** — routed through the shared confirm modal; since that
    modal's hidden form has no room for extra fields, the medicine id
    travels in the confirm button's query string
    (`schedule_controller.php?action=delete_medicine&medicine_id=…`)
    alongside the POST method.
  - **Mark taken / missed** — small per-row forms next to each of today's
    upcoming doses.
  - All write actions re-check `user_id` ownership server-side before
    touching a row (a user can't mark or edit another user's dose/medicine
    by guessing an id).
- Sidebar's `schedule` entry now points at `schedule/schedule.php`
  (previously linked to a `schedule.php` that didn't exist yet), following
  the same nested-folder + `$mrRootBase` convention introduced for
  `report/` in §6.

## 12. Settings page (`pages/user/settings/`)

- Notification preferences (`notify_email`, `notify_sms` columns added to
  `users`) — toggled with a simple checkbox-based switch, no new table
  needed.
- Account deactivation: re-verifies the current password (same plain-text
  convention as login), flips `status` to `deactivated`, destroys the
  session, and redirects to login. `login/controller.php` already
  refuses non-`active` accounts, so a deactivated user is immediately
  locked out — verified by testing the login attempt afterward.
- The deactivate form re-enters the password as its own confirmation
  step, backed by a plain `confirm()` in JS rather than stretching the
  shared confirm modal (whose hidden form has no room for a password
  field) — noted in code so it's clear why this page doesn't use it.
- Navbar's "Account settings" and "Contact support" links, previously
  pointing at `settings.php` and a `tickets.php` that never existed,
  now point at the real settings and report-issue pages.

## 13. Reports — personal adherence analytics (`pages/user/reports.php`)

- Distinct from `report/` ("Report an Issue" — a support ticket to the
  admin). This is a read-only view of the patient's own `dose_logs`:
  30-day adherence rate, taken/missed/upcoming counts, a per-medicine
  breakdown with progress bars, and a 20-row recent history table.
  No controller — nothing on this page writes data.

## 14. Prescriptions (`pages/user/prescriptions/`)

- Upload (PDF/JPG/PNG, 2MB max, extension + size validated server-side),
  list, view, and delete. New `prescriptions` table.
- Files are stored at `assets/uploads/prescriptions/{user_id}/{random}.{ext}`
  and linked to directly rather than streamed through an access-gated
  PHP script — the random 32-hex-char filename is effectively
  unguessable, an accepted tradeoff for this project's scope (called out
  in a code comment; a production system would re-check session
  ownership on every file request instead).
- No OCR/text-extraction — still a separate, not-yet-built feature.
- Deleting removes both the DB row and the file on disk; verified this
  and the upload validation (including a rejected `.exe`) and cross-user
  delete protection (a second user can't delete the first user's file by
  guessing its id) by testing directly against the running server.

## 15. Doctors directory + connection requests (`pages/user/doctors/`)

- New `doctor_connections` table (`patient_id`, `doctor_id`, `status`,
  optional `message`, timestamps) plus a `specialty` column on `users`
  and 3 seeded demo doctor accounts (signup only ever creates
  `role='patient'`, so there was otherwise no way to populate this list
  in dev — see `db/05_user_side_modules.sql`).
- Patients can browse active doctors and send a connection request with
  an optional note, see pending/accepted/declined status per doctor,
  cancel a pending request, or disconnect from an accepted one.
- This only builds the **patient-initiated half** of the lifecycle
  described in the old "what's next" list — a doctor-facing area to
  accept/decline requests doesn't exist (out of scope for "the user
  side"). For this session's testing, accepted connections were created
  directly in the DB to simulate that side.
- Verified duplicate-request rejection and that a connection to a
  non-existent/inactive doctor id is rejected.

## 16. Messages — polling-based chat (`pages/user/messages/`)

- New `messages` table, one thread per **accepted** `doctor_connections`
  row. Two-pane UI: conversation list + thread, a composer that posts via
  normal PRG, and `messages.js` polling `messages_poll.php` every 3s via
  `fetch` to pick up new messages without a full reload.
- Every entry point (page load, send, poll) independently re-checks that
  the connection belongs to the logged-in patient and is `accepted` —
  verified a second user gets 403/no-op rather than another patient's
  messages when probing another patient's connection id directly.
- The sidebar's "Messages" badge is now a real unread count (`messages`
  gained a `read_at` column, set when a thread is opened) instead of the
  hard-coded placeholder `3` it shipped with.

## 17. Help & FAQ (`pages/user/help.php`) + search (`pages/user/search.php`)

- Static FAQ page (accordion, no DB) covering every module above —
  finally gives the sidebar footer's "Help & FAQ" link somewhere to go.
- The navbar's search form posted to a `search.php` that never existed;
  built it as a simple `LIKE`-based search over the logged-in user's own
  medicines and prescriptions (no full-text index needed at this scale).
  Placeholder text updated from "Search patients, medicines,
  prescriptions" to drop the "patients" reference (see §18).

## 18. Removed: "My Patients" sidebar item

- The sidebar's `patients` entry (linking to a `patients.php` that was
  never built) didn't correspond to anything the data model supports —
  `users.role` is `enum('patient','doctor','admin')` with no
  caretaker-manages-multiple-patients relationship anywhere in the
  schema. Rather than build a feature the schema can't back, or leave a
  dead link, the nav item was removed. If a caretaker role is added
  later, this would need a real `patient_id`/`caretaker_id` relationship
  table first.

## 19. Doctor-facing portal (`pages/doctor/`, `shared/doctor/`)

Builds the doctor's half of the connection + messaging lifecycle that
§15/§16 only started from the patient side, plus a doctor dashboard and
account page. New role-based login routing and a `require_role()` guard
back all of it.

- **Role-based login routing** (`auth/login/controller.php`) — doctors
  now land on `pages/doctor/home.php`, patients on `pages/user/home.php`
  (unchanged). An `admin` login is signed out with a toast explaining
  the admin portal isn't built yet, rather than dropping them into
  either dashboard.
- **`assets/helpers/auth.php`** — `require_role('doctor', $mrRootBase)`,
  used at the top of every page under `pages/doctor/`. A logged-out
  visitor is sent to login; a logged-in patient hitting a doctor URL is
  redirected to their own home instead of either an error page or (worse)
  a UI built for the wrong role.
- **`shared/doctor/sidebar.php` + `navbar.php`** — same conventions as
  the patient versions (`$activePage`, `$mrRootBase`), trimmed to what a
  doctor needs. Sidebar badges (pending requests, unread messages) are
  computed the same way as the patient sidebar's message badge (§16).
- **Dashboard** (`pages/doctor/home.php`) — pending-request count, active
  patient count, unread messages, and a preview of the most recent
  pending requests.
- **Requests** (`pages/doctor/requests/`) — accept/decline pending
  connection requests, with the patient's optional note shown. Verified
  a second doctor can't act on a request addressed to someone else (the
  update is scoped to `doctor_id`, not just `id`).
- **My Patients** (`pages/doctor/patients/`) — connected patients with a
  30-day adherence badge (same shape of query as the patient's own
  Reports page, §13, just scoped per patient) and an expandable
  read-only view of today's doses. Supports `?q=` from the navbar
  search. Disconnecting removes the `doctor_connections` row the same
  way the patient-side "Disconnect" button does.
- **Messages** (`pages/doctor/messages/`) — a straight mirror of
  `pages/user/messages/` (§16) scoped to `doctor_id`. Verified a full
  round trip: patient sends → doctor's page shows it and can reply →
  patient's poll picks up the reply live. Also verified the doctor poll
  endpoint rejects a patient's session outright (401), not just a
  wrong-connection 403.
- **Account** (`pages/doctor/account/`) — combines what the patient side
  splits into `profile/` and `settings/` into one page: edit details
  (name, phone, specialty), change password, notification preferences,
  and password-gated deactivation. Verified deactivation blocks
  subsequent login, same as the patient side (§12).

**Scope note:** this is the doctor side specifically — there's still no
admin portal (§ "what's next" below), and a doctor's view of a patient's
schedule is intentionally read-only (no editing another user's medicines
from here).

## 20. Admin module (`pages/admin/`, `shared/admin/`)

Closes the loop on the last major gap: the "Report an Issue" flow (§9)
had a patient-side submission UI and `admin_reply`/`replied_at` columns
sitting unused ever since, with no way for anyone to actually reply.
`role='admin'` logins were also just signed out with an apology toast.
Both are now real.

- **No schema changes needed** beyond seeding the first admin account
  (`db/06_admin_module.sql`) — `users.is_verified` (already existed,
  previously unused) is reused for doctor verification, and
  `reports.admin_reply`/`replied_at` (already existed since §9) are
  finally written to.
- **Login routing** (`auth/login/controller.php`) — `role='admin'` now
  routes to `pages/admin/home.php` instead of being signed out.
- **`assets/helpers/auth.php`** — `require_role()` extended with an
  `'admin'` case in its wrong-role redirect map, so a patient or doctor
  hitting an admin URL bounces to their own home, same as the existing
  doctor/patient cases.
- **`shared/admin/sidebar.php` + `navbar.php`** — same conventions as
  the other two role shells. Sidebar badges: open-report count, and
  unverified-doctor count.
- **Dashboard** (`pages/admin/home.php`) — patient/doctor counts, open
  reports, unverified doctors, an account-status breakdown
  (active/suspended/deactivated), and a preview of recent open reports.
- **Users** (`pages/admin/users/`) — every user, searchable (also fed by
  the navbar search) and filterable by role/status. Per row: suspend/
  reactivate (status-appropriate button, not a fixed pair), and a
  verify/unverify toggle on doctor rows. An admin cannot change their
  own status from this page (routed to Account instead) — verified this
  rejection actually fires rather than just hiding the button. Verified
  a suspended user is immediately blocked from logging back in.
- **Reports** (`pages/admin/reports/`) — two-pane list/detail view
  (same shape as the messaging pages), filterable by status, with a
  reply form that writes `admin_reply`/`replied_at`/`status`. Verified
  the **full loop**: patient submits → shows up in admin's Open filter →
  admin replies + marks resolved → patient's own Report page (§ report/)
  renders the reply and new status with zero changes needed there — it
  was already built to display these columns, just never had anything
  in them.
- **Account** (`pages/admin/account/`) — same shape as the doctor's
  Account page (details, password, notifications, deactivation), plus
  one admin-specific safeguard: deactivation is blocked if it's the last
  active admin account, since there'd be no way back into the portal
  afterward. Verified both sides of this — blocked with one admin,
  succeeds once a second active admin exists.

---

## 21. Planned additions (README §12), all implemented and tested

All five small features approved in README §12 are now built. One
schema migration (`db/07_planned_additions.sql`): `dose_logs` gained
`snooze_count`, and a new `doctor_patient_notes` table was added. The
other three features needed no schema changes at all.

- **Message "seen" indicator** — `messages.read_at` (already existed,
  set since §16) is now selected and rendered: a "Seen" checkmark shows
  under the sender's own last message once the recipient has opened the
  thread. Verified no false-positive before viewing, correct appearance
  after. Known simplification, documented in code: this is computed on
  page load only — an already-open thread doesn't retroactively gain the
  indicator via the 3s poll without a reload, since read-marking happens
  on page load, not on poll.
- **"Remember me" on login** — a checkbox on the login form; when
  checked, `session_set_cookie_params(30 days)` is called *before*
  `session_start()` in `auth/login/controller.php` (has to happen in
  that order). Verified via raw `Set-Cookie` headers: unchecked gives an
  ordinary session cookie, checked gives a 30-day `Max-Age`.
- **Snooze a dose** — new `snooze_dose` action in
  `pages/user/schedule/schedule_controller.php`, pushes
  `scheduled_for` by 15/30/60 minutes, capped at 3 snoozes per dose
  (`snooze_count`) so it can't be pushed forever, and only works on an
  `upcoming` dose. Verified: time advances correctly across repeated
  snoozes, the 4th attempt is rejected with a clear message, snoozing a
  `taken` dose is rejected, and cross-user access is blocked the same
  way `mark_dose` already was.
- **Calendar view of dose history** — a Table/Calendar toggle on
  `pages/user/reports.php`; the calendar is a month grid built from a
  day-grouped version of the same `dose_logs` join already used for the
  adherence stats, with prev/next month navigation via `?month=YYYY-MM`.
  Verified: correct day count including leading empty cells, today's
  cell highlighted, dots match actual seeded dose data, and an invalid
  `?month=garbage` value falls back to the current month instead of
  crashing.
- **Private notes on a patient** — new `doctor_patient_notes` table
  (one row per doctor-patient pair, upserted on save), a collapsible
  note panel on each patient card in `pages/doctor/patients/`, `save_note`
  in `patients_controller.php`. Verified: save, upsert-not-duplicate,
  clearing via empty submit deletes the row, saving a note for a patient
  the doctor isn't (accepted-)connected to is rejected, and — checked
  explicitly — **nothing on the patient side reads this table at all**,
  confirmed by grepping every patient-facing file and by loading every
  patient page while a note existed and finding zero occurrences of its
  content.

## 22. Full regression + edge-case pass across all three portals

Beyond the individual feature tests above, ran a dedicated pass looking
for crashes and logic errors across the whole app:

- Fresh `DROP DATABASE` + rebuild from all 7 migration files in order,
  then exercised every page across all three roles (patient, doctor,
  admin) — every page 200 when authenticated, every page 302 when not,
  zero PHP warnings/errors/notices in the server log across the entire
  run.
- **Empty states**: verified a brand-new user sees the correct
  empty-state message (not a blank page or error) on dashboard,
  schedule, reports, prescriptions, and messages.
- **Invalid/malformed input**: medicine with no reminder times, empty
  medicine name, malformed time value (`99:99`), negative/non-numeric/
  SQL-injection-shaped IDs passed to `mark_dose` — all rejected cleanly
  with no server errors (prepared statements + `(int)` casts held).
- **Auth edge cases**: duplicate-email signup, mismatched-password
  signup, missing terms checkbox, wrong password, nonexistent email,
  malformed email format — each produces its own correct, specific
  error message.
- **XSS resistance**: `<script>` and `<img onerror>` payloads in a
  medicine name and a report subject both render as escaped text
  (`&lt;script&gt;...`), never as executable markup, anywhere they're
  displayed.
- **File upload limits**: a 3MB file against the 2MB cap is rejected
  (caught by PHP's own `upload_max_filesize` before the app's own
  validation even runs).
- **Admin/doctor boundary conditions**: an invalid status value passed
  to `update_status`, verifying a non-doctor user as if they were a
  doctor, an empty-body report reply, a reply to a nonexistent report
  id, a doctor saving a note against `patient_id=0` — all rejected with
  clear messages, no crashes.
- **Cross-feature integration**: confirmed the calendar view's dots
  reflect the same underlying data as the dashboard/schedule after a
  snooze/mark action, and confirmed search results are correctly scoped
  to the logged-in user's own data (a search term matching another
  user's medicine correctly returns "no matches," not their medicine).

---

## 23. Payments — consultation fees & pay-to-connect (`pages/user/payments/`, `pages/doctor/payments/`, `pages/admin/payments/`)

Doctors previously had no way to charge for their time at all — the only
existing money concept was `prescription_requests.fee_amount`/`fee_paid`
(§ README 3.10), a pair of columns bolted onto one narrow workflow. This
adds a general, doctor-settable **consultation fee**, charged up front
before a patient can even send a connection request ("pay-to-connect"),
plus a proper payments ledger, checkout, receipt, and history views on
all three portals.

**Schema** (`db/12_consultation_payments.sql`):
- `users.consultation_fee` — nullable `decimal(8,2)`, doctor-only field
  (same shape as `specialty`). `NULL`/`0` means free — connecting stays
  exactly as it worked before this change, so every existing doctor and
  every existing connection is unaffected until a doctor opts in by
  setting a fee.
- `payments` — one row per simulated payment: `payer_id`/`payee_id` (both
  FK → `users.id`), `type` (`'consultation'` today, `'prescription_request'`
  reserved for later folding the existing fee-per-request flow into the
  same ledger — not done in this pass, to avoid touching a working
  feature), `reference_id` (the `doctor_connections.id` it paid for),
  `amount`, `status` (`pending`/`paid`/`failed`), `method` (always
  `'simulated'` today), `paid_at`, `created_at`.

**Still simulated, per README §7/§8** — no real payment gateway, no card
network contacted, no money actually moves. The checkout page collects
card-shaped fields (name, number, expiry, CVC) and validates their
*shape* server-side (regex — 13–19 digit number, `MM/YY` expiry, 3–4
digit CVC) purely so the flow feels real; the moment those pass, a
`payments` row is written straight to `status = 'paid'`. Same spirit as
the pre-existing `pay_fee` action on prescription requests.

**Doctor side — setting a fee:** `pages/doctor/account/account_controller.php`'s
existing `update_profile` action gained a `consultation_fee` field
alongside `specialty` — optional, validated as a non-negative number or
blank. The Account page shows a "Free consultation" or "$X consultation"
badge next to the doctor's specialty badge depending on whether it's set.

**Patient side — pay-to-connect:** `pages/user/doctors/doctors.php`'s
directory query now also selects `consultation_fee`. Each doctor card
shows "Free consultation" or "$X consultation fee" under the specialty
line. For an unconnected doctor with a fee set, the existing free
"Connect" button (which opens the note-only dialog) is replaced with a
"Pay & Connect" link to the new `pages/user/payments/checkout.php` —
doctors with no fee keep the exact original free-connect dialog
unchanged.

`checkout.php` shows an order summary (doctor, fee, total) and a card
form (name/number/expiry/CVC + the same optional note field the free
dialog has), posting to the new `pages/user/payments/payments_controller.php`'s
`pay_and_connect` action. That action, in one transaction:
1. Re-validates the doctor server-side (active, verified, has a fee) —
   never trusts the amount the checkout page rendered from.
2. Re-checks the `(patient_id, doctor_id)` uniqueness constraint so a
   double-submit can't charge twice or create two connections.
3. Inserts the `payments` row as `paid`.
4. Inserts the `doctor_connections` row (same effect as the free
   `request_connection` action).
5. Links `payments.reference_id` to the new connection id.
6. Notifies the doctor: *"\[Patient\] paid $X and sent you a connection
   request."* — one notification covering both events, reusing the
   existing `connection_request` type/link so it lands in the same place
   a free request would.

On success, redirects to `receipt.php?payment_id=…` rather than back to
the doctors list, so the patient sees confirmation immediately.

**Receipt & history:**
- `pages/user/payments/receipt.php` — a printable receipt
  (doctor/patient/amount/date/status), ownership-checked to the paying
  patient. "Download" is the browser's own print-to-PDF (a `@media
  print` block hides the sidebar/navbar/toast) rather than a new
  server-side PDF library, consistent with this project's no-build-step
  approach.
- `pages/user/payments/payments.php` — the patient's own payment
  history (new "Payments" sidebar item, `Care` group), total paid +
  count stat cards, a table of every payment with a link to its receipt.
- `pages/doctor/payments/payments.php` — the doctor's earnings view
  (new "Payments" sidebar item), same shape, scoped to `payee_id`,
  read-only (a doctor doesn't act on a payment — the patient pays once,
  up front).
- `pages/admin/payments/payments.php` — read-only, all-platform
  transaction list (new "Payments" sidebar item under Management),
  total volume + transaction-count stat cards. No admin action on a
  payment (no refund/void) is built — visibility only, matching how
  the admin Reports page is currently the only admin write surface.

**A pre-existing styling gap, not repeated here:** discovered while
building this that `.mr-table`/`.mr-table-wrap` (the sortable-looking
list styling used by the dashboard's and Reports' history tables) is
only actually defined in `pages/user/home.css` — `pages/user/reports.css`
uses the same classes but never defines them, so `reports.php`'s dose
history table has likely been running unstyled-by-default this whole
time (a narrower case of the same "moved to shared/components.css" bug
documented in the September 2026 batch for stat-cards). Not fixed here
(out of scope for this pass — flagged for whoever touches `reports.css`
next); this feature's own `pages/*/payments/payments.css` defines its
own copy of `.mr-table` rather than depending on `home.css` happening to
already be loaded, so the new Payments tables render correctly
regardless.

**Demo data** (`db/13_seed_demo_payments.sql`): two of the three seeded
demo doctors were given a fee (`rahul.nair@kare-demo.test` → $25,
`sara.thomas@kare-demo.test` → $40; `anjali.menon@kare-demo.test` stays
free, exercising the "no fee" path). Seeded three `payments` rows
reusing `testpatient`'s existing connections to the two paid doctors,
plus one new connection + payment for `kohai` → Dr. Sara Thomas, so all
three Payments views (patient/doctor/admin) show real, non-empty,
multi-patient/multi-doctor data on a fresh install.

**Tested live** against the sandbox (curl, exact request shapes, not
just reading the code):
- Fee editing on the doctor Account page — set, updated, and cleared
  back to free; badge updates correctly each time.
- Doctors directory — confirmed per-doctor branching (free badge + free
  Connect dialog vs. fee badge + Pay & Connect link) renders correctly,
  and that a doctor a patient is *already* connected/pending to shows
  the existing status badge regardless of fee (checkout is only offered
  for a genuinely new connection).
- Full checkout → payment → connection → notification → receipt chain,
  end to end, as a patient (`govindmanoj333@gmail.com`) connecting to a
  paid doctor for the first time: confirmed the `payments` row, the
  `doctor_connections` row, and the notification row all landed with
  the correct linked ids and amounts, and that the doctor's
  notification bell (`notifications_fetch.php`) showed the expected
  unread notification.
- **Double-submit / duplicate-connection guard**: attempted
  `pay_and_connect` a second time against a doctor the patient had
  already just paid and requested — correctly rejected before writing
  anything, verified no second `payments` row was created.
- **Card validation**: malformed card number/expiry/CVC rejected with a
  clear error, back on the checkout page, confirmed no `payments` row
  was written for the rejected attempt.
- Doctor earnings page and admin all-payments page both verified to
  show the correct aggregated totals and per-row data across multiple
  patients and doctors.
- All test data created during this pass (a throwaway connection,
  payment, and notification for `govindmanoj333@gmail.com` paying Dr.
  Sara Thomas) was deleted afterward — the sandbox DB was confirmed back
  at the seeded row counts from `13_seed_demo_payments.sql`.

**Deliberately not done:** folding the existing prescription-request fee
(§ README 3.10) into the new `payments` table — the `type` enum reserves
space for it (`'prescription_request'`), but the existing
`prescriptions`/`prescription_requests` fee flow is left untouched to
avoid risking a working feature; a future pass could migrate it once
there's a concrete reason to. No refunds, no partial payments, no
real gateway (Stripe/Razorpay or otherwise) — all explicitly out of
scope per README §7/§8 unless asked for.

---

## 24. Styling audit + doctor/admin financial visibility

A follow-up pass triggered by user-reported "some elements are shown without style," plus two feature requests: doctor-facing earnings visibility, and an admin-facing financial statistics page.

**Styling audit — real, widespread bugs found and fixed.** Ran a systematic check (every page's HTML classes vs. what's actually defined in its included stylesheets) rather than relying on the one gap already flagged in §23. Found this was far bigger than expected — the exact same "moved to shared" bug class documented for `.mr-stat-grid`/`.mr-stat-card` in the September 2026 batch (§6) had recurred repeatedly:

- **`.mr-medicine-card-name`** (the bold name/heading text) was only actually defined in 2 of 17 pages using it — doctor dashboard, doctor patients/prescriptions/account/requests/payments, admin dashboard/account/payments/reports, and user doctors/search/payments were all rendering it as unstyled plain text.
- **`.mr-table`/`.mr-table-wrap`** was only defined in `pages/user/home.css` — doctor's Patients and Prescriptions tables, **admin's Users table**, and user's Reports and Schedule tables were rendering as completely unstyled raw HTML `<table>`s (no borders, no header treatment, no row striping). The admin Users table in particular is a page every admin visits regularly.
- **`.mr-card-heading`/`.mr-card-heading-row`** was duplicated identically across 12 separate page CSS files instead of defined once — doctor's Prescriptions page and user's Help & FAQ page never got a local copy and rendered unstyled sub-headings.
- **`.mr-textarea`**, **`.mr-doctor-card`/`-avatar`/`-main`**, **`.mr-filter-select`**, **`.mr-rate-bar`/`.mr-rate-bar-fill`** — same duplicated-but-gapped pattern, smaller blast radius each but the same root cause.
- Also fixed two bugs introduced in §23 itself: `.mr-doctor-fee` had been defined in `pages/user/payments/payments.css` instead of `pages/user/doctors/doctors.css` (the only page that actually uses the class), so it was silently never applying; and `pages/user/schedule/schedule.css` was missing a `.mr-medicine-card-main` rule entirely (not a duplication bug, just never written), so the medicine-card's action buttons didn't get pushed to the card's right edge on wide screens.

**Fix, consistent with the project's own established pattern:** every one of the classes above was consolidated into `shared/components.css` as the single source of truth, with the now-redundant per-page copies removed. `design.md`'s own stated rule ("if a component is used on more than one page, its styles belong in components.css") was the guide for what got promoted vs. left page-local — `.mr-medicine-rate-item` and `.mr-snooze-form`, both flagged by the initial audit pass, turned out to be false positives (covered by a parent's `gap`/a generic descendant selector respectively) and were correctly left alone rather than "fixed" with unnecessary CSS.

**Doctor earnings — added to the dashboard, not just the dedicated page.** `pages/doctor/home.php` gained a fourth stat card ("Total earnings", sum of `payments` where `payee_id` = the doctor and `status = 'paid'`) and a "Recent payments received" preview list (mirroring the existing "Recent connection requests" pattern), both linking to the existing `pages/doctor/payments/payments.php`. The stat-card grid's rendering was generalized to optionally wrap a card in `<a>` instead of `<div>` when a `href` key is present — CSS Grid's blockification means an anchor behaves identically to a div as a grid item, so no layout changes were needed, just a small hover affordance (`border-color`/`translateY`) added to `shared/components.css` for `a.mr-stat-card` specifically.

**Admin financial statistics — `pages/admin/payments/payments.php` rebuilt from a plain transaction list into a real stats page:**
- Total platform revenue, this-month revenue (+ payment count), transaction count, and average payment — four stat cards.
- A new **revenue-by-doctor breakdown**: each doctor's total earned and payment count, with a proportional bar relative to the top earner (reusing the `.mr-rate-bar` pattern from the Reports page's per-medicine breakdown, now promoted to `shared/components.css` since it's used on 2 pages).
- The existing full transaction table kept, below the breakdown.
- `pages/admin/home.php` gained a fifth stat card ("Platform revenue", linking to the new page), using the same optional-link stat-card pattern added for the doctor dashboard.
- Admin sidebar's "Payments" label renamed to "Financial Stats" to match the page's new scope.
- Deliberately did **not** add a chart/graph library for this — the README (§1) explicitly notes Chart.js is used only on the user Reports page and nowhere else in the project; the revenue-by-doctor bars use the same lightweight CSS-only pattern already established, not a new dependency.

**A documentation bug fixed in passing:** the CHANGELOG's own "What's next" section heading was accidentally deleted when §23 was added in the previous session — the heading is restored below, content updated to reflect that payments and this styling pass are both now done.

**Verified live**, not just read: full regression sweep across all 21 pages spanning all three roles (patient/doctor/admin) — every page 200, zero PHP warnings/errors/notices in the server log; CSS brace-balance check across every stylesheet in the project (no orphaned rules from the consolidation edits); confirmed the admin Users table specifically now renders with real borders/header styling by inspecting the served HTML against the updated `shared/components.css`; confirmed the doctor dashboard's new earnings card and admin dashboard's new revenue card show live, correct figures matching the `payments` table ($25 for Dr. Rahul Nair, $105 platform-wide, $80/$25 split on the admin revenue-by-doctor breakdown); confirmed the database's row counts (`payments`, `doctor_connections`, `notifications`) were unchanged by this pass — no test data was written this time, since everything was read-only verification of existing seeded data.

**Also checked, per explicit request, and left untouched because it was already correct:** the "missed dose" display feature (an `upcoming` dose past its scheduled time displaying as "Missed" without changing the stored status) was verified present and working in all four places it should be — `pages/user/schedule/schedule.php`, `pages/user/home.php`, `pages/doctor/patients/patients.php`, and `pages/user/reports.php` (both its trend-chart query and its calendar-view query use an `effective_status` computed column for exactly this). Confirmed live against two genuinely-overdue `upcoming` rows already in the seed data (`dose_logs` ids 28 and 33, both for `testpatient`) — both display as "Missed" on the Schedule page and the Dashboard while `dose_logs.status` itself remains `upcoming` in the database, exactly as designed in §3.4. No code changes made for this item.

---

## What's next (not yet built)

All three sides — patient (§10–§18), doctor (§19), and admin (§20) —
are now functionally complete. Every sidebar link across all three
resolves to a real, tested page, and every `role` value in the `users`
enum has somewhere to log in to. The five small approved additions
(§21) are also done. A Payments round (§23) added consultation fees,
pay-to-connect, and financial visibility for doctors/admin; a follow-up
pass (§24) fixed a widespread styling-consolidation bug and expanded
that financial visibility further. What's left:

- Email/SMS notifications — preferences are collected on all three
  sides (§12, §19, §20) but nothing actually sends anything yet
  (PHPMailer, Fast2SMS/Twilio)
- Missed-dose detection via cron job (today's dose rows are created
  automatically — see §10 — but nothing yet auto-flips an overdue
  'upcoming' row to 'missed' in the database; the *display* already
  shows "Missed" for these everywhere, per §24's verification — this
  item is specifically about the stored value, a deliberate design
  choice per §3.4, not a bug)
- Prescription text extraction / OCR (upload + storage is done, §14)
- If a caretaker-manages-multiple-patients relationship is ever wanted
  (see §18), it needs a real relationship table before any UI for it —
  the current `users.role` enum has no caretaker role
- Folding the existing prescription-request fee (§3.10) into the newer
  general `payments` ledger (§23) — reserved for in the schema
  (`type = 'prescription_request'`) but not migrated, to avoid risking
  a working feature
- A real payment gateway, if ever wanted — everything in §23/§24 is
  simulated by design (§7/§8)

