# Kare Design System — Complete Reference

Use this document as the authoritative style reference when generating any new page, component, or feature for this product. Follow every value exactly — colors, spacing, radii, and timing are not approximations, they are the system.

---

## 1. Brand Feel

A calm, friendly, ocean-inspired SaaS aesthetic. Soft sky-blue backgrounds, rounded white cards, and clean typography give the product a light, approachable tone. One accent color (`#55acee`), one font family (Poppins), and a consistent radius/shadow scale tie every surface together.

**Two words:** Simple clarity.

---

## 2. Project Structure

```
kare/
  assets/
    connection/
      Connection.php       ← MySQL DB connection (PDO)
    svg/
      logo.svg             ← Brand mark (white wave squiggle)
      wave.svg             ← Horizontally-tileable wave for login/signup hero
      google.svg           ← Google "G" icon for social login
      facebook.svg         ← Facebook "f" icon for social login
    design.md              ← THIS FILE — single source of design truth
  auth/
    login/
      index.html           ← Login page (self-contained HTML)
      style.css            ← Login-specific styles
      script.js            ← Login form validation
    signup/
      index.html           ← Signup page (self-contained HTML)
      style.css            ← Signup-specific styles
      script.js            ← Signup form validation + terms checkbox
  shared/
    tokens.css             ← All CSS custom properties (THE SINGLE SOURCE)
    base.css               ← Global reset, body, scrollbar, scroll-entry animation
    components.css         ← Sidebar, navbar, popover, buttons, status badges
    user/
      navbar.php           ← Reusable top navbar component
      sidebar.php          ← Reusable sidebar navigation component
  pages/
    user/
      home.php             ← Dashboard page template
      home.css             ← Dashboard-specific styles (layout header, stat grid, table)
      home.js              ← Dashboard JS (popover, sidebar toggle, scroll-entry observer)
```

---

## 3. File Details

### `shared/tokens.css` — Design Tokens

**Purpose:** The single source of truth for every design value in the product. No other CSS file should hard-code colors, radii, shadows, or font stacks. Everything references a `var(--token)`.

**How to use:** To restyle the entire app (e.g. swap the accent color, change card radius, adjust shadows), edit ONLY this file. Every component will update automatically.

**Contains:**

| Category          | Tokens                                                                                                          | Example values                                                                          |
| ----------------- | --------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- |
| Canvas & Surfaces | `--canvas`, `--layout-bg`, `--surface`, `--surface-raised`, `--surface-inset`                                   | `#c7e7ff`, `#eaf5fd`, `#ffffff`, `#f4f9fd`, `#efefef`                                   |
| Borders           | `--border`, `--border-strong`                                                                                   | `rgb(0 0 0 / 10%)`, `rgb(0 0 0 / 16%)`                                                  |
| Text              | `--text-primary`, `--text-muted`, `--text-placeholder`, `--text-on-dark`, `--text-on-accent`                    | `#332e2e`, `#7a7a7a`, `#9e9e9e`, `#f9f9f9`, `#ffffff`                                   |
| Accent            | `--accent`, `--accent-glow`, `--accent-bg`, `--accent-text`                                                     | `#55acee`, `rgb(85 172 238 / 20%)`, `rgb(85 172 238 / 14%)`, `#226699`                  |
| Status            | `--status-taken-color/bg`, `--status-upcoming-color/bg`, `--status-missed-color/bg`                             | Green `#1f9d55`, Blue `#226699`, Red `#e0433f` with 14% opacity BGs                     |
| Interactive       | `--hover-tint`, `--hover-tint-md`, `--translucent-btn`, `--focus-ring`                                          | `rgb(0 0 0 / 4%)`, `rgb(0 0 0 / 7%)`, `rgb(0 0 0 / 6%)`, `0 0 0 4px var(--accent-glow)` |
| CTA button        | `--cta-bg`, `--cta-bg-hover`, `--cta-bg-active`, `--cta-text`                                                   | `#332e2e`, `#464040`, `#241f1f`, `#f9f9f9`                                              |
| Danger            | `--danger`, `--danger-bg`                                                                                       | `#e0433f`, `rgb(224 67 63 / 10%)`                                                       |
| Typography        | `--font-family`                                                                                                 | `"Euclid Circular B", "Poppins", sans-serif`                                            |
| Type scale        | `--text-xs` through `--text-xl`                                                                                 | `12px`, `13px`, `14px`, `16px`, `17px`, `24px`                                          |
| Radii             | `--radius-card`, `--radius-panel`, `--radius-control`, `--radius-checkbox`, `--radius-pill`, `--radius-popover` | `30px`, `24px`, `14px`, `5px`, `999px`, `20px`                                          |
| Shadows           | `--shadow-card`, `--shadow-sidebar`                                                                             | `0 20px 40px -10px rgb(0 0 0 / 10%)`, `20px 0 40px -10px rgb(0 0 0 / 14%)`              |
| Timing            | `--ease`, `--ease-transform`                                                                                    | `0.25s ease`, `0.15s ease`                                                              |
| Layout            | `--sidebar-width`, `--navbar-height`, `--content-max`                                                           | `260px`, `76px`, `1100px`                                                               |

---

### `shared/base.css` — Global Reset & Defaults

**Purpose:** Sets up the global environment every page needs. Targets raw HTML elements only — no component classes.

**Contains:**

- `box-sizing: border-box` on `*`, `*::before`, `*::after`
- `body` — margin 0, overflow hidden, font-family from `--font-family`, color from `--text-primary`, background from `--canvas`, font smoothing
- `a` — `color: inherit`, no underline
- `button` — `font-family: inherit`, `cursor: pointer`
- Thin scrollbar styling (WebKit + Firefox)
- **Scroll-entry animation system:** `[data-mr-scroll-entry]` elements start at `opacity: 0` + `translateY(12px)` and resolve to visible when `.is-visible` class is added. Stagger via `--index` CSS var: `transition-delay: calc(var(--index, 0) * 80ms)`. Duration: `0.6s` with `cubic-bezier(0.16, 1, 0.3, 1)`.

---

### `shared/components.css` — Shared UI Components

**Purpose:** Every reusable UI pattern that appears on multiple pages. If a component is used in more than one page, its styles belong here. Page-specific CSS files should never duplicate these.

**Contains:**

#### App Shell (`.mr-app-shell`, `.mr-main`, `.mr-content`)

Full-viewport flex layout: sidebar on left, main column on right. Main column has layout-bg background.

#### Sidebar (`.mr-sidebar`)

- Width: `var(--sidebar-width)` (260px). White background, right border.
- Brand: `.mr-sidebar-brand` — logo circle (36px, accent bg, circular) + wordmark "Kare" (16px, weight 600).
- Section labels: `.mr-sidebar-section-label` — 12px uppercase, muted color, 0.06em tracking.
- Nav items: `.mr-nav-item` — 44px height, 14px radius, 14px font, weight 500, muted color.
  - Hover: `rgb(0 0 0 / 4%)` tint, text darkens to primary.
  - Active (`.is-active`): `rgb(85 172 238 / 14%)` background, primary text, 3px accent bar on left.
  - Focus-visible: accent glow ring + 2px accent border.
- Badges: `.mr-nav-badge` — pill shape, 11px, accent background, white text.
- Footer: `.mr-sidebar-footer` — pinned to bottom via `margin-top: auto`, top border.

#### Navbar (`.mr-navbar`)

- Height: `var(--navbar-height)` (76px). Bottom border.
- Search: `.mr-search` — relative container, magnifying glass icon, 44px height input, 14px radius.
  - Focus: white background, accent border, accent glow ring.
- Toggle: `.mr-sidebar-toggle` — hidden on desktop, shown below 900px.
- Icon buttons: `.mr-icon-btn` — 44px square, translucent bg, 14px radius. Notification dot via `.mr-dot`.
- Avatar: `.mr-avatar-btn` — pill wrapper, 32px avatar circle, caret. Toggles `.is-open` on `.mr-avatar-wrap`.

#### Popover (`.mr-popover`)

- 260px wide, white bg, 20px radius, card shadow. Animates in via opacity + translateY.
- Header: avatar (40px) + name/email.
- Items: `.mr-popover-item` — 40px height, 12px radius, muted icons. Danger variant in red.
- Dividers: `.mr-popover-divider` — 1px border-colored line.

#### Primary Button (`.mr-btn`)

- 44px height, pill radius, `--cta-bg` background (#332e2e), `--cta-text` (#f9f9f9).
- Hover: `--cta-bg-hover` (#464040). Active: `--cta-bg-active` (#241f1f).
- Focus: accent glow ring.
- Background-color-only interaction. No lift, no shadow change.

#### Status Badges (`.mr-status`)

- Pill shape, 12px font, weight 500, color dot via `::before`.
- `.is-taken` — green. `.is-upcoming` — blue. `.is-missed` — red.

#### Responsive

- `@media (max-width: 900px)` — sidebar becomes fixed overlay with shadow, hamburger appears, search goes full width.
- `@media (max-width: 560px)` — navbar padding shrinks, popover adjusts.

---

### `shared/user/sidebar.php` — Sidebar Component

**Purpose:** Reusable data-driven sidebar. To add a new page link, add one array entry to `$mrNavGroups`. No HTML editing needed.

**Expects:** `$activePage` to be set before including (e.g. `$activePage = 'dashboard'`).

**Contains:**

- `$mrNavGroups` PHP array — 3 groups ("Overview", "Care", "Support") with items containing `key`, `label`, `icon` (Phosphor class), `href`, optional `badge`.
- `<aside class="mr-sidebar">` — renders brand, loops groups into section labels + `<ul>` nav lists, footer with "Help & FAQ".
- Active state: compares `$activePage` against each item's `key`, adds `.is-active` class and `aria-current="page"`.

**Adding a new sidebar item:**

```php
['key' => 'newpage', 'label' => 'New Page', 'icon' => 'ph-star', 'href' => 'newpage.php']
```

---

### `shared/user/navbar.php` — Navbar Component

**Purpose:** Reusable top navigation bar with search, notifications, and profile popover.

**Expects:** `$currentUser` array with `name`, `email`, `avatar` (optional). Falls back to `$_SESSION` values or "Guest Caretaker" if not set.

**Contains:**

- Hamburger button (`.mr-sidebar-toggle`, hidden on desktop)
- Search form (`.mr-search`, action `search.php`)
- Notification bell (`.mr-icon-btn` with `.mr-dot`)
- Avatar wrapper (`.mr-avatar-wrap`) with button + popover
- Popover: user info header, profile/settings/support links, logout form
- Avatar fallback: `.mr-avatar-initial` (first letter of name) when no image URL provided

---

### `pages/user/home.php` — Dashboard Page

**Purpose:** The main dashboard view after login. Shows greeting, stat cards, and today's medication schedule.

**CSS loading order:**

```html
<link rel="stylesheet" href="../../shared/tokens.css" />
<link rel="stylesheet" href="../../shared/base.css" />
<link rel="stylesheet" href="../../shared/components.css" />
<link rel="stylesheet" href="home.css" />
```

**External dependencies:**

- Phosphor Icons (regular): `https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css`
- Google Fonts: `Poppins:wght@400;500;600`

**Contains:**

- PHP data: `$activePage`, `$currentUser`, `$stats` (4 metrics), `$todaysSchedule` (4 rows), `$statusLabels`
- Includes `sidebar.php` and `navbar.php`
- Layout header: greeting with user's first name + "Add reminder" button
- Layout card: overlaps header with negative margin, contains stat grid + schedule table
- `data-mr-scroll-entry` attributes with `--index` for staggered fade-in animations

---

### `pages/user/home.css` — Dashboard Page Styles

**Purpose:** Styles that exist ONLY on the dashboard. No shared component styles — those live in `components.css`.

**Contains:**

- **Layout header** (`.mr-layout-header`) — padding area with a `::before` pseudo-element that draws a large circular blue blob at `opacity: 0.16` behind the greeting text
- **Layout card** (`.mr-layout-card`) — white card with 30px radius, card shadow, `margin: -100px auto 40px` to overlap the header
- **Stat grid** (`.mr-stat-grid`, `.mr-stat-card`) — CSS Grid, `minmax(200px, 1fr)`, cards with layout-bg tint, 24px radius, icon in accent-bg square
- **Table** (`.mr-table-wrap`, `.mr-table`) — sticky thead, zebra striping (`--surface-raised`), row opacity `0.75` → `1` on hover, status badges in cells
- **Responsive** — card margin adjusts at 900px and 560px, stat grid becomes 2-column below 560px

---

### `pages/user/home.js` — Dashboard JavaScript

**Purpose:** Client-side behavior for the dashboard shell.

**Contains 3 features:**

1. **Avatar popover** — Click `.mr-avatar-btn` toggles `.is-open` on `.mr-avatar-wrap`. Click-outside and Escape close it. Manages `aria-expanded`.
2. **Mobile sidebar** — Click `.mr-sidebar-toggle` toggles `.mr-sidebar-open` on `.mr-app-shell`. Click-outside closes it.
3. **Scroll-entry animations** — `IntersectionObserver` watches all `[data-mr-scroll-entry]` elements. When 10% visible (with -40px root margin), adds `.is-visible` class. Each element unobserves after first trigger. Fallback: if IntersectionObserver isn't supported, everything gets `.is-visible` immediately.

---

### `assets/connection/Connection.php` — Database

**Purpose:** PDO MySQL connection. Currently just the connection setup — queries are added per-page.

---

### Auth Pages (`auth/login/`, `auth/signup/`)

**Purpose:** Self-contained login and signup pages. Each has its own `index.html`, `style.css`, and `script.js`. They do NOT use the shared token system (they predate it) but share the same design values.

**Design values used (must stay in sync with tokens.css):**

- Background: `#c7e7ff` (= `--canvas`)
- Card: white, `border-radius: 30px`, `box-shadow: 0 20px 40px -10px rgb(0 0 0 / 10%)`
- Font: `"Euclid Circular B", "Poppins", sans-serif`
- Inputs: `height: 52px`, `border-radius: 14px`, bg `#efefef`, focus border `#55acee` + glow ring
- Primary button: bg `#332e2e`, text `#f9f9f9`, hover `#464040`, active `#241f1f`
- Hero panel: `border-radius: 24px`, radial gradient from `#eaf5fd` → `#55acee`, animated ocean wave

---

## 4. Typography

```css
font-family: "Euclid Circular B", "Poppins", sans-serif;
```

- **Primary font:** Euclid Circular B (licensed). If unavailable, loads Google Fonts "Poppins" as the working fallback: `Poppins:wght@400;500;600`.
- **Weights used:** Only 400, 500, 600. Never go heavier.
- **Sizes:**
  - `--text-xs` (12px): Table headers, section labels, badges, status text
  - `--text-sm` (13px): Secondary text, popover items, card links, stat labels
  - `--text-base` (14px): Body text, nav items, buttons, search input
  - `--text-md` (16px): Card headings, brand name, table cell text
  - `--text-lg` (17px): Submit buttons on auth pages
  - `--text-xl` (24px): Page/section headings

---

## 5. Color Palette

### Single Accent Rule

`#55acee` is the ONLY accent color. It is used for: focus rings, active nav indicators, notification dots, icon backgrounds, link text, badges. **Never introduce a new accent.**

All hover/focus states reuse existing palette tones — lighten or darken the element's own base color rather than picking a new hue.

### Full Palette Table

| Token                   | Value                   | Where it's used                                      |
| ----------------------- | ----------------------- | ---------------------------------------------------- |
| `--canvas`              | `#c7e7ff`               | Outermost app shell / page background                |
| `--layout-bg`           | `#eaf5fd`               | Main content area, stat card backgrounds             |
| `--surface`             | `#ffffff`               | Cards, sidebar, navbar, popovers, input focus bg     |
| `--surface-raised`      | `#f4f9fd`               | Table header bg, zebra stripe rows                   |
| `--surface-inset`       | `#efefef`               | Input idle background                                |
| `--surface-inset-hover` | `#e6e6e6`               | Input hover background                               |
| `--text-primary`        | `#332e2e`               | Headings, body text, primary button bg               |
| `--text-muted`          | `#7a7a7a`               | Labels, secondary content, nav inactive text         |
| `--text-placeholder`    | `#9e9e9e`               | Input placeholder text                               |
| `--accent`              | `#55acee`               | Focus rings, active indicator bars, badges, icon bgs |
| `--accent-glow`         | `rgb(85 172 238 / 20%)` | Focus ring glow (4px outset)                         |
| `--accent-bg`           | `rgb(85 172 238 / 14%)` | Active nav item bg, stat icon bg                     |
| `--accent-text`         | `#226699`               | Link text, schedule link, upcoming status            |
| `--cta-bg`              | `#332e2e`               | Primary button background                            |
| `--cta-bg-hover`        | `#464040`               | Primary button hover                                 |
| `--danger`              | `#e0433f`               | Logout text/icon, missed status                      |

---

## 6. Radius Scale

| Token               | Value   | Used on                                                  |
| ------------------- | ------- | -------------------------------------------------------- |
| `--radius-card`     | `30px`  | Layout card, auth card                                   |
| `--radius-panel`    | `24px`  | Hero panel, stat cards, table wrapper                    |
| `--radius-control`  | `14px`  | Inputs, buttons, nav items, icon buttons, sidebar toggle |
| `--radius-popover`  | `20px`  | Profile popover dropdown                                 |
| `--radius-checkbox` | `5px`   | Custom checkboxes                                        |
| `--radius-pill`     | `999px` | Badges, avatar button, CTA button                        |

---

## 7. Component Interaction Rules

### Focus Ring (universal)

Every interactive element gets the same treatment on `:focus-visible`:

```css
outline: none;
box-shadow: 0 0 0 4px rgb(85 172 238 / 20%);
```

Nav items additionally get `border: 2px solid var(--accent)`.

### Buttons — Color shift only

No lift (`translateY`), no drop shadow on hover. Keep feedback to `background-color` changes only. The product is calm, not bouncy.

### Inputs

- Idle: `2px solid transparent` border, bg `#efefef`
- Hover: bg `#e6e6e6`
- Focus: bg `#ffffff`, border `#55acee`, glow ring

### Popovers

Animate in via `opacity` + `translateY(-6px)` → `translateY(0)` over `0.25s ease`. Close via `.is-open` class toggle.

---

## 8. Animation System

### Scroll Entry (for content sections)

```css
[data-mr-scroll-entry] {
  opacity: 0;
  transform: translateY(12px);
  transition:
    opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1),
    transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  transition-delay: calc(var(--index, 0) * 80ms);
}
```

JavaScript adds `.is-visible` class via `IntersectionObserver` when 10% of the element is visible. Each element unobserves after first trigger.

**Usage in PHP:**

```html
<div class="mr-stat-card" data-mr-scroll-entry style="--index: 0">...</div>
<div class="mr-stat-card" data-mr-scroll-entry style="--index: 1">...</div>
```

### Signature Wave (auth pages only)

Two `.wave` divs inside `.ocean`, horizontally-tiling `wave.svg` at 35% opacity over solid `#226699`. Duration always `7s` — don't speed it up.

---

## 9. Icons

- **Library:** Phosphor Icons (regular weight), loaded via CDN: `https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css`
- **Usage:** `<i class="ph ph-{name}"></i>` (e.g. `ph-bell`, `ph-plus`, `ph-magnifying-glass`)
- **Auth pages:** Inline SVGs for social icons (Google, Facebook). No icon font.

---

## 10. CSS Loading Order

Every page MUST load CSS files in this exact order:

```html
<!-- 1. Tokens: defines all custom properties -->
<link rel="stylesheet" href="../../shared/tokens.css" />

<!-- 2. Base: reset, body, scrollbar, scroll-entry -->
<link rel="stylesheet" href="../../shared/base.css" />

<!-- 3. Components: sidebar, navbar, popover, buttons, badges -->
<link rel="stylesheet" href="../../shared/components.css" />

<!-- 4. Page-specific: ONLY styles unique to this page -->
<link rel="stylesheet" href="{page}.css" />
```

---

## 11. Responsive Breakpoints

| Breakpoint | What changes                                                               |
| ---------- | -------------------------------------------------------------------------- |
| `<= 900px` | Sidebar becomes fixed overlay + hamburger appears + search goes full width |
| `<= 560px` | Navbar padding shrinks, popover narrows, stat grid becomes 2-column        |
| `>= 485px` | Auth card widens, form padding increases, social buttons go vertical       |
| `>= 640px` | Auth card becomes horizontal (hero left, form right)                       |

---

## 12. Checklist for Any New Page

- [ ] Load CSS in order: `tokens.css` → `base.css` → `components.css` → `{page}.css`
- [ ] Load Poppins font: `Poppins:wght@400;500;600`
- [ ] Load Phosphor Icons if icons are needed
- [ ] Set `$activePage` before including `sidebar.php`
- [ ] Set `$currentUser` before including `navbar.php`
- [ ] Colors from tokens only — no new hues
- [ ] Radii from tokens only — `--radius-card` for cards, `--radius-control` for inputs/buttons
- [ ] All interactive elements use the `--focus-ring` treatment on `:focus-visible`
- [ ] Buttons: background-color-only hover, no lift/shadow
- [ ] Page CSS contains ONLY page-specific styles
- [ ] Add `data-mr-scroll-entry` on major content blocks for fade-in animation
- [ ] Include the JS file with IntersectionObserver setup (or copy from `home.js`)
