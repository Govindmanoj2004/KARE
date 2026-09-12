# KARE (MediRemind) — Developer README

A medication-reminder web app connecting patients and doctors. Patients track medicines, doses, and prescriptions; doctors monitor connected patients and message them; both sides get in-app notifications when something needs their attention. A guest landing page at the project root introduces the product and links into login/signup for both roles. Built as a beginner/academic-scope PHP + MySQL project (procedural `mysqli`, no framework, no ORM — by design).

This README is written to be a complete, standalone reference for continuing development — with another AI agent, a different model, or a human — without needing to re-read the whole codebase first. For narrative history (what was built, in what order, and why), see [`assets/CHANGELOG.md`](assets/CHANGELOG.md). For visual/design rules (colors, spacing, component patterns), see [`assets/design.md`](assets/design.md) — that file is the **authoritative style reference** and should be read before building any new UI.

---

## 1. Tech Stack

| Layer | Technology |
|---|---|
| Server language | PHP 8.3, procedural style, no framework |
| Database | MySQL / MariaDB, accessed via `mysqli` (prepared statements throughout) |
| Frontend | Plain HTML + CSS + vanilla JS (no build step, no bundler, no framework) |
| Icons | Phosphor Icons via CDN (`unpkg.com/@phosphor-icons/web@2.1.1`) |
| Charts | Chart.js via CDN (`cdn.jsdelivr.net/npm/chart.js@4.4.4`) — used only on `pages/user/reports.php`'s trend chart (§13.2); nowhere else in the project |
| Fonts | Google Fonts "Poppins" (working fallback for the licensed "Euclid Circular B") |
| Typical local host | XAMPP (Apache + MySQL/MariaDB + PHP) |

**No package manager, no Composer, no npm.** Everything is flat files served directly by Apache/PHP's built-in server.

---

## 2. Project Structure

```
KARE/
├── index.php                       # Guest/landing page (public entry point) — see §13.3
├── guest.css / guest.js            # Landing page styles + fade-in-on-scroll (not part of the dashboard app-shell)
├── assets/
│   ├── connection/
│   │   └── Connection.php          # mysqli connection (root, no password, db_kare)
│   ├── helpers/
│   │   ├── auth.php                # require_role() — role-based page guard
│   │   ├── dose_logs.php           # ensure_todays_dose_logs() — auto-creates today's doses
│   │   └── notifications.php       # create_notification() — writes a row to the notifications table
│   ├── uploads/
│   │   └── prescriptions/{user_id}/{random}.{ext}   # uploaded prescription files
│   ├── svg/                        # logo, wave, google/facebook icons
│   ├── design.md                   # AUTHORITATIVE design system reference
│   └── CHANGELOG.md                # chronological build log with rationale
├── auth/
│   ├── login/            (index.php, controller.php, style.css, script.js)
│   ├── signup/            (index.php, controller.php, style.css, script.js — index.php also
│   │                        accepts ?role=patient|doctor to preselect the role toggle, used by
│   │                        the landing page's two signup CTAs)
│   └── logout.php
├── db/
│   ├── db_kare_backup_16-7.sql         # base schema: `users` table + 3 seed patients
│   ├── 02_states_districts.sql          # states/districts tables + users.state_id/district_id
│   ├── 03_seed_states_districts.sql     # seeds 36 states/UTs + 391 districts
│   ├── 04_reports_and_meds.sql          # reports, medicines, medicine_schedules, dose_logs
│   ├── 05_user_side_modules.sql         # prescriptions, doctor_connections, messages, notify_*/specialty cols, 3 demo doctors
│   ├── 06_admin_module.sql              # seeds the first admin account (no schema changes)
│   ├── 07_planned_additions.sql         # dose_logs.snooze_count, doctor_patient_notes table

│   ├── 08_seed_demo_data.sql            # realistic demo data for testpatient/kohai (medicines, doses, connections, messages)
│   ├── 09_prescription_requests.sql     # prescriptions.issued_by/is_current, new prescription_requests table
│   ├── 10_seed_prescription_requests.sql # demo data for the above
│   ├── 11_notifications.sql             # new `notifications` table (in-app bell dropdown, §3.11/§6)
│   ├── 12_consultation_payments.sql     # doctor-settable `users.consultation_fee` + new `payments` ledger table (§3.12, §14)
│   ├── 13_seed_demo_payments.sql        # demo payment data for the above (§3.12, §14)
│   └── db_kare_backup_1-8.sql           # full phpMyAdmin snapshot (schema + data) as of Sep 2 — covers migrations 1–8 only; 09/10/11/12/13 still need to run on top — see §3
├── doc/
│   └── Abstract.pdf                # original academic project abstract
├── pages/
│   ├── user/              # ── PATIENT-FACING PAGES ──
│   │   ├── home.php / home.css / home.js              # dashboard
│   │   ├── schedule/       (schedule.php, schedule_controller.php, .css, .js)
│   │   ├── profile/        (profile.php, profile_controller.php, get_districts.php, .css, .js)
│   │   ├── report/         (report.php, report_controller.php, .css, .js)     # "Report an Issue" (support ticket)
│   │   ├── settings/       (settings.php, settings_controller.php, .css, .js)
│   │   ├── reports.php / reports.css / reports.js      # personal adherence analytics (NOT the support-ticket "report") — stat cards, a Chart.js trend chart (§6/§13), per-medicine bar, table/calendar history toggle
│   │   ├── prescriptions/  (prescriptions.php, prescriptions_controller.php, .css, .js)   # upload/view/download own files, "current prescription" callout, request-update workflow (§3.10, §6)
│   │   ├── doctors/        (doctors.php, doctors_controller.php, .css, .js)
│   │   ├── payments/       (checkout.php, payments_controller.php, receipt.php, payments.php, .css, .js)   # pay-to-connect consultation-fee checkout, receipt, payment history (§3.12, §14)
│   │   ├── messages/       (messages.php, messages_controller.php, messages_poll.php, .css, .js)
│   │   ├── help.php / help.css / help.js                # static FAQ
│   │   └── search.php / search.css / search.js          # searches own medicines + prescriptions
│   └── doctor/             # ── DOCTOR-FACING PAGES ──
│       ├── home.php / home.css / home.js               # dashboard
│       ├── requests/       (requests.php, requests_controller.php, .css, .js)
│       ├── patients/       (patients.php, patients_controller.php, .css, .js)
│       ├── prescriptions/  (prescriptions.php, prescriptions_controller.php, .css, .js)   # fulfill/decline patient requests, ask a patient for an update
│       ├── messages/       (messages.php, messages_controller.php, messages_poll.php, .css, .js)
│       ├── payments/       (payments.php, .css, .js)   # read-only earnings view — payments received (§3.12, §14)
│       └── account/        (account.php, account_controller.php, .css, .js)   # combines profile+settings
├── pages/admin/            # ── ADMIN-FACING PAGES ──
│   ├── home.php / home.css / home.js                    # dashboard: counts, status breakdown, recent reports
│   ├── users/        (users.php, users_controller.php, .css, .js)      # all users, filter/search, suspend/reactivate, doctor verification
│   ├── reports/      (reports.php, reports_controller.php, .css, .js)  # reply to patient/doctor support tickets
│   ├── payments/     (payments.php, .css, .js)   # read-only, all-platform payment transactions (§3.12, §14)
│   └── account/      (account.php, account_controller.php, .css, .js) # combines profile+settings
├── shared/
│   ├── tokens.css                  # ALL design tokens (colors, radii, spacing) — single source of truth
│   ├── base.css                    # reset, body, scrollbar, scroll-entry animation system
│   ├── components.css              # sidebar, navbar, popover, buttons, forms, tables, badges, status pills
│   ├── toast/       (toast.php, toast.css, toast.js)     # flash-message system (PRG pattern)
│   ├── modal/       (modal.php, modal.css, modal.js)     # generic confirm-first dialog
│   ├── notifications/                                    # in-app notification bell (§6/§13)
│   │   ├── notifications_fetch.php     # GET — JSON: unread_count + recent notifications for the session user
│   │   ├── notifications_controller.php # POST — action=mark_read|mark_all_read
│   │   ├── notifications.css            # dropdown styling (reuses .mr-popover from components.css)
│   │   └── notifications.js             # open/close, initial load + 20s poll, click-to-mark-read
│   ├── user/        (sidebar.php, navbar.php)             # patient nav shell — navbar.php includes the notification bell
│   ├── doctor/      (sidebar.php, navbar.php)             # doctor nav shell — same
│   └── admin/       (sidebar.php, navbar.php)             # admin nav shell — same
```

**Note on `start_sandbox.sh`:** earlier revisions of this README referenced a `start_sandbox.sh` at the project root for spinning up a Linux dev sandbox automatically. That script is not present in this checkout — if you need one, write it from scratch following §10's manual steps rather than assuming it exists; don't rely on it being there.

---

## 3. Database Schema (current, as of last migration)

Apply migrations from `db/` **in this exact order** — each depends on tables/columns the previous one created:

1. `db_kare_backup_16-7.sql`
2. `02_states_districts.sql`
3. `03_seed_states_districts.sql`
4. `04_reports_and_meds.sql`
5. `05_user_side_modules.sql`
6. `06_admin_module.sql`
7. `07_planned_additions.sql`
8. `08_seed_demo_data.sql` — realistic demo data (medicines, dose history, connections, messages, a prescription, a support ticket) for `testpatient@example.com` and `kohai79941@gmail.com`, so a fresh install isn't empty. Safe to re-run (deletes-then-inserts its own rows).
9. `09_prescription_requests.sql` — adds `prescriptions.issued_by` / `prescriptions.is_current`, and the new `prescription_requests` table (see §3.10). Required for the prescription-request workflow described in §6 and §13.
10. `10_seed_prescription_requests.sql` — demo data for the above (one fulfilled, one pending request for `testpatient@example.com`). Depends on the `doctor_connections` rows that `08_seed_demo_data.sql` creates (connection ids 101/102/103) — see the snapshot gotcha below if this file fails with a foreign-key error.
11. `11_notifications.sql` — adds the new `notifications` table (§3.11, §6/§13's in-app notification bell). No dependency on 09/10; can technically run any time after migration 1, but keep it last for a predictable apply order.
12. `12_consultation_payments.sql` — adds `users.consultation_fee` and the new `payments` table (§3.12). Required for the pay-to-connect consultation-fee workflow described in §14.
13. `13_seed_demo_payments.sql` — demo data for the above: gives two of the three seeded demo doctors a fee, and seeds `payments` rows against existing (and one new) demo connections. Depends on `08_seed_demo_data.sql`'s connection ids (101/102/103) — same snapshot-order caveat as `10_seed_prescription_requests.sql` below.

**Snapshot gotcha, confirmed while standing up a sandbox from scratch:** `db_kare_backup_1-8.sql` is a snapshot taken *before* `08_seed_demo_data.sql` existed, so importing it does **not** give you the `testpatient`/`kohai` demo connections that `10_seed_prescription_requests.sql` expects (connection ids 101/102/103). If you import the snapshot and then run `09` and `10` directly, `10` will fail with `Cannot add or update a child row: a foreign key constraint fails (prescription_requests, ... FOREIGN KEY (connection_id) REFERENCES doctor_connections)`. Fix: re-run `08_seed_demo_data.sql` explicitly after the snapshot (it's safe to re-run) *before* running `09`/`10` — that creates the missing connection rows and `10` will apply cleanly. The step-by-step order in §10 below already accounts for this by running every file individually rather than relying on the snapshot.

### 3.1 `users`

The single accounts table for all three roles. Doctors and patients share this table; distinguished by `role`.

| Column | Type | Notes |
|---|---|---|
| `id` | int, PK, AI | |
| `name` | varchar(150) | |
| `email` | varchar(255) | unique |
| `email_verified_at` | datetime, null | unused (no verification flow built) |
| `phone` | varchar(20), null | |
| `phone_verified_at` | datetime, null | unused |
| `state_id` | int, null, FK → `states.id` | patient address, `ON DELETE SET NULL` |
| `district_id` | int, null, FK → `districts.id` | patient address, `ON DELETE SET NULL` |
| `notify_email` | tinyint(1), default 1 | notification preference |
| `notify_sms` | tinyint(1), default 0 | notification preference — **no SMS is actually sent**, preference only |
| `specialty` | varchar(100), null | **doctor-only** field (e.g. "Cardiologist") |
| `consultation_fee` | decimal(8,2), null | **doctor-only** field, added in `12_consultation_payments.sql`. `NULL`/unset means a free consultation; a set value is what a patient pays to connect (§3.12, §14) |
| `password` | varchar(255) | **PLAIN TEXT, not hashed** — deliberate academic-scope tradeoff, documented everywhere it's compared |
| `role` | enum('patient','doctor','admin') | **no caretaker role exists** |
| `status` | enum('active','suspended','deactivated'), default 'active' | login blocked unless `active` |
| `is_verified` | tinyint(1), default 1 | |
| `last_login_at` | datetime, null | unused (never written to) |
| `failed_login_attempts` | tinyint unsigned, default 0 | unused (no lockout logic built) |
| `locked_until` | datetime, null | unused |
| `created_at` / `updated_at` | datetime | auto timestamps |
| `deleted_at` | datetime, null | unused (no soft-delete logic built) |

### 3.2 `states` / `districts`

```sql
states(id PK, name)
districts(id PK, state_id FK→states.id ON DELETE CASCADE, name)
```
Seeded with 36 Indian states/UTs and 391 districts (not exhaustive for every state, but real place names). Used only by the patient profile's address fields.

### 3.3 `reports`

Support tickets ("Report an Issue" — **not** the analytics "Reports" page).

```sql
reports(
  id PK,
  user_id FK→users.id ON DELETE CASCADE,
  subject varchar(150),
  message text,
  status enum('open','in_progress','resolved','closed') default 'open',
  admin_reply text null,
  replied_at datetime null,
  created_at, updated_at
)
```
User-side submission is fully built. **Admin-side reply is fully built** (`pages/admin/reports/`) — `admin_reply`/`replied_at` are now actively written to and displayed back on the patient's own report page.

### 3.4 Medicine reminder tables

```sql
medicines(
  id PK, user_id FK→users.id ON DELETE CASCADE,
  name varchar(150), dosage varchar(100) null, notes varchar(255) null,
  is_active tinyint default 1, created_at, updated_at
)

medicine_schedules(
  id PK, medicine_id FK→medicines.id ON DELETE CASCADE,
  time_of_day time,           -- e.g. 08:00:00, one row per daily reminder time
  is_active tinyint default 1, created_at
)

dose_logs(
  id PK, schedule_id FK→medicine_schedules.id ON DELETE CASCADE,
  scheduled_for datetime,      -- the actual date+time this dose was due
  status enum('upcoming','taken','missed') default 'upcoming',
  taken_at datetime null,
  snooze_count tinyint default 0,  -- capped at 3 by application logic (schedule_controller.php's snooze_dose)
  created_at
)
```

**Important behavior:** `dose_logs` rows for *today* are **not** pre-generated by a cron job — they're created lazily by `ensure_todays_dose_logs()` (see §5) whenever a patient visits their dashboard or schedule page. There is **no automated "missed" detection** — a dose only becomes `missed` if a human (patient or, read-only, doctor) marks it so, or the patient/doctor explicitly does. An overdue `upcoming` dose stays `upcoming` forever unless acted on. A patient can **snooze** an `upcoming` dose (pushes `scheduled_for` forward 15/30/60 min), capped at 3 times per dose via `snooze_count` — this is a manual, user-initiated action, not automated missed-detection.

### 3.5 `prescriptions`

```sql
prescriptions(
  id PK, user_id FK→users.id ON DELETE CASCADE,
  issued_by enum('patient','doctor') default 'patient',   -- added in 09_prescription_requests.sql
  is_current tinyint(1) default 0,                        -- added in 09_prescription_requests.sql
  title varchar(150), doctor_name varchar(150) null, notes varchar(255) null,
  file_path varchar(255),           -- e.g. assets/uploads/prescriptions/3/ab12.../file.pdf
  file_original_name varchar(255),
  uploaded_at datetime
)
```
Files: PDF/JPG/PNG, 2MB max, validated server-side. Stored at `assets/uploads/prescriptions/{user_id}/{32-hex-char-random}.{ext}` and served by **direct static link**, not an access-gated PHP script — the random filename is the only protection (see §7, Known Limitations). **No OCR/text-extraction** — files are stored as-is.

`issued_by` and `is_current` support the prescription-request workflow (§3.10, §6): `issued_by = 'doctor'` marks a row a doctor uploaded on a patient's behalf (still stored under the *patient's* upload folder — same access model as a self-upload); `is_current = 1` marks the one prescription a patient's "Current prescription" callout shows. Only one row per `user_id` should have `is_current = 1` at a time — enforced in application code (both controllers clear the previous current row before setting a new one), not by a DB constraint.

### 3.6 `doctor_connections`

The patient↔doctor relationship + its lifecycle.

```sql
doctor_connections(
  id PK,
  patient_id FK→users.id ON DELETE CASCADE,
  doctor_id FK→users.id ON DELETE CASCADE,
  status enum('pending','accepted','declined') default 'pending',
  message varchar(255) null,      -- patient's optional note sent with the request
  requested_at datetime, responded_at datetime null,
  UNIQUE(patient_id, doctor_id)    -- one relationship per pair; a declined pair cannot re-request
)
```

### 3.7 `messages`

Chat, one thread per `doctor_connections` row (only usable once `status = 'accepted'`).

```sql
messages(
  id PK,
  connection_id FK→doctor_connections.id ON DELETE CASCADE,
  sender_id FK→users.id ON DELETE CASCADE,
  body text,
  created_at datetime,
  read_at datetime null            -- set when the recipient opens the thread; drives unread badges
)
```
Delivery is **polling-based** (client `fetch`s a JSON endpoint every 3s) — not WebSockets, not push.

### 3.8 Admin — no new tables

`06_admin_module.sql` adds **no schema** — it only seeds the first admin account (`id=201`, `admin@kare-demo.test`). The admin module reuses two columns that already existed but were unused before it was built:
- `users.is_verified` — repurposed as the doctor-verification flag (toggled by admin, shown as a badge on doctor rows in `pages/admin/users/`)
- `reports.admin_reply` / `reports.replied_at` — finally written to by `pages/admin/reports/`

### 3.9 `doctor_patient_notes`

```sql
doctor_patient_notes(
  id PK,
  doctor_id FK→users.id ON DELETE CASCADE,
  patient_id FK→users.id ON DELETE CASCADE,
  note text,
  updated_at datetime,
  UNIQUE(doctor_id, patient_id)   -- one note per pair; save upserts, empty note deletes the row
)
```
Private to the doctor — **never read anywhere on the patient side** (verified by grep + by loading every patient page with a note present and confirming zero occurrences of its content). Rendered as a collapsible panel per patient card on `pages/doctor/patients/`.

### 3.10 `prescription_requests`

Added in `09_prescription_requests.sql`. Either side of an accepted `doctor_connections` pair can start a request; the *other* side fulfills or declines it.

```sql
prescription_requests(
  id PK,
  connection_id FK→doctor_connections.id ON DELETE CASCADE,
  requested_by enum('patient','doctor'),   -- who started it
  message varchar(255),
  status enum('pending','fulfilled','declined') default 'pending',
  fee_amount decimal(8,2) null,      -- doctor-set fee, optional
  fee_paid tinyint(1) default 0,     -- patient "pays" — simulated, no real gateway (see §7)
  fulfilled_prescription_id FK→prescriptions.id ON DELETE SET NULL, null,
  doctor_note varchar(255) null,     -- currently unused by any UI — reserved for a future decline/fulfill reason
  requested_at datetime, responded_at datetime null
)
```

**Two directions, two different fulfillment paths:**
- `requested_by = 'patient'` (patient asks their doctor for an updated prescription) → fulfilled by the **doctor**, via `pages/doctor/prescriptions/`'s "Fulfill" dialog: doctor uploads a new file + optional title/fee. This creates a new `prescriptions` row with `issued_by = 'doctor'`, sets it `is_current = 1` (clearing any previous current row for that patient), and links it back via `fulfilled_prescription_id`.
- `requested_by = 'doctor'` (doctor asks a patient to update their prescription on file) → fulfilled by the **patient**, via the normal upload form on `pages/user/prescriptions/` (now carrying a hidden `fulfill_request_id` when reached by clicking "Upload update" on the incoming ask). Same effect: new `is_current = 1` row, request marked fulfilled.

Declining either direction just sets `status = 'declined'` — no new prescription row. Both decline actions (`decline_request` on the doctor side, `decline_ask` on the patient side) go through the shared confirm modal, same as everywhere else in the app (§5).

`fee_amount`/`fee_paid` implement "there's a fee for this" (to-do request, not a real payment integration): the doctor names a number when fulfilling; the patient sees an "Unpaid"/"Paid" badge and a "Mark as paid" button that just flips `fee_paid` to 1 — no payment processor, no enforcement that blocks anything on non-payment. Consistent with this project's documented no-external-integrations scope (§7).

### 3.11 `notifications`

Added in `11_notifications.sql`. Backs the in-app notification bell in the navbar (§6/§13) — one row per notification event, read by `shared/notifications/notifications_fetch.php` and written by `assets/helpers/notifications.php`'s `create_notification()`.

```sql
notifications(
  id PK,
  user_id FK→users.id ON DELETE CASCADE,
  type varchar(50),           -- 'connection_request' | 'connection_response' | 'message' |
                               -- 'prescription_request' | 'report_reply' (free-form; the UI
                               -- doesn't branch on it today, but it's there for future filtering)
  body varchar(255),          -- plain-text sentence shown in the dropdown, already user-facing
  link varchar(255) null,     -- root-relative path e.g. 'pages/user/messages/messages.php',
                               -- no leading slash; null if there's nowhere useful to link
  read_at datetime null,      -- set by notifications_controller.php's mark_read/mark_all_read
  created_at datetime
)
```

No `notifications` row is ever read or written directly by page controllers other than through `create_notification()` — every write goes through that one helper, so adding a new notification-worthy event elsewhere is a one-line call, not a raw INSERT. See §13 for the exact call sites currently wired up and the request/response shape the shared confirm modal needs when triggering a mark-read action (not applicable here — mark-read is fetch-based, not confirm-modal-based).

### 3.12 `payments`

Added in `12_consultation_payments.sql`, alongside the `users.consultation_fee` column (§3.1). Backs the "pay-to-connect" consultation-fee workflow (§14): a doctor optionally sets a fee on their Account page; a patient pays it up front, before a `doctor_connections` row is even created.

```sql
payments(
  id PK,
  payer_id FK→users.id ON DELETE CASCADE,   -- the patient who paid
  payee_id FK→users.id ON DELETE CASCADE,   -- the doctor who was paid
  type enum('consultation', 'prescription_request') default 'consultation',
  reference_id int null,       -- e.g. doctor_connections.id once created
  amount decimal(8,2),
  status enum('pending', 'paid', 'failed') default 'pending',
  method varchar(50) default 'simulated',
  paid_at datetime null,
  created_at datetime
)
```

Still simulated, per §7/§8 — no real gateway, no card network is ever contacted. The `type` enum reserves `'prescription_request'` for eventually folding the existing `prescriptions.fee_amount`/`fee_paid` columns (§3.5) into this same ledger, but that migration wasn't done in this pass — the two fee mechanisms currently coexist rather than being unified. Every row today is written with `type = 'consultation'` by `pages/user/payments/payments_controller.php`'s `pay_and_connect` action, which also writes the matching `doctor_connections` row and links `reference_id` back to it in the same transaction. Read (never written) by the three Payments views: `pages/user/payments/payments.php` (patient history, scoped to `payer_id`), `pages/doctor/payments/payments.php` (doctor earnings, scoped to `payee_id`), and `pages/admin/payments/payments.php` (all-platform, no scoping — admin oversight only, no write actions built).

### Entity relationship summary

```
users (role=patient) ─┬─< medicines ─< medicine_schedules ─< dose_logs
                       ├─< prescriptions
                       ├─< reports (support tickets)
                       ├─< notifications
                       ├─< payments (as payer)
                       ├─(state_id/district_id)→ states/districts
                       └─< doctor_connections >─┬─ users (role=doctor, has consultation_fee)
                                                 ├─< messages
                                                 ├─< doctor_patient_notes (doctor-only, per patient)
                                                 └─< prescription_requests >─ prescriptions (fulfilled_prescription_id)

users (role=doctor) ─< payments (as payee)
users (any role) ─< notifications   (not just patients — doctors and admins receive them too)
```

---

## 4. Session & Auth Conventions

Set once, only in `auth/login/controller.php`, on successful login:

| Session key | Meaning |
|---|---|
| `$_SESSION['user_id']` | the user's `id` |
| `$_SESSION['name']` | display name |
| `$_SESSION['email']` | email |
| `$_SESSION['role']` | `patient` / `doctor` / `admin` |

**Any page needing the logged-in user's identity must read these exact keys** — there is a documented history (see CHANGELOG §5) of a bug where pages read `user_name`/`user_email` instead and silently showed placeholder data. Don't reintroduce a second naming convention.

For anything beyond name/email, **re-query the `users` table by `user_id`** rather than stuffing more into the session (`profile.php`/`account.php` do this).

### Login routing (role-based)

`auth/login/controller.php`:
- `role='patient'` → `pages/user/home.php`
- `role='doctor'` → `pages/doctor/home.php`
- `role='admin'` → `pages/admin/home.php`

### Page-level guards

- **Patient pages**: check `isset($_SESSION['user_id'])` only — redirect to login if not. They do **not** check `role`, so a doctor/admin account *can* technically browse patient URLs (harmless — data is scoped by `user_id` regardless of role, just semantically odd; not fixed, out of scope).
- **Doctor and admin pages**: use `require_role('doctor', $mrRootBase)` / `require_role('admin', $mrRootBase)` from `assets/helpers/auth.php`. This redirects a logged-out visitor to login, and a logged-in user of the *wrong* role to their own home — not an error page. **Use this helper for any new role-specific page.**

```php
require_once __DIR__ . '/../../assets/helpers/auth.php';
require_role('admin', $mrRootBase); // or 'doctor', 'patient', or a new role if one is added
```

### `$mrRootBase` — depth-independent shared links

`shared/user/sidebar.php`, `shared/user/navbar.php`, `shared/doctor/sidebar.php`, `shared/doctor/navbar.php`, `shared/admin/sidebar.php`, `shared/admin/navbar.php` are included from pages at different folder depths. Each including page must set `$mrRootBase` to the relative path back to the project root **before** including these:

```php
$mrRootBase = '../../';      // e.g. pages/user/home.php            (2 levels to root)
$mrRootBase = '../../../';   // e.g. pages/user/profile/profile.php (3 levels to root)
```
Every link the sidebar/navbar build is `$mrRootBase . '...'`. Any new page nested deeper than `pages/user/{file}.php` or `pages/doctor/{file}.php` must set this correctly or navigation breaks silently (wrong relative links, not a crash — easy to miss).

---

## 5. Key Shared Patterns (use these for any new feature)

### PRG + Toast (every form submission)

Every controller (`*_controller.php`) follows Post/Redirect/Get:
```php
function back_with_toast(string $type, array $messages, array $old = []): void {
    $_SESSION['toast'] = ['type' => $type, 'messages' => $messages];
    if (!empty($old)) $_SESSION['some_page_old'] = $old; // repopulate form on error
    header('Location: the_page.php');
    exit;
}
```
The page includes `shared/toast/toast.php` once in the body; it reads+clears `$_SESSION['toast']` and renders it via `shared/toast/toast.js`'s `initFlashToasts()`.

### Shared confirm modal (any destructive/confirm-first action)

`shared/modal/modal.php` (include once per page) + `shared/modal/modal.js`. Any button anywhere becomes a confirm-first action just by adding data attributes — **no changes to the modal files needed**:
```html
<button type="button" data-mr-confirm
    data-mr-confirm-action="controller.php?action=delete_x&id=5"
    data-mr-confirm-method="post"
    data-mr-confirm-title="Delete this?"
    data-mr-confirm-message="This can't be undone."
    data-mr-confirm-label="Delete"
    data-mr-confirm-variant="danger">
    Delete
</button>
```
**Caveat:** the modal's hidden form only carries `action` + `method` — no room for extra POST fields. Workaround used throughout: pass IDs via **query string** on the action URL (`?action=x&id=5`), read them with `$_GET['id'] ?? $_POST['id'] ?? 0` in the controller. If a confirm-first action needs a text field (e.g. deactivate-account's password), build a **second custom overlay** reusing the same `.mr-modal-overlay`/`.mr-modal` CSS classes (see `pages/user/doctors/doctors.php`'s connect-request dialog for the pattern) rather than stretching the shared modal's contract.

### Auto-generating today's doses

```php
require_once __DIR__ . '/../../assets/helpers/dose_logs.php';
ensure_todays_dose_logs($con, $userId);
```
Call this at the top of any page that reads a patient's today's-doses (dashboard, schedule). It backfills missing `dose_logs` rows for active schedules — idempotent, safe to call every page load.

### Ownership checks (mandatory on every write)

Every controller re-verifies that the row being modified belongs to the acting user **server-side**, not just relying on the UI hiding the button. Pattern:
```php
$stmt = mysqli_prepare($con, 'SELECT id FROM medicines WHERE id = ? AND user_id = ? LIMIT 1');
// ... if 0 rows, reject before doing anything
```
This has been verified by testing (not just written) for every module — see CHANGELOG for specific cross-user/cross-role attack tests that were run and blocked.

### File-per-feature convention

New feature = new folder under `pages/user/{feature}/` or `pages/doctor/{feature}/` containing `{feature}.php`, `{feature}_controller.php` (if it writes data), `{feature}.css`, `{feature}.js`. Update the relevant `sidebar.php`'s `$mrNavGroups` array to add the nav link (one array entry, nothing else to touch).

### CSS: check `shared/components.css` before writing a new rule

A recurring, real bug class in this project (documented three separate times now: the September 2026 batch's stat-card fix, §14's own `.mr-doctor-fee` mistake, and §15's wider audit) is a class getting defined in one page's CSS file, then reused on a second page that never actually includes that file — silently rendering unstyled. **Before adding a new CSS class, grep the codebase for it first.** If it's genuinely page-specific (used on exactly one page), a local rule in that page's own `.css` file is fine. If there's any chance a second page will reuse the same markup (a card pattern, a table, a heading style), put the rule in `shared/components.css` from the start rather than duplicating it — `design.md`'s own stated rule is "if a component is used on more than one page, its styles belong in components.css." §15 / CHANGELOG §24 has the full list of classes this bit the project on and were consolidated; don't reintroduce the same duplication pattern for new work.

---

## 6. Module Status

### ✅ Completed — Patient side (`pages/user/`)

| Module | Files | What it does |
|---|---|---|
| Auth | `auth/login/`, `auth/signup/`, `auth/logout.php` | Signup, login (role-routed), logout |
| Dashboard | `home.php` | Real stats (doses due/taken/missed) + today's schedule from DB |
| Schedule | `schedule/` | Add/edit/delete medicines + reminder times; mark doses taken/missed |
| Profile | `profile/` | Edit name/email/phone/state/district; change password |
| Report an Issue | `report/` | Submit support ticket; view own tickets + admin replies |
| Settings | `settings/` | Notification toggles; password-gated account deactivation |
| Reports (analytics) | `reports.php` | 30-day adherence rate, a Chart.js trend chart of daily taken/missed + adherence-rate line (§13.2), per-medicine breakdown, dose history, table/calendar toggle (both correctly persist "Missed" for an overdue-but-unconfirmed dose — see §7 note below) |
| Prescriptions | `prescriptions/` | Upload/view/download/delete PDF/JPG/PNG (2MB max); "Current prescription" callout; request an update from a connected doctor, with fee/payment tracking (§3.10) |
| Doctors | `doctors/` | Browse doctors (shows each doctor's free/paid consultation status), send/cancel connection requests; pay-to-connect checkout for doctors with a fee set (§3.12, §14) |
| Messages | `messages/` | Polling chat with accepted doctor connections |
| Payments | `payments/` | Consultation-fee checkout, printable receipt, and payment history (§3.12, §14) |
| Help & Search | `help.php`, `search.php` | Static FAQ; search own medicines/prescriptions |

### ✅ Completed — Doctor side (`pages/doctor/`)

| Module | Files | What it does |
|---|---|---|
| Dashboard | `home.php` | Pending requests / active patients / unread messages / total earnings stats, plus a recent-payments preview (§15, §3.12) |
| Requests | `requests/` | Accept/decline incoming connection requests |
| My Patients | `patients/` | Connected patients, 30-day adherence badge, read-only today's-doses view (also reflects the "Missed" overdue-display rule), search |
| Prescriptions | `prescriptions/` | Fulfill or decline prescription-update requests from patients (upload a new file + optional fee); ask a connected patient for an update; view sent-request status (§3.10) |
| Messages | `messages/` | Same polling chat system, doctor-scoped |
| Payments | `payments/` | Read-only earnings view — every consultation-fee payment received (§3.12, §14) |
| Account | `account/` | Combines profile + settings: details, password, notifications, deactivation; consultation fee is set here (§3.12, §14) |

### ✅ Completed — Admin side (`pages/admin/`)

| Module | Files | What it does |
|---|---|---|
| Dashboard | `home.php` | Patient/doctor counts, open reports, unverified doctors, platform revenue, account-status breakdown, recent open reports (§15, §3.12) |
| Users | `users/` | All users, searchable/filterable by role+status; suspend/reactivate; doctor verification toggle. Admin can't change their own status here (routed to Account) |
| Reports | `reports/` | Reply to support tickets (writes `admin_reply`/`replied_at`/`status`) — two-pane list/detail, filterable by status. Closes the loop with the patient-side "Report an Issue" flow |
| Payments | `payments/` | Financial statistics — total/monthly revenue, average payment, revenue-by-doctor breakdown, plus the full transaction list (§3.12, §14, §15) |
| Account | `account/` | Same shape as doctor's Account page, plus a safeguard blocking self-deactivation if it's the last active admin |

### ✅ Completed — Small approved additions (README §12 / CHANGELOG §21)

| Feature | Where | What it does |
|---|---|---|
| Message "seen" indicator | `pages/user/messages/`, `pages/doctor/messages/` | Shows "Seen" under the sender's last message once the recipient has opened the thread (reuses `messages.read_at`) |
| "Remember me" on login | `auth/login/` | 30-day session cookie when checked, ordinary session cookie otherwise |
| Snooze a dose | `pages/user/schedule/` | Push an `upcoming` dose 15/30/60 min, capped at 3 times (`dose_logs.snooze_count`) |
| Calendar view of dose history | `pages/user/reports.php` | Table/Calendar toggle; month grid with prev/next navigation, color-coded per-day dots |
| Private notes on a patient | `pages/doctor/patients/` | Doctor-only free-text note per connected patient (`doctor_patient_notes` table), never visible to the patient |

### ✅ Completed — September 2026 update batch

A larger round of fixes and features built on top of the original modules above. Listed here as a batch rather than folded into the tables above so it's easy to see what changed in this pass.

| Item | Where | What it does |
|---|---|---|
| Demo data seed | `db/08_seed_demo_data.sql` | Realistic medicines/dose history/connections/messages/prescription/support-ticket for `testpatient@example.com` and `kohai79941@gmail.com`, so a fresh install isn't empty |
| Shared stat-card CSS | `shared/components.css` | `.mr-stat-grid`/`.mr-stat-card` (and `.mr-layout-card`/`.mr-schedule-empty`) were previously only defined in `pages/user/home.css`, so the identical markup on the doctor dashboard, admin dashboard, and `reports.php` rendered unstyled. Moved to the shared file — this was the "no style" complaint for those pages |
| Chat send-button icon centering | `shared/components.css` | `.mr-btn` was missing `justify-content: center`; icon-only buttons at a fixed size (the message composer's send button) left-aligned their icon instead of centering it |
| Reports calendar month-nav bug | `pages/user/reports.php` | Changing months reset the Table/Calendar toggle back to Table. Now round-trips the selected view through the URL (`?view=calendar`) across the prev/next links |
| "Missed" display for overdue doses | `pages/user/schedule/`, `pages/user/home.php`, `pages/doctor/patients/`, `pages/user/reports.php` | A dose still `upcoming` in the DB but past its scheduled time now **displays** as "Missed" everywhere it's shown (today's-doses tables, dashboard, doctor's read-only view, the reports calendar dots and history table). This is display-only — the stored `dose_logs.status` stays `upcoming` until a human explicitly marks it Taken/Missed, preserving the no-automated-detection design documented in §3.4 |
| Redesigned reminder-time picker | `pages/user/schedule/` | Replaced the native `<input type="time">` (fiddly HH/MM/AM-PM segments) with three `<select>` dropdowns (hour, 5-minute steps, AM/PM), synced to a hidden field carrying the same `"HH:MM"` value the controller already expected — no backend changes needed |
| Doctor signup | `auth/signup/` | Patient/Doctor toggle on the signup form; Doctor reveals a required Specialty field. New doctors insert with `is_verified = 0` (self-registered, unverified) rather than `1` |
| Doctor verification gate on patient-facing directory | `pages/user/doctors/doctors.php` | Now filters on `is_verified = 1`, so a self-registered doctor is invisible to patients until an admin verifies them via `pages/admin/users/` — without this, the new signup flow would have let anyone appear as a legitimate doctor immediately |
| Download button on prescriptions | `pages/user/prescriptions/` | Added alongside the existing "View" link (which opens the file rather than downloading it) |
| Prescription-request workflow | `pages/user/prescriptions/`, `pages/doctor/prescriptions/` (new), `db/09_prescription_requests.sql` | Full two-way request/fulfill/decline flow between patient and doctor, with an optional fee. Detailed in §3.10 |
| **`$_POST`/`$_GET` action bug (12 controllers)** | see detailed writeup below | A real, pre-existing bug affecting confirm-modal-triggered actions app-wide — fixed |

**The `$_POST['action']` bug, in detail:** the shared confirm modal (`shared/modal/modal.php`) submits a POST request to a URL built from the trigger button's `data-mr-confirm-action` (e.g. `controller.php?action=delete_x&id=5`), but the modal's hidden form has **no input fields at all** — so the POST body is empty. That means `action` only ever arrives via `$_GET`, never `$_POST`. Two controllers (`schedule_controller.php`, `patients_controller.php`) already read `$_POST['action'] ?? $_GET['action'] ?? ''` and worked correctly. Twelve others — including the pre-existing `delete_prescription` action, not something added in this pass — read only `$_POST['action'] ?? ''`, which is **always empty** for any action reached through the confirm modal, silently making that action a no-op (the request still returns 200 and redirects back, so nothing *looks* wrong in the UI; the row just never actually changes). Confirmed via direct curl testing (bypassing the browser/JS entirely) that `delete_prescription` did not delete anything before the fix, and does after it.

Fixed identically in all twelve: `pages/doctor/prescriptions/prescriptions_controller.php`, `pages/doctor/account/account_controller.php`, `pages/doctor/requests/requests_controller.php`, `pages/doctor/messages/messages_controller.php`, `pages/admin/account/account_controller.php`, `pages/admin/reports/reports_controller.php`, `pages/user/prescriptions/prescriptions_controller.php`, `pages/user/doctors/doctors_controller.php`, `pages/user/profile/profile_controller.php`, `pages/user/report/report_controller.php`, `pages/user/settings/settings_controller.php`, `pages/user/messages/messages_controller.php`.

**✅ Verified (update: all twelve controllers re-tested).** Every confirm-modal-triggered action reachable from the patched controllers has now been exercised with the *exact* request shape the shared modal actually sends — `POST` to `controller.php?action=X&id=Y` with an **empty body** (not a normal form POST) — and confirmed against the database that the row actually changed, not just that the page returned a redirect:

- `cancel_connection` (`pages/user/doctors/`) — deletes the `doctor_connections` row. Verified.
- `disconnect_patient` (`pages/doctor/patients/`) — deletes the `doctor_connections` row. Verified (this controller already read `$_GET` correctly before the fix, per the original note below, but is now confirmed rather than assumed).
- `decline_request` (`pages/doctor/prescriptions/`) — sets `prescription_requests.status = 'declined'`. Verified.
- `decline_ask` (`pages/user/prescriptions/`) — sets `prescription_requests.status = 'declined'`. Verified.
- `delete_prescription` (`pages/user/prescriptions/`) — deletes the `prescriptions` row + file on disk. Verified.
- `delete_medicine` (`pages/user/schedule/`) — deletes the `medicines` row (cascades schedules + dose_logs). Verified (already-correct controller, confirmed rather than assumed).

The remaining six patched controllers (`pages/doctor/account/`, `pages/admin/account/`, `pages/doctor/requests/`, `pages/doctor/messages/`, `pages/user/messages/`, `pages/admin/reports/`, `pages/user/profile/`, `pages/user/report/`, `pages/user/settings/` — nine files, not six; see below) turned out **not to have any action currently reachable through the shared confirm modal** — a full grep of every `data-mr-confirm-action=` in the codebase turned up only the six actions above (plus `auth/logout.php`, which isn't a data-mutating controller). Every action on those nine files is a plain `<form method="post">` submit with the action carried as a normal hidden field, so `$_POST['action']` was always populated directly regardless of the bug — they were never actually reachable in a broken state from their own page's UI. (The original 12-controller patch list was written defensively, adding the `$_GET` fallback everywhere for consistency even where no current call site needed it — reasonable, just broader than what was strictly exploitable.) All nine were smoke-tested anyway for this pass:

- `update_profile` (`pages/user/profile/`) — verified (wrote a real field change, confirmed in DB).
- `create_report` (`pages/user/report/`) — verified (submitted a real ticket, confirmed in DB).
- `update_notifications` (`pages/user/settings/`, `pages/doctor/account/`, `pages/admin/account/`) — verified on all three roles.
- `deactivate_account` (`pages/user/settings/`, mirrored on doctor/admin account pages) — verified with both a wrong password (correctly rejected) and the correct password (correctly deactivated + logged out), using a disposable throwaway account created via the real signup flow rather than a demo account.
- Admin's "can't deactivate the last active admin" safeguard — verified it actually blocks the attempt (status stayed `active`) rather than just trusting the code path exists.
- `respond_request` (`pages/doctor/requests/`, accept/decline) and `send_message` (both messages controllers) and `reply_report` (`pages/admin/reports/`) — verified as part of wiring up notifications (§13); each produced the expected DB row change and the correct notification landed for the other party.

All test data created for this verification pass (throwaway connections, prescriptions, medicines, a throwaway account, a test report) was deleted afterward — the sandbox DB was confirmed back at its original seeded row counts.

### ✅ Completed — October 2026 update batch

A second follow-up round, built on top of the September batch above. Covers everything that was previously listed in the "❌ Not built" table — that table is gone now; every item in it is done. Full as-built details (call sites, data flow, test steps) are in §13.

| Item | Where | What it does |
|---|---|---|
| Working notifications | `db/11_notifications.sql`, `assets/helpers/notifications.php`, `shared/notifications/`, all three `navbar.php` files | Real in-app notification bell with unread count, dropdown, and mark-read — wired into 8 real events across both patient and doctor sides (connection requests/responses, messages, prescription requests/fulfillment/decline/fee-paid, report replies). Detailed in §13.1 |
| Real trend chart on Reports | `pages/user/reports.php`, `pages/user/reports.js`, `pages/user/reports.css` | A genuine time-series chart (Chart.js, loaded via CDN) showing daily taken/missed doses and the adherence-rate trend line over the last 30 days — sits above the existing per-medicine bar and calendar heatmap, doesn't replace either. Detailed in §13.2 |
| Guest/landing page | `index.php` (project root), `guest.css`, `guest.js` | A public entry point introducing the product, with CTAs into both signup flows (deep-linking the role toggle via `?role=patient`/`?role=doctor`) and into login. Logged-in visitors hitting `/` are redirected straight to their role's dashboard rather than shown a pitch. Detailed in §13.3 |
| Confirm-modal action verification | see the verified list above | Closed out the "not yet verified" follow-up from the September batch — every confirm-modal-triggered action across the app re-tested with the modal's exact request shape and confirmed against the database |

**Explicitly out of scope for this project, by design — not pending, not a gap to fill:** password hashing, file-access gating/streaming, equivalent security hardening (see §7), real email/SMS sending, prescription OCR, and a caretaker role. These are deliberate scope boundaries, not oversights — don't propose or implement them unless explicitly asked.

---

## 7. Design Decisions: No Security Hardening

This project **intentionally does not implement**, and should not be extended to implement:

- **Password hashing** — passwords are stored and compared in plain text everywhere (login, signup, change-password, deactivation across all roles).
- **File-access gating** — uploaded prescription files are served as plain static files, not through a session-checked streaming script.
- Any equivalent hardening (rate limiting, CSRF tokens, session rotation, lockout enforcement despite the unused `failed_login_attempts`/`locked_until` columns, etc.)

This is a deliberate scope decision for this academic/beginner-level project, not an oversight. **Do not propose, flag, or implement these** in future work on this codebase unless explicitly asked to. The sections below describe how the plain-text/ungated approach actually works, purely as an implementation reference — not as a list of problems to fix.

- **Passwords**: stored and compared as plain strings (`password === $storedPassword`) in `auth/login/controller.php`, `*/profile*_controller.php`, `*/account_controller.php`, and `*/settings_controller.php`. Keep this pattern for any new password-related code.
- **Prescription files**: served as plain static files at `assets/uploads/prescriptions/{user_id}/{32-hex-char-random}.{ext}`, linked to directly. The random filename is the only mitigation, by design — no streaming/session-check script needed.

## 8. Other Accepted Tradeoffs (non-security)

These are separate from §7 — general scope/complexity tradeoffs worth knowing before extending the code:

1. **Editing a medicine's reminder times replaces the whole list** (`schedule_controller.php`'s `update_medicine`) — deletes and re-inserts `medicine_schedules`, which cascades and clears `dose_logs` history for removed times.
2. **N+1 queries in doctor's "My Patients"** page — one query per patient for adherence + today's doses. Fine for a small demo patient list; would need batching for scale.
3. **No pagination anywhere** — schedule lists, message threads, prescription lists, etc. all load in full. Fine at demo data volumes.
4. **Doctor role has no verification step** — any account with `role='doctor'` in the DB is treated as a legitimate doctor. There's no signup flow for doctors at all; the 3 demo doctors were seeded directly via SQL (`05_user_side_modules.sql`) because there was otherwise no way to create one.

---

## 9. Test / Demo Accounts

Seeded by the migration files — safe to use for manual testing:

| Email | Password | Role |
|---|---|---|
| `govindmanoj333@gmail.com` | `12345677` | patient |
| `testpatient@example.com` | `test1234` | patient |
| `kohai79941@gmail.com` | `12341234` | patient |
| `anjali.menon@kare-demo.test` | `doctor1234` | doctor (General Physician, free consultation) |
| `rahul.nair@kare-demo.test` | `doctor1234` | doctor (Cardiologist, $25 consultation fee) |
| `sara.thomas@kare-demo.test` | `doctor1234` | doctor (Endocrinologist, $40 consultation fee) |
| `admin@kare-demo.test` | `admin1234` | admin |

Freshly-migrated accounts have **no medicines/dose history/connections** seeded — that demo data was only ever created ad hoc during testing, not shipped in the SQL files. To see the app with real data, either use it manually (add a medicine, connect to a doctor) or write your own seed SQL.

**Update:** as of `08_seed_demo_data.sql`/`10_seed_prescription_requests.sql` (see §3), `testpatient@example.com` and `kohai79941@gmail.com` now *do* come with realistic demo data out of the box — medicines, a mix of taken/missed/upcoming doses, doctor connections, messages, a prescription, a support ticket, and a couple of prescription requests. `govindmanoj333@gmail.com` has its own separate ad-hoc data from manual testing (left untouched). The three doctor accounts and admin account have no demo data of their own beyond what the patient-side seeds create via their connections.

**Update:** as of `13_seed_demo_payments.sql` (see §3, §3.12, §14), `testpatient@example.com` has two paid consultation-fee payments on record (to Dr. Rahul Nair and Dr. Sara Thomas), and `kohai79941@gmail.com` has a new accepted connection + payment to Dr. Sara Thomas — so the Payments pages on all three portals show real, non-empty, multi-patient/multi-doctor data out of the box.

---

## 10. Local Setup (XAMPP / Windows)

1. Copy the `KARE/` folder into `C:\xampp\htdocs\KARE`
2. Start **Apache** and **MySQL** in the XAMPP Control Panel
3. Create the database and run migrations, **in order**:
   ```cmd
   cd C:\xampp\htdocs\KARE
   "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS db_kare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\db_kare_backup_16-7.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\02_states_districts.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\03_seed_states_districts.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\04_reports_and_meds.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\05_user_side_modules.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\06_admin_module.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\07_planned_additions.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\08_seed_demo_data.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\09_prescription_requests.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\10_seed_prescription_requests.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\11_notifications.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\12_consultation_payments.sql
   "C:\xampp\mysql\bin\mysql.exe" -u root db_kare < db\13_seed_demo_payments.sql
   ```
   (No `-p` — XAMPP's default root user has no password, matching `assets/connection/Connection.php`'s hardcoded `root`/empty-password/`db_kare` config.)

   **Shortcut:** `db\db_kare_backup_1-8.sql` is a full snapshot (schema + data as of Sep 2) that replaces the first eight files above in one import — but it **predates** `08_seed_demo_data.sql`'s actual seed rows as well as `09`–`13`, so all of `08`–`13` still need to run afterward regardless of which path you take. Skipping the `08` re-run before `09`/`10` will make `10_seed_prescription_requests.sql` fail with a foreign-key error — see the "Snapshot gotcha" note in §3.
4. Verify: `SHOW TABLES;` should list 14 tables (`users`, `states`, `districts`, `reports`, `medicines`, `medicine_schedules`, `dose_logs`, `prescriptions`, `doctor_connections`, `messages`, `doctor_patient_notes`, `prescription_requests`, `notifications`, `payments`).
5. Browse to `http://localhost/KARE/` for the guest/landing page (§13.3), or straight to `http://localhost/KARE/auth/login/index.php` to skip it — log in as any of the accounts in §9, including `admin@kare-demo.test` for the admin portal at `pages/admin/`.

See §2's note on `start_sandbox.sh` — it's referenced by an earlier revision of this README but not present in this checkout; there's no automatic Linux sandbox script to fall back on, follow the manual steps above (adapted for `mysql`/PHP's built-in server instead of XAMPP paths) if you're not on Windows.

### 10.1 Linux sandbox (manual, tested) — Apache-free alternative

No `start_sandbox.sh` exists (see above), but here's the exact sequence that was used to stand up and test this project on a plain Ubuntu container with MariaDB + PHP's built-in server (no Apache, no XAMPP):

```bash
apt-get install -y php php-cli php-mysqli php-mysql php-mbstring mariadb-server mariadb-client
# php-mbstring is easy to miss — auth/login/controller.php calls mb_strtolower()
# and fails with an uncaught Error without it.

mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
mysqld_safe --datadir=/var/lib/mysql &        # use setsid+nohup if this needs to outlive the shell

mysql -u root -e "CREATE DATABASE IF NOT EXISTS db_kare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cd KARE/db
mysql -u root db_kare < db_kare_backup_1-8.sql
mysql -u root db_kare < 08_seed_demo_data.sql   # re-run — see the "Snapshot gotcha" note in §3
mysql -u root db_kare < 09_prescription_requests.sql
mysql -u root db_kare < 10_seed_prescription_requests.sql
mysql -u root db_kare < 11_notifications.sql
mysql -u root db_kare < 12_consultation_payments.sql
mysql -u root db_kare < 13_seed_demo_payments.sql

cd ..
php -S 127.0.0.1:8000 -t .
# then browse (or curl) http://127.0.0.1:8000/ for the landing page,
# or http://127.0.0.1:8000/auth/login/index.php directly.
```

`assets/connection/Connection.php`'s hardcoded `root`/no-password/`db_kare` needs no changes for this path either.

---

## 11. Guidance for Continued Development (for other agents/models)

1. **Read `assets/design.md` before writing any new UI** — it defines the exact color/radius/spacing/typography tokens and the checklist every new page must follow (CSS load order, `$activePage`, `$mrRootBase`, session key usage, toast/modal inclusion, scroll-entry animation attributes). It is the single source of truth for visual consistency.
2. **Read `assets/CHANGELOG.md`** for the *why* behind non-obvious decisions — several sections document bugs that were found and fixed (e.g. session key mismatches, folder-restructure path breakage) specifically so they aren't reintroduced.
3. **All three portals (patient, doctor, admin) are functionally complete.** Five rounds of work have landed on top of the original modules: the "September 2026 update batch" (doctor signup, the prescription-request workflow, "Missed" display for overdue doses, a redesigned time picker, several shared-CSS fixes, and a widespread `$_POST`/`$_GET` action-dispatch bug fix), the "October 2026 update batch" (working notifications, a real Chart.js trend chart on Reports, a guest/landing page, and full verification of the confirm-modal action fix — see §13), a **Payments round** (doctor-settable consultation fees, pay-to-connect checkout, receipts, and payment history across all three portals — see §14, CHANGELOG §23), and a **styling + financial-visibility round** (a widespread CSS-consolidation bug fix affecting 15+ pages, doctor/admin earnings dashboards, and an admin financial statistics page — see §15, CHANGELOG §24). **Nothing remains in a "not built" state** except what's explicitly out of scope by design (§7's security-hardening exclusions, plus real email/SMS sending, prescription OCR, a real payment gateway, and a caretaker role — see §6's October-batch note and §14). Any further work from here is genuinely new scope, not a gap to fill in.
4. When adding any new page: follow the folder-per-feature + PRG/toast + ownership-check conventions in §5 exactly — every existing module does, and deviating creates inconsistency an agent reading the codebase later won't expect. For a new *role-specific* area (a 4th role, say), mirror `pages/admin/` + `shared/admin/` + the `require_role()` pattern in §4 rather than inventing a new guard mechanism.
5. **Test by actually running the app**, not just reading the code — every module in this project was verified via curl/browser against a live PHP+MySQL server, including deliberately trying cross-user and cross-role access to confirm ownership checks actually reject them (e.g. the admin module's "can't suspend yourself" and "can't deactivate the last admin" checks were both verified to actually fire, not just assumed to work). CHANGELOG §22 documents a dedicated edge-case sweep (empty states, malformed input, XSS payloads, upload limits, boundary conditions) beyond just the individual feature tests — worth doing the same for any new work rather than only testing the happy path.

---

## 12. Small Additions — Implementation Notes (all built, CHANGELOG §21)

These five features were scoped here as a plan and are now fully implemented and tested (see CHANGELOG §21 for the test details). Kept as an as-built reference for the actual code locations and any deviations from the original plan.

### 12.1 "Remember me" on login — as built
`auth/login/index.php` has a `remember_me` checkbox; `auth/login/controller.php` reads `$_POST['remember_me']` and calls `session_set_cookie_params(60*60*24*30)` **before** `session_start()` when checked (order matters — cookie params can't be changed after the session starts). No DB change.

### 12.2 Snooze a dose — as built
`snooze_dose` action in `pages/user/schedule/schedule_controller.php`. Requires `status = 'upcoming'` and `snooze_count < 3`; on success, `scheduled_for = scheduled_for + INTERVAL ? MINUTE` and `snooze_count` increments. UI: a `<select>` (15/30/60 min) + button next to the existing Taken/Missed buttons, hidden once `snooze_count` reaches 3. Required the `dose_logs.snooze_count` column added in `07_planned_additions.sql`.

### 12.3 Calendar view of dose history — as built
`pages/user/reports.php` gained a Table/Calendar toggle (`data-mr-view-btn`/`data-mr-view-panel`, plain JS show/hide, no framework). The calendar queries `dose_logs` grouped by `DATE(scheduled_for)` and status for the month in `?month=YYYY-MM` (defaults to current month, falls back to current month on invalid input — regex-validated as `\d{4}-\d{2}`). Rendered as a 7-column grid with leading empty cells for the month's starting weekday, colored dots per status, today's cell outlined. No new table.

### 12.4 Private notes on a patient — as built
New `doctor_patient_notes` table (`07_planned_additions.sql`), `UNIQUE(doctor_id, patient_id)` so saving is an `INSERT ... ON DUPLICATE KEY UPDATE` upsert. `save_note` action added to the existing `pages/doctor/patients/patients_controller.php` (not a separate controller file as originally sketched — kept everything patient-card-related in one file). Ownership check requires an `accepted` `doctor_connections` row between the acting doctor and the target patient. Submitting an empty note **deletes** the row rather than storing blank text. Collapsible panel per patient card, toggled the same way as the existing "Today's doses" panel. Verified with a grep sweep that no patient-facing file references this table at all.

### 12.5 Message "seen" indicator — as built
`read_at` added to the message `SELECT`s in both `pages/user/messages/messages.php` and `pages/doctor/messages/messages.php` (previously fetched but not selected). A "Seen" checkmark renders under the sender's own **last** message in the thread only, when `read_at IS NOT NULL`. **Known simplification:** this is computed server-side on page load only — `messages_poll.php`'s live-append via `fetch` does not retroactively add the indicator to an already-rendered bubble when the other party reads it without the page reloading, since read-marking itself happens on page load, not via polling. Acceptable for this project's scope; flagged here for whoever extends the polling logic next.

---

## 13. October 2026 Update Batch — Implementation Notes (all built and tested)

As-built reference for the three items that used to be in the "❌ Not built" table (§6), plus the confirm-modal verification sweep. All four were implemented, wired up end-to-end, and tested against a live PHP + MariaDB sandbox during this pass (curl-driven, simulating exact request shapes — see each subsection).

### 13.1 Working notifications — as built

**Schema:** `notifications` table (`db/11_notifications.sql`) — `id`, `user_id` (FK → `users.id`, `ON DELETE CASCADE`), `type` (free-form string, e.g. `'message'`, `'prescription_request'`), `body` (plain-text sentence), `link` (root-relative path, nullable), `read_at` (nullable), `created_at`. See §3.11.

**Write path:** one helper, `assets/helpers/notifications.php`'s `create_notification($con, $userId, $type, $body, $link = null)`. Every controller that needs to notify someone requires this file and calls it — there's no other code path that writes to the table.

**Read path:** `shared/notifications/notifications_fetch.php` (`GET`, session-scoped, no role check needed since it filters by `$_SESSION['user_id']`) returns `{unread_count, notifications: [...]}` for the logged-in user, most recent 20. `shared/notifications/notifications_controller.php` (`POST`, `action=mark_read` + `id=`, or `action=mark_all_read`) marks rows read, scoped to the session user the same ownership-check way every other controller in this app is (§5) — a user can never mark another user's notification as read, verified by testing cross-role.

**UI:** all three `navbar.php` files (`shared/user/`, `shared/doctor/`, `shared/admin/`) got a `.mr-notif-wrap` dropdown next to (or replacing, on the patient side, where it was previously decorative) the bell icon — same `.mr-popover` shell the avatar menu already uses. `shared/notifications/notifications.js` handles open/close, an initial load on page load (so the unread dot is right even before the dropdown is opened), a 20-second poll (lighter than the 3-second chat poll — notifications aren't as latency-sensitive), and click-to-mark-read-then-navigate. `shared/notifications/notifications.css` holds the dropdown-specific styling.

Every page that includes a navbar also needs `shared/notifications/notifications.css` and `.js` — added to all 21 such pages alongside the existing `shared/modal/modal.css`/`.js` includes, same file-per-page convention already used for the toast/modal system.

**Trigger points wired up (8 events, both patient and doctor sides get notified depending on who acted):**

| Event | Controller | Notifies |
|---|---|---|
| Patient sends a connection request | `pages/user/doctors/doctors_controller.php` (`request_connection`) | The doctor |
| Doctor accepts/declines a request | `pages/doctor/requests/requests_controller.php` (`respond_request`) | The patient |
| Patient sends a chat message | `pages/user/messages/messages_controller.php` (`send_message`) | The doctor |
| Doctor sends a chat message | `pages/doctor/messages/messages_controller.php` (`send_message`) | The patient |
| Patient requests a prescription update | `pages/user/prescriptions/prescriptions_controller.php` (`request_update`) | The doctor |
| Patient fulfills a doctor's "please update" ask | `pages/user/prescriptions/prescriptions_controller.php` (`upload_prescription`, when `fulfill_request_id` is set) | The doctor |
| Patient declines a doctor's ask | `pages/user/prescriptions/prescriptions_controller.php` (`decline_ask`) | The doctor |
| Patient marks a prescription fee paid | `pages/user/prescriptions/prescriptions_controller.php` (`pay_fee`) | The doctor |
| Doctor asks a patient for an update | `pages/doctor/prescriptions/prescriptions_controller.php` (`request_update`) | The patient |
| Doctor fulfills a patient's request | `pages/doctor/prescriptions/prescriptions_controller.php` (`fulfill_request`) | The patient |
| Doctor declines a patient's request | `pages/doctor/prescriptions/prescriptions_controller.php` (`decline_request`) | The patient |
| Admin replies to a support ticket | `pages/admin/reports/reports_controller.php` (`reply_report`) | The patient who filed it |

(Table has 12 rows, not 8 — 8 distinct *events*, some of which appear as both directions of the prescription-request lifecycle.) Each was tested live: performed the action via curl as one account, then confirmed via `notifications_fetch.php` that the *other* account received exactly the expected notification body and link. All test data (temporary connections, prescriptions, medicines, notifications) was cleaned up afterward.

**Deliberately not done:** notifications aren't generated for anything time-based (a dose becoming due, say) — everything wired up is triggered directly by a user action in an existing controller. A due-dose notification would need a cron/polling mechanism this project doesn't have (consistent with §3.4's "no automated missed-detection" design). Not in scope unless asked for.

### 13.2 Real trend chart on Reports — as built

`pages/user/reports.php` already computed a month's worth of calendar data grouped by day; a near-identical query now also computes a **rolling 30-day** window (independent of whatever month the calendar is showing), grouped by day and effective status (same overdue-as-missed display rule as everywhere else, §3.4). PHP zero-fills any day with no doses so the chart always has a full, evenly-spaced 30-point x-axis, then hands the three arrays (`labels`, `taken`, `missed`, plus a computed `rate` per day, `null` when there were zero doses that day so Chart.js's `spanGaps` skips it instead of drawing a false 0%) to the frontend via a `<script type="application/json" id="mr-trend-data">` tag — kept separate from `reports.js` itself so the JS file stays pure JS, no inline PHP mixed in.

Chart.js is loaded via `<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js">` in the page's `<head>` — same no-build, no-bundler CDN pattern already used for Phosphor Icons (§1). `reports.js` reads the JSON script tag and renders a combo chart on a `<canvas id="mr-trend-chart">`: stacked taken/missed bars on a left "Doses" axis, an adherence-rate line on a right "Adherence %" axis. Sits in a new "Trend — last 30 days" panel between the stat-card grid and the existing per-medicine bar — doesn't replace either of those, all three views (per-medicine bar, 30-day trend chart, calendar heatmap) coexist.

When a patient has zero dose history in the window, the canvas and the JSON script tag are both omitted (PHP `if ($total > 0)`) and a "No dose history yet to chart" empty state renders instead — verified there's no dead `#mr-trend-chart`/`#mr-trend-data` reference left behind to trip up the JS in that case.

### 13.3 Guest/landing page — as built

New `index.php` at the **project root** (not a `guest/` subfolder — chosen so `http://localhost/KARE/` just works with no extra path segment), plus `guest.css` and `guest.js` alongside it. Deliberately doesn't include `shared/base.css` or the dashboard app-shell CSS — this is a normal-scrolling public page, not a fixed-height authenticated layout, so it gets its own minimal reset in `guest.css` and reuses only `shared/tokens.css` for color/spacing tokens.

Content: a hero (headline, subcopy, two signup CTAs + a login link, plus a small mock "today's doses" card and the same wave/ocean motif from the login/signup hero — reusing `assets/svg/wave.svg`, not a new asset), a 4-card feature grid, and a final CTA section. `guest.js` is just the fade-in-on-scroll behavior (`IntersectionObserver`), a lighter standalone copy of `base.css`'s scroll-entry system since this page doesn't include that file.

**Logged-in visitors are redirected, not shown the pitch:** `index.php` checks `$_SESSION['user_id']`/`['role']` at the top and sends patient/doctor/admin sessions straight to their own `home.php` — verified for all three roles.

**Signup deep-linking:** the landing page's two signup buttons go to `auth/signup/index.php?role=patient` and `?role=doctor`. `auth/signup/index.php` was given a small addition to support this: it now reads `$_GET['role']` as a fallback default (behind a failed-submit's `old('role', ...)` value, which still wins) for both the hidden `#role` input's initial value and the toggle button's initial `is-active`/`aria-selected` state — `script.js`'s existing `applyRole()` (already called on `DOMContentLoaded` based on the hidden field's value) picks this up with no JS changes needed. Verified `?role=doctor` renders with the Doctor tab pre-selected and the Specialty field visible; no `role` param still defaults to Patient as before.

### 13.4 Confirm-modal action verification — as built

See the "✅ Verified" note under §6's September-batch write-up for the full list and results. Summary: every action reachable through the shared confirm modal (`cancel_connection`, `disconnect_patient`, `decline_request`, `decline_ask`, `delete_prescription`, `delete_medicine`) was re-tested with the modal's *exact* request shape (`POST controller.php?action=X&id=Y` with an empty body — not a normal form-encoded POST, which would mask the bug this was meant to catch), and every plain-form action on the remaining patched controllers was smoke-tested too, including the admin "can't deactivate the last admin" safeguard actually firing rather than just existing in code.

---

## 14. Payments — Consultation Fees & Pay-to-Connect (as built)

A follow-up round addressing a real gap: doctors had no way to charge for their time. The only prior money concept was `prescriptions_requests.fee_amount`/`fee_paid` (§3.10) — a fee bolted onto one narrow workflow (a prescription update), simulated, with no ledger, no receipt, no history. This adds a general, doctor-settable **consultation fee**, charged up front before a patient can send a connection request ("pay-to-connect"), plus a proper `payments` ledger and checkout/receipt/history views across all three portals. Full narrative + test log in `assets/CHANGELOG.md` §23; this section is the as-built reference.

**Schema:** `users.consultation_fee` (nullable, doctor-only, like `specialty`) and a new `payments` table (`db/12_consultation_payments.sql`, §3.12). `NULL`/`0` means free — every existing doctor and connection is unaffected until a doctor opts in.

**Still simulated, per §7/§8** — no real gateway, no card network contacted. The checkout form's card fields are validated for *shape* only (regex: 13–19 digit number, `MM/YY` expiry, 3–4 digit CVC); once they pass, a `payments` row is written straight to `status = 'paid'`. Same spirit as the pre-existing `pay_fee` action on prescription requests.

**Doctor side:** `pages/doctor/account/account_controller.php`'s `update_profile` action gained a `consultation_fee` field alongside `specialty` (optional, validated non-negative). The Account page shows a "Free consultation" or "$X consultation" badge accordingly.

**Patient side:** `pages/user/doctors/doctors.php`'s directory now shows each doctor's fee status. An unconnected doctor with a fee set shows a **"Pay & Connect"** link (to the new `pages/user/payments/checkout.php`) instead of the free "Connect" button — doctors with no fee keep the original free-connect dialog completely unchanged.

`checkout.php` → `pages/user/payments/payments_controller.php`'s `pay_and_connect` action, in one transaction:
1. Re-validates the doctor server-side (active, verified, has a fee) — never trusts the amount the checkout page rendered from.
2. Re-checks the `(patient_id, doctor_id)` uniqueness constraint, so a double-submit can't charge twice or create two connections.
3. Inserts the `payments` row as `paid`.
4. Inserts the `doctor_connections` row (same effect as the existing free `request_connection` action).
5. Links `payments.reference_id` to the new connection id.
6. Notifies the doctor: *"\[Patient\] paid $X and sent you a connection request"* — one notification, reusing the existing `connection_request` type/link.

On success, redirects to a receipt rather than back to the doctors list.

**Receipt & history:**
- `pages/user/payments/receipt.php` — printable receipt, ownership-checked to the paying patient. "Download" is the browser's own print-to-PDF (`@media print` hides sidebar/navbar/toast) — no new server-side PDF library introduced.
- `pages/user/payments/payments.php` — patient's payment history (new "Payments" sidebar item, Care group).
- `pages/doctor/payments/payments.php` — doctor's earnings view (new "Payments" sidebar item), read-only, scoped to `payee_id`.
- `pages/admin/payments/payments.php` — all-platform transaction list (new "Payments" sidebar item, Management group), read-only, no scoping.

**A pre-existing styling gap, noted but not fixed here:** `.mr-table`/`.mr-table-wrap` is only actually defined in `pages/user/home.css` — `pages/user/reports.css` uses the same classes without defining them, so `reports.php`'s history table has likely been unstyled by default (a narrower case of the stat-card CSS bug from the September batch, §6). This feature's own `pages/*/payments/payments.css` defines its own copy of `.mr-table` rather than assuming `home.css` is already loaded, so the new Payments tables render correctly regardless — flagged here for whoever next touches `reports.css`.

**Demo data** (`db/13_seed_demo_payments.sql`, §3.12): `rahul.nair@kare-demo.test` → $25 fee, `sara.thomas@kare-demo.test` → $40 fee, `anjali.menon@kare-demo.test` stays free. Seeded three `payments` rows against `testpatient`'s existing connections plus one new connection + payment for `kohai` → Dr. Sara Thomas.

**Tested live** (curl, exact request shapes): fee set/update/clear on the doctor Account page; per-doctor branching on the directory (free vs. paid, and that an already-connected doctor shows its existing status regardless of fee); the full checkout → payment → connection → notification → receipt chain for a first-time paid connection, with DB rows confirmed linked correctly; the doctor's notification bell showing the expected unread item; a double-submit against an already-paid/pending doctor correctly rejected with no duplicate row; malformed card input rejected with no `payments` row written; doctor earnings and admin all-payments pages both showing correct aggregated totals across multiple patients/doctors. All test data created during this pass (one throwaway connection/payment/notification) was deleted afterward — the sandbox DB was confirmed back at `13_seed_demo_payments.sql`'s seeded row counts.

**Deliberately not done:** folding the existing prescription-request fee (§3.10) into `payments` (the `type` enum reserves space for it, but the existing flow is left untouched to avoid risking a working feature); refunds; partial payments; a real payment gateway (Stripe/Razorpay or otherwise) — all out of scope per §7/§8 unless asked for.

---

## 15. Styling Audit + Financial Visibility (as built)

A follow-up pass: a user-reported "some elements are shown without style" bug, plus two requests — doctor-facing earnings visibility, and an admin-facing financial statistics page. Full narrative + test log in `assets/CHANGELOG.md` §24; this section is the as-built reference.

**Styling audit.** A systematic check (every page's HTML classes vs. what's actually defined in its included stylesheets, not just the one gap flagged in §14) found the exact "moved to shared" bug class from the September 2026 batch (§6) had recurred repeatedly and much more widely than expected:

| Class | Was missing on | Impact |
|---|---|---|
| `.mr-medicine-card-name` | 15 of 17 pages using it | Bold name/heading text rendering as plain unstyled text almost everywhere — doctor dashboard/patients/prescriptions/account/requests/payments, admin dashboard/account/payments/reports, user doctors/search/payments |
| `.mr-table` / `.mr-table-wrap` | Doctor Patients & Prescriptions, **admin Users**, user Reports & Schedule | Completely unstyled raw `<table>`s — no borders, header treatment, or row striping |
| `.mr-card-heading` / `-row` | Doctor Prescriptions, user Help & FAQ (duplicated in 12 other files instead of shared) | Unstyled sub-headings |
| `.mr-textarea`, `.mr-doctor-card/-avatar/-main`, `.mr-filter-select`, `.mr-rate-bar` | Various | Same duplicated-but-gapped pattern, smaller blast radius each |

Fixed the same way the project already fixed the stat-card bug: every class above consolidated into `shared/components.css` as the single source of truth, redundant per-page copies removed. Also fixed two bugs introduced in §14 itself: `.mr-doctor-fee` had been defined in the wrong file (`payments.css` instead of `doctors.css`, the only page using it) so it never applied; and `pages/user/schedule/schedule.css` was missing a `.mr-medicine-card-main` rule entirely, so a medicine card's action buttons didn't get pushed to the card's right edge on wide screens. Two flagged classes (`.mr-medicine-rate-item`, `.mr-snooze-form`) turned out to be false positives — already covered by a parent's `gap` / a generic descendant selector — and were correctly left alone.

**Doctor earnings, added to the dashboard.** `pages/doctor/home.php` gained a fourth stat card ("Total earnings") and a "Recent payments received" preview list, both linking to the existing `pages/doctor/payments/payments.php`. The stat-card grid now optionally renders a card as `<a>` instead of `<div>` when a `href` key is present (CSS Grid blockifies both identically, so no layout changes needed) — same pattern reused on the admin dashboard.

**Admin financial statistics, expanded.** `pages/admin/payments/payments.php` rebuilt from a plain transaction list into: total platform revenue, this-month revenue, transaction count, and average payment (four stat cards); a new **revenue-by-doctor breakdown** with proportional bars (reusing `.mr-rate-bar`, promoted to shared since it's now used on 2 pages); the existing transaction table kept below. `pages/admin/home.php` gained a fifth stat card ("Platform revenue," linking to the new page). Admin sidebar's "Payments" label renamed to "Financial Stats." No chart library was added — the README (§1) notes Chart.js is used only on the user Reports page; the revenue bars use the same lightweight CSS-only pattern already established.

**Tested live:** full regression across all 21 pages spanning all three roles — every page 200, zero PHP errors; CSS brace-balance checked across every stylesheet; confirmed the admin Users table now renders styled; confirmed the new dashboard cards show correct live figures ($25 for Dr. Rahul Nair, $105 platform-wide, $80/$25 revenue-by-doctor split); confirmed no DB rows were altered by this pass (read-only verification against existing seed data).

**Also checked, per explicit request, and left untouched because already correct:** the "Missed" display for an overdue-but-still-`upcoming` dose (§6's September batch feature). Verified present and working in all four claimed locations (`pages/user/schedule/schedule.php`, `pages/user/home.php`, `pages/doctor/patients/patients.php`, `pages/user/reports.php`), confirmed live against two genuinely-overdue rows already in the seed data — both display "Missed" while the stored `dose_logs.status` correctly stays `upcoming`, exactly per §3.4's design. No code changes made for this item.

---

## 16. Original To-Do List — Status

The list below is the project owner's original working to-do list (kept verbatim, typos and all, for traceability against the "as-built" sections above). Every item is now done.

1. ✅ Seed demo data. — `db/08_seed_demo_data.sql` + `10_seed_prescription_requests.sql` (§3, §9).
2. ✅ Some elemets doesnt have design. Check all the ui elements and add style.
   User module: shedule, Chat with doctor send button icon not centered, reports no style.
   Doctor module: Dashboard, my patients(todays dose), messages sent icon not aligned. — Shared stat-card CSS moved to `shared/components.css`; chat send-button icon centering fixed (both in the September batch, §6).
3. ✅ Admin: dashboard, users — both built (§6, "Completed — Admin side").
4. ✅ Doctor signup — Patient/Doctor toggle on `auth/signup/`, new doctors inserted unverified (September batch, §6); landing page can also deep-link straight to it (§13.3).
5. ✅ Seperate users and doctors in admin view. — `pages/admin/users/users.php`'s role filter dropdown (All roles / Patients / Doctors / Admins) already covers this; no separate page was needed.
6. ✅ If sheduled medicine time is missed show missed. — "Missed" display for overdue doses, everywhere a dose list is shown (September batch, §6).
7. ✅ History of daily shedule, show a the progress in a graphicaly way(in reports, use barchart, linechart etc). — Real Chart.js trend chart on `reports.php` (§13.2), alongside the existing per-medicine bar and calendar heatmap.
8. ✅ Option to download precription format. — Download button on `pages/user/prescriptions/`, alongside the existing "View" link (September batch, §6).
9. ✅ Doctors should update prescription of patients if they ask it through chat like a request. they may need payment for it, nothing is free so. — Full two-way prescription-request workflow with an optional fee (§3.10, §6); doctor and patient notify each other via the notification bell when a request is made, fulfilled, declined, or paid (§13.1). ("Through chat" specifically wasn't built — requests go through the dedicated Prescriptions pages, not the chat thread — but the request/fulfill/decline/fee lifecycle itself is fully built both directions.)
10. ✅ bug: when i change month in reports calendar the page resets to table. — Fixed by round-tripping the selected view through the URL (`?view=calendar`) across the prev/next links (September batch, §6).
11. ✅ Update the design of timepicker in schedule. Too confusing. — Replaced with three `<select>` dropdowns (hour / 5-minute steps / AM-PM) synced to a hidden field (September batch, §6).
12. ✅ Wire up the working on notifications. For doctor and user(patient). — Full notification system: table, helper, shared fetch/mark-read endpoints, navbar dropdown on all three portals, wired into 8 real events covering both patient and doctor sides (§13.1).
13. ✅ Option to view active or current prescription given by the doctor to patient and update. — "Current prescription" callout on `pages/user/prescriptions/`, set/cleared automatically whenever a new prescription becomes current (§3.5, §6).
14. ✅ Both patient and user can request for prescription update. Based on scenrios. — Two-way request workflow: patient → doctor and doctor → patient, each with its own fulfillment path (§3.10).
15. ✅ Generate and design a guest page. where login and signup(both patient and doctor) is connected. — New root `index.php` landing page with CTAs into login and both signup roles (§13.3). *(This item wasn't in the numbered list above but was tracked alongside it in an earlier revision of this README — included here for completeness since it's now done too.)*
15. Generate and design a guest page. where login and signup(both patient and doctor) is connected.
16. ✅ Project reviewer feedback: doctors need a way to actually charge for their time (echoing to-do #9's "nothing is free" note, but broader than just prescriptions) — implement something like a payment template. — Doctor-settable consultation fee + pay-to-connect checkout, simulated card-details template, receipt, and payment history across all three portals (§14, CHANGELOG §23).
17. ✅ Project reviewer feedback: some elements are shown without style — fix and add doctor/admin earnings & financial-statistics visibility; also verify the overdue-dose "Missed" display feature is actually implemented. — Widespread CSS-consolidation fix (15+ pages affected, including the admin Users table), doctor dashboard earnings card + preview, admin financial statistics page with revenue-by-doctor breakdown; the "Missed" display feature was checked and confirmed already correct, left untouched (§15, CHANGELOG §24).