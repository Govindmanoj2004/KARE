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

---

## What's next (not yet built)

The user (patient) side (§10–§18) and the doctor side (§19) are both
functionally complete — every sidebar link on both sides resolves to a
real, tested page. What's left:

- Email/SMS notifications — preferences are collected on both sides
  (§12, §19) but nothing actually sends anything yet (PHPMailer,
  Fast2SMS/Twilio)
- Missed-dose detection via cron job (today's dose rows are created
  automatically — see §10 — but nothing yet auto-flips an overdue
  'upcoming' row to 'missed')
- Prescription text extraction / OCR (upload + storage is done, §14)
- **Admin module** — user management, doctor verification, system
  health, and a reply/management view for the `reports` table from §9
  (user-side submission is done, admin-side reply is not). `auth/login/
  controller.php` already signs an admin login out with an explanatory
  toast rather than dropping them into either existing dashboard.
- If a caretaker-manages-multiple-patients relationship is ever wanted
  (see §18), it needs a real relationship table before any UI for it —
  the current `users.role` enum has no caretaker role
