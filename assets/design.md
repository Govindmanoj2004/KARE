# Wavy.ai Design System — Page Generation Prompt

Use this document as the authoritative style reference any time you generate a new page for this product. Follow every value exactly — colors, spacing, radii, and timing are not approximations, they are the system.

---

## 1. Brand Feel

A calm, friendly, ocean-inspired SaaS aesthetic. Soft sky-blue backgrounds, a rounded white card, and a gently animated wave/ocean scene give auth and marketing surfaces a light, approachable tone — contrasted with a dark, dense, data-focused theme for internal dashboard/table views. Two moods, one system:

- **Light mood** (auth, onboarding, marketing): sky blue `#c7e7ff` background, white card, animated wave hero.
- **Dark mood** (dashboards, data tables, internal tools): charcoal/purple background, dark cards, high-density tables.

Every page uses the same typography, radius scale, and interaction language regardless of mood.

---

## 2. Typography

```css
font-family: "Euclid Circular B", "Poppins", sans-serif;
```

- **Primary font**: Euclid Circular B (licensed — if unavailable, load Google Fonts "Poppins" as the working fallback: `Poppins:wght@400;500;600`).
- **Weights used**: only 400, 500, 600. Never go heavier (no 700/bold) — weight is used sparingly, mostly 500 for emphasis.
- **Sizes**:
  - Page/section headings (dashboard): `24px`, weight 400
  - Form titles ("Login to your account"): `14px`, weight 500, centered
  - Body / input text: `16px`
  - Buttons: `15px`–`17px`, weight 500
  - Small text (labels, table headers, checkbox labels, link text): `12px`–`13px`
  - Table cell text: `16px`

---

## 3. Color Palette

### Light mood (auth/marketing)
| Token | Hex / Value | Usage |
|---|---|---|
| Page background | `#c7e7ff` | full-viewport background behind the card |
| Card background | `#ffffff` | main card surface |
| Primary text | `#332e2e` | headings, body text, primary button bg |
| Primary button text | `#f9f9f9` | text on dark submit button |
| Muted text | `#5c5c5c` | social button labels, checkbox label text |
| Placeholder text | `#9e9e9e` | input placeholders |
| Border (subtle) | `rgb(0 0 0 / 16%)` | stored as `--color-border` custom property |
| Divider line | `rgb(0 0 0 / 40%)` at `0.6` opacity | "Or" divider hairline |
| Accent blue (interactive) | `#55acee` | **the single accent color** — focus rings, hero gradient edge, link/checked states |
| Hero gradient | `radial-gradient(ellipse at center, #eaf5fd 0%, #cfe7fa 45%, #55acee 100%)` | hero panel background |
| Ocean solid | `#226699` | base strip under the wave |
| Wave fill | `#ffffff` at `35%` opacity | tiled wave SVG |
| Input idle bg | `#efefef` | default input/button bg |
| Input hover bg | `#e6e6e6` | |
| Input focus bg | `#ffffff` | paired with blue border + ring |
| Social button bg | `#c0c0c0` → hover `#cccccc` → active `#b3b3b3` | lighten on interaction, never darken |
| Primary button bg | `#332e2e` → hover `#464040` → active `#241f1f` | |

### Dark mood (dashboard/internal)
| Token | Hex | Usage |
|---|---|---|
| App shell background | `#2b2a2f` | outermost body background |
| Layout background | `#222129` | main content area |
| Card background | `#1a191e` | data card / table container |
| Table row stripe | `#1e1d25` | odd rows |
| Row border | `#34323c` | between rows |
| Text (primary) | `#f5f3f9` | |
| Text (muted) | `#706d84` | table header labels |
| Accent purple | `#5926fc` | decorative background blob behind header only |
| Translucent button bg | `rgb(0 0 0 / 16%)` | header buttons/icon tiles |

**Rule: never introduce a new accent color.** `#55acee` is the only interactive/focus accent in the light mood; `#5926fc` is the only accent in the dark mood. All hover/focus states reuse existing palette tones — lighten or darken the element's *own* base color rather than picking a new hue.

---

## 4. Layout System

### Page wrapper (every page)
```css
body {
  margin: 0;
  box-sizing: border-box; /* applied via universal selector */
  overflow: hidden;
  font-family: "Euclid Circular B", "Poppins", sans-serif;
}
* { box-sizing: border-box; }

.page {
  width: 100vw;
  height: 100vh;
  display: grid;
  place-items: center;
}
```
Every route is a single, non-scrolling viewport centered around one card/panel. Body background and text color are set per-mood on the `.page` element itself (e.g. `.page.login-9`, `.page.signup-9`), not globally — this lets light and dark pages coexist without leaking styles.

### Auth card pattern (hero + form split)
- Outer card: `border-radius: 30px`, `box-shadow: 0 20px 40px -10px rgb(0 0 0 / 10%)`, `padding: 8px`, `width: clamp(300px, 85vw, 500px)`.
- Two children, each `flex: 1 1 50%`:
  1. **Hero panel** — `border-radius: 24px`, `min-height: 200px`, gradient background, contains the animated ocean/wave scene.
  2. **Form panel** — `padding: 30px 18px`, `display: flex; flex-direction: column; gap: 12px`.
- **Mobile (default)**: stacked vertically, hero on top.
- **≥485px**: card widens to `clamp(300px, 90vw, 740px)`, form padding becomes `50px` inline, social buttons switch from row to column, extra label text ("Login with" / "Sign up with") becomes visible before the provider name.
- **≥640px**: card becomes `flex-direction: row` (hero left, form right), card padding becomes `8px 0 8px 8px` so the hero bleeds to the card's edge, form padding becomes flat `30px`.

### Dashboard/table pattern (dark mood)
- Full-height dark `.layout` wrapper.
- `.layout-header`: contains a huge circular decorative blob (`::before`, `500vw` wide circle in accent purple, positioned bottom-center, overflow hidden) sitting behind a centered content row (max-width 1000px) with a heading (left) and a pill button (right).
- `.layout-card`: sits *on top of* the header's rounded blob using a **negative top margin** (`margin: -100px auto 30px`) to create the overlapping "card floats over hero" effect — reuse this trick anywhere a card needs to visually sit on a decorative header.
- Sticky table headers (`position: sticky; top: 0`), zebra-striped rows, `60px` row height, hover raises row `opacity` from `0.65` → `1`.

---

## 5. Component Library

### Buttons & Inputs (shared base)
```css
height: 52px;
border-radius: 14px;
width: 100%;
font-size: 16px;
transition: background-color .25s ease, box-shadow .25s ease, transform .15s ease, opacity .25s ease;
```
- All buttons: `cursor: pointer`.
- **Interaction philosophy: color shift only.** No lift (`translateY`) or drop shadow on hover for buttons — keep feedback to background-color changes plus a focus ring for keyboard users. (Earlier iterations used a hover lift + shadow; this was intentionally removed in favor of a flatter, calmer feel.)

### Text inputs
- Idle: `2px solid transparent` border, bg `#efefef`.
- Hover: bg → `#e6e6e6`.
- Focus: bg → `#ffffff`, border → accent blue `#55acee`, plus a soft glow: `box-shadow: 0 0 0 4px rgb(85 172 238 / 20%)`. Placeholder text lightens to `#b8b8b8` while focused.
- This focus-ring treatment (blue border + 4px 20% opacity glow) is the **standard focus state for every interactive element** — apply it to buttons' `:focus-visible` and checkboxes too, so keyboard focus always looks the same across the whole product.

### Social/secondary buttons
- Bg `#c0c0c0` → hover `#cccccc` → active `#b3b3b3` (lighten, don't darken).
- Icon + label, centered, `gap: 10px`, label text in muted `#5c5c5c`.
- `:focus-visible` gets the standard blue glow ring.

### Primary/submit button
- Bg `#332e2e`, text `#f9f9f9`, weight 500, `17px`.
- Hover → `#464040`, active → `#241f1f` (background only, no lift/shadow).
- `:focus-visible` gets the standard blue glow ring.

### Custom checkbox
```css
appearance: none;
width: 18px; height: 18px;
min-width: 18px; min-height: 18px;
flex-shrink: 0;              /* critical: prevents squish inside flex rows */
border-radius: 5px;
border: 2px solid #c0c0c0;
background: #efefef;
display: grid; place-items: center;
```
- Checked: bg + border → `#332e2e`, white checkmark (drawn via `::after` with two rotated borders, not an image).
- Hover (unchecked): border → `#9e9e9e`.
- Focus-visible: standard blue glow ring.
- **Always pair with `flex-shrink: 0`** when a checkbox lives inside a flex row with a text label — otherwise it gets compressed into a non-square shape.
- Label + link text: `13px`, muted `#5c5c5c` body text, with any inline link in `#332e2e` weight 500 underlined.

### "Or" divider
A hairline with a centered pill label, built from one empty `<span>`:
```css
.divider { position: relative; text-align: center; height: 24px; font-weight: 500; opacity: .5; }
.divider::before { content:""; position:absolute; top:50%; left:0; width:100%; translate:0 -50%; height:1px; background: rgb(0 0 0 / 40%); opacity:.6; }
.divider::after { content:"Or"; position:absolute; top:50%; left:50%; translate:-50% -50%; background:#fff; font-size:12px; padding:0 12px; }
```

---

## 6. Animation System — the signature wave

The hero panel's animated ocean is the brand's signature motion and should appear on every hero panel unless a page explicitly calls for a static image instead.

```css
.ocean {
  height: 5%; width: 100%;
  position: absolute; bottom: 0; left: 0;
  background: #226699;
}

.wave {
  background: url(./wave.svg) repeat-x;
  position: absolute;
  top: -198px;
  width: 6400px;
  height: 198px;
  animation: wave 7s cubic-bezier(0.36, 0.45, 0.63, 0.53) infinite;
}

.wave:nth-of-type(2) {
  top: -175px;
  animation:
    wave 7s cubic-bezier(0.36, 0.45, 0.63, 0.53) -0.125s infinite,
    swell 7s ease -1.25s infinite;
}

@keyframes wave {
  0% { margin-left: 0; }
  100% { margin-left: -1600px; }
}

@keyframes swell {
  0%, 100% { transform: translate3d(0, -25px, 0); }
  50% { transform: translate3d(0, 5px, 0); }
}
```
- Two `.wave` divs layered inside `.ocean`, second one offset in timing/position for a parallax effect.
- `wave.svg` is a small (`200×198`) horizontally-tileable SVG: a simple double-crest curve, white at `35%` opacity, so it reads as a translucent crest over the solid `#226699` ocean strip.
- Duration is always `7s`; don't speed this up — the slow cycle is part of the calm brand feel.

---

## 7. Assets & Icons

- Logo mark: circular blue (`#55acee`) badge with a simple white wave squiggle, paired with wordmark text "wavy.ai" in weight 600.
- Social icons: standard multicolor Google "G" and blue-circle Facebook "f", `20–24px`.
- No icon font is used on auth pages (icons are inline SVG). Dashboard/internal pages use **Phosphor Icons** (`<i class="fa-solid ...">`-style usage seen in Layout.jsx is legacy Font Awesome and should be migrated to Phosphor for consistency going forward).

---

## 8. Naming Convention

Each page/variant gets its own numbered prefix matching its component name, e.g.:
- `Login9.jsx` / `Login9.css` → classes prefixed `login-9-*`
- `Signup9` → classes prefixed `signup-9-*`

When generating a new page (e.g. "forgot password", "OTP verification", "onboarding step 2"), pick a descriptive-plus-number name (`ForgotPassword3`, `Otp2`, etc. — following whatever numbering scheme the existing variant library uses) and prefix **all** classes with that name. Never share class names across variants; duplicate the shared rules (card, hero, form, button, input, checkbox, divider, wave) under the new prefix so each page file stays self-contained, exactly as `Login9.css` and `Signup9.css` do today.

---

## 9. Checklist for Any New Page

- [ ] Body: `margin:0`, `overflow:hidden`, universal `box-sizing:border-box`, correct font-family.
- [ ] Root element uses `.page` + `.{name}-{n}` classes for full-viewport centering.
- [ ] Colors pulled only from the palette table above — no new hues introduced.
- [ ] Card radius `30px`, inner hero radius `24px`, buttons/inputs radius `14px`, checkbox radius `5px`.
- [ ] All interactive elements (inputs, buttons, checkboxes) share the same `#55acee` focus-ring treatment.
- [ ] Buttons: background-color-only hover/active, no lift/shadow.
- [ ] Responsive breakpoints at `485px` and `640px` reused verbatim unless content requires otherwise.
- [ ] If a hero panel is present, include the two-layer animated wave exactly as specified.
- [ ] Classes prefixed with the new page's unique name; no shared/global class names across pages.
