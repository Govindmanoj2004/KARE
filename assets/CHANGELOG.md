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

## What's next (not yet built)

Per the original project scope, still pending:

- Database wiring for `home.php`'s stat cards and schedule table (currently
  placeholder arrays)
- Medicine schedule management + time-based reminders
- Email/SMS notifications (PHPMailer, Fast2SMS/Twilio)
- Missed-dose detection via cron job
- Prescription upload + text extraction
- Doctor connection lifecycle
- Doctor–caretaker chat (polling-based)
- Admin module (user management, doctor verification, system health, and
  a reply/management view for the reports table added in §9 — user-side
  submission is done, admin-side reply is not)
- Account settings page (`settings.php`, already linked from the sidebar)
  — discussed but not yet built. Candidates: notification preference
  toggles, account deactivation (flip `status` to `deactivated`, same
  confirm-modal pattern as logout), linked caretaker/doctor accounts with
  disconnect, last-login/session info, and data export or soft-delete
  (`deleted_at` already exists on `users`)
