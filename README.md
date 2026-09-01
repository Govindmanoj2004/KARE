# KARE (MediRemind) — Developer README

A medication-reminder web app connecting patients and doctors. Patients track medicines, doses, and prescriptions; doctors monitor connected patients and message them. Built as a beginner/academic-scope PHP + MySQL project (procedural `mysqli`, no framework, no ORM — by design).

This README is written to be a complete, standalone reference for continuing development — with another AI agent, a different model, or a human — without needing to re-read the whole codebase first. For narrative history (what was built, in what order, and why), see [`assets/CHANGELOG.md`](assets/CHANGELOG.md). For visual/design rules (colors, spacing, component patterns), see [`assets/design.md`](assets/design.md) — that file is the **authoritative style reference** and should be read before building any new UI.

---

## 1. Tech Stack

| Layer | Technology |
|---|---|
| Server language | PHP 8.3, procedural style, no framework |
| Database | MySQL / MariaDB, accessed via `mysqli` (prepared statements throughout) |
| Frontend | Plain HTML + CSS + vanilla JS (no build step, no bundler, no framework) |
| Icons | Phosphor Icons via CDN (`unpkg.com/@phosphor-icons/web@2.1.1`) |
| Fonts | Google Fonts "Poppins" (working fallback for the licensed "Euclid Circular B") |
| Typical local host | XAMPP (Apache + MySQL/MariaDB + PHP) |

**No package manager, no Composer, no npm.** Everything is flat files served directly by Apache/PHP's built-in server.

---

## 2. Project Structure

```
KARE/
├── assets/
│   ├── connection/
│   │   └── Connection.php          # mysqli connection (root, no password, db_kare)
│   ├── helpers/
│   │   ├── auth.php                # require_role() — role-based page guard
│   │   └── dose_logs.php           # ensure_todays_dose_logs() — auto-creates today's doses
│   ├── uploads/
│   │   └── prescriptions/{user_id}/{random}.{ext}   # uploaded prescription files
│   ├── svg/                        # logo, wave, google/facebook icons
│   ├── design.md                   # AUTHORITATIVE design system reference
│   └── CHANGELOG.md                # chronological build log with rationale
├── auth/
│   ├── login/            (index.php, controller.php, style.css, script.js)
│   ├── signup/            (index.php, controller.php, style.css, script.js)
│   └── logout.php
├── db/
│   ├── db_kare_backup_16-7.sql         # base schema: `users` table + 3 seed patients
│   ├── 02_states_districts.sql          # states/districts tables + users.state_id/district_id
│   ├── 03_seed_states_districts.sql     # seeds 36 states/UTs + 391 districts
│   ├── 04_reports_and_meds.sql          # reports, medicines, medicine_schedules, dose_logs
│   └── 05_user_side_modules.sql         # prescriptions, doctor_connections, messages, notify_*/specialty cols, 3 demo doctors
├── doc/
│   └── Abstract.pdf                # original academic project abstract
├── pages/
│   ├── user/              # ── PATIENT-FACING PAGES ──
│   │   ├── home.php / home.css / home.js              # dashboard
│   │   ├── schedule/       (schedule.php, schedule_controller.php, .css, .js)
│   │   ├── profile/        (profile.php, profile_controller.php, get_districts.php, .css, .js)
│   │   ├── report/         (report.php, report_controller.php, .css, .js)     # "Report an Issue" (support ticket)
│   │   ├── settings/       (settings.php, settings_controller.php, .css, .js)
│   │   ├── reports.php / reports.css / reports.js      # personal adherence analytics (NOT the support-ticket "report")
│   │   ├── prescriptions/  (prescriptions.php, prescriptions_controller.php, .css, .js)
│   │   ├── doctors/        (doctors.php, doctors_controller.php, .css, .js)
│   │   ├── messages/       (messages.php, messages_controller.php, messages_poll.php, .css, .js)
│   │   ├── help.php / help.css / help.js                # static FAQ
│   │   └── search.php / search.css / search.js          # searches own medicines + prescriptions
│   └── doctor/             # ── DOCTOR-FACING PAGES ──
│       ├── home.php / home.css / home.js               # dashboard
│       ├── requests/       (requests.php, requests_controller.php, .css, .js)
│       ├── patients/       (patients.php, patients_controller.php, .css, .js)
│       ├── messages/       (messages.php, messages_controller.php, messages_poll.php, .css, .js)
│       └── account/        (account.php, account_controller.php, .css, .js)   # combines profile+settings
├── pages/admin/            # ── ADMIN-FACING PAGES ──
│   ├── home.php / home.css / home.js                    # dashboard: counts, status breakdown, recent reports
│   ├── users/        (users.php, users_controller.php, .css, .js)      # all users, filter/search, suspend/reactivate, doctor verification
│   ├── reports/      (reports.php, reports_controller.php, .css, .js)  # reply to patient/doctor support tickets
│   └── account/      (account.php, account_controller.php, .css, .js) # combines profile+settings
├── shared/
│   ├── tokens.css                  # ALL design tokens (colors, radii, spacing) — single source of truth
│   ├── base.css                    # reset, body, scrollbar, scroll-entry animation system
│   ├── components.css              # sidebar, navbar, popover, buttons, forms, tables, badges, status pills
│   ├── toast/       (toast.php, toast.css, toast.js)     # flash-message system (PRG pattern)
│   ├── modal/       (modal.php, modal.css, modal.js)     # generic confirm-first dialog
│   ├── user/        (sidebar.php, navbar.php)             # patient nav shell
│   ├── doctor/      (sidebar.php, navbar.php)             # doctor nav shell
│   └── admin/       (sidebar.php, navbar.php)             # admin nav shell
└── start_sandbox.sh                # Linux/bash dev-sandbox launcher (NOT for XAMPP/Windows — see §10)
```

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
  title varchar(150), doctor_name varchar(150) null, notes varchar(255) null,
  file_path varchar(255),           -- e.g. assets/uploads/prescriptions/3/ab12.../file.pdf
  file_original_name varchar(255),
  uploaded_at datetime
)
```
Files: PDF/JPG/PNG, 2MB max, validated server-side. Stored at `assets/uploads/prescriptions/{user_id}/{32-hex-char-random}.{ext}` and served by **direct static link**, not an access-gated PHP script — the random filename is the only protection (see §7, Known Limitations). **No OCR/text-extraction** — files are stored as-is.

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

### Entity relationship summary

```
users (role=patient) ─┬─< medicines ─< medicine_schedules ─< dose_logs
                       ├─< prescriptions
                       ├─< reports (support tickets)
                       ├─(state_id/district_id)→ states/districts
                       └─< doctor_connections >─┬─ users (role=doctor)
                                                 ├─< messages
                                                 └─< doctor_patient_notes (doctor-only, per patient)
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
| Reports (analytics) | `reports.php` | 30-day adherence rate, per-medicine breakdown, dose history |
| Prescriptions | `prescriptions/` | Upload/view/delete PDF/JPG/PNG (2MB max) |
| Doctors | `doctors/` | Browse doctors, send/cancel connection requests |
| Messages | `messages/` | Polling chat with accepted doctor connections |
| Help & Search | `help.php`, `search.php` | Static FAQ; search own medicines/prescriptions |

### ✅ Completed — Doctor side (`pages/doctor/`)

| Module | Files | What it does |
|---|---|---|
| Dashboard | `home.php` | Pending requests / active patients / unread messages stats |
| Requests | `requests/` | Accept/decline incoming connection requests |
| My Patients | `patients/` | Connected patients, 30-day adherence badge, read-only today's-doses view, search |
| Messages | `messages/` | Same polling chat system, doctor-scoped |
| Account | `account/` | Combines profile + settings: details, password, notifications, deactivation |

### ✅ Completed — Admin side (`pages/admin/`)

| Module | Files | What it does |
|---|---|---|
| Dashboard | `home.php` | Patient/doctor counts, open reports, unverified doctors, account-status breakdown, recent open reports |
| Users | `users/` | All users, searchable/filterable by role+status; suspend/reactivate; doctor verification toggle. Admin can't change their own status here (routed to Account) |
| Reports | `reports/` | Reply to support tickets (writes `admin_reply`/`replied_at`/`status`) — two-pane list/detail, filterable by status. Closes the loop with the patient-side "Report an Issue" flow |
| Account | `account/` | Same shape as doctor's Account page, plus a safeguard blocking self-deactivation if it's the last active admin |

### ✅ Completed — Small approved additions (README §12 / CHANGELOG §21)

| Feature | Where | What it does |
|---|---|---|
| Message "seen" indicator | `pages/user/messages/`, `pages/doctor/messages/` | Shows "Seen" under the sender's last message once the recipient has opened the thread (reuses `messages.read_at`) |
| "Remember me" on login | `auth/login/` | 30-day session cookie when checked, ordinary session cookie otherwise |
| Snooze a dose | `pages/user/schedule/` | Push an `upcoming` dose 15/30/60 min, capped at 3 times (`dose_logs.snooze_count`) |
| Calendar view of dose history | `pages/user/reports.php` | Table/Calendar toggle; month grid with prev/next navigation, color-coded per-day dots |
| Private notes on a patient | `pages/doctor/patients/` | Doctor-only free-text note per connected patient (`doctor_patient_notes` table), never visible to the patient |

### ❌ Not built (pending)

| Item | Notes |
|---|---|
| **Email/SMS sending** | `notify_email`/`notify_sms` preferences are collected and saved on all three sides (patient/doctor/admin) but **nothing sends anything** — no PHPMailer, no Twilio/Fast2SMS integration |
| **Missed-dose cron job** | No scheduled task exists; `dose_logs.status` only changes via explicit human action (including the snooze feature above, which is manual, not automated detection) |
| **Prescription OCR** | Files are stored as opaque blobs; no text extraction |
| **Caretaker role** | `users.role` enum has no caretaker value; a caretaker-manages-multiple-patients relationship would need a new table (there's a documented historical decision to *remove* a "My Patients" nav item from the patient sidebar because it didn't correspond to anything the schema supports — see CHANGELOG §18) |

The five small features previously listed here (§12) are done — see the "Small approved additions" table above.

**Explicitly out of scope for this project, by design — not pending, not a gap to fill:** password hashing, file-access gating/streaming, and equivalent security hardening. See §7.

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
| `anjali.menon@kare-demo.test` | `doctor1234` | doctor (General Physician) |
| `rahul.nair@kare-demo.test` | `doctor1234` | doctor (Cardiologist) |
| `sara.thomas@kare-demo.test` | `doctor1234` | doctor (Endocrinologist) |
| `admin@kare-demo.test` | `admin1234` | admin |

Freshly-migrated accounts have **no medicines/dose history/connections** seeded — that demo data was only ever created ad hoc during testing, not shipped in the SQL files. To see the app with real data, either use it manually (add a medicine, connect to a doctor) or write your own seed SQL.

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
   ```
   (No `-p` — XAMPP's default root user has no password, matching `assets/connection/Connection.php`'s hardcoded `root`/empty-password/`db_kare` config.)
4. Verify: `SHOW TABLES;` should list 10 tables (`users`, `states`, `districts`, `reports`, `medicines`, `medicine_schedules`, `dose_logs`, `prescriptions`, `doctor_connections`, `messages`).
5. Browse to `http://localhost/KARE/auth/login/index.php` — log in as any of the accounts in §9, including `admin@kare-demo.test` for the admin portal at `pages/admin/`.

`start_sandbox.sh` at the project root is a **bash script for a Linux dev sandbox** (used during development to spin up MariaDB + PHP's built-in server from scratch) — it does not apply to XAMPP/Windows and can be ignored or deleted.

---

## 11. Guidance for Continued Development (for other agents/models)

1. **Read `assets/design.md` before writing any new UI** — it defines the exact color/radius/spacing/typography tokens and the checklist every new page must follow (CSS load order, `$activePage`, `$mrRootBase`, session key usage, toast/modal inclusion, scroll-entry animation attributes). It is the single source of truth for visual consistency.
2. **Read `assets/CHANGELOG.md`** for the *why* behind non-obvious decisions — several sections document bugs that were found and fixed (e.g. session key mismatches, folder-restructure path breakage) specifically so they aren't reintroduced.
3. **All three portals (patient, doctor, admin) are functionally complete, and all five small approved additions (§12) are built.** What's left is the larger not-yet-built items in §6's pending table (email/SMS sending, missed-dose cron, prescription OCR, a caretaker role) — none of which has existing UI scaffolding the way the admin module or the §12 additions did, so expect more schema/design work up front for any of those.
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




To do:
1.Seed demo data.
2.some elemets doesnt have design. Check all the ui elements and add style.
  User module: shedule, Chat with doctor send button icon not centered, reports no style.
  Doctor module: Dashboard, my patients(todays dose), messages sent icon not aligned,
3.Admin: dashboard , users 