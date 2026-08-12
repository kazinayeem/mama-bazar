# Ghorer Bazar — Design System Guidance

## 1. Context and Goals

**Design intent (one sentence):** Ghorer Bazar's storefront is a clean, functional, keyboard-first e-commerce surface that uses a token-driven system so every page ships consistent, accessible behavior without one-off visual exceptions.

Scope covers the six dominant component families on the storefront, with observed page density: **links (290), buttons (205), inputs (102), cards (78), lists (30), navigation (2) per page**.

Goals:
- Standardize anatomy, states, and behavior for every high-density component family.
- Guarantee WCAG 2.2 AA compliance with acceptance criteria that are testable in CI or by inspection.
- Eliminate raw hex values, one-off spacing, and local typography exceptions from implementation.
- Define responsive, long-content, overflow, and empty-state behavior up front so storefront pages degrade predictably.

## 2. Design Tokens and Foundations

### 2.1 Typography

| Token | Value | Notes |
|---|---|---|
| `font.family.primary` | Open Sans | |
| `font.family.stack` | Open Sans, sans-serif | fallback must never render a different family |
| `font.size.base` | 16px | root/body size; must not be overridden per viewport |
| `font.weight.base` | 500 | body weight |
| `font.lineHeight.base` | 19.2px | 1.2 ratio at base size |

Scale (semantic use only — never arbitrary sizes):

| Token | Size | Intended use |
|---|---|---|
| `font.size.xs` | 10px | micro-labels, legal text |
| `font.size.sm` | 12px | metadata, secondary info |
| `font.size.md` | 13px | input labels, card secondary text |
| `font.size.lg` | 14px | default UI text, buttons |
| `font.size.xl` | 16px | body copy, button emphasis |
| `font.size.2xl` | 20px | section headings, card titles |
| `font.size.3xl` | 22px | page headings |
| `font.size.4xl` | 28px | page hero headings |

### 2.2 Color

| Token | Value | Primary use |
|---|---|---|
| `color.text.primary` | #222831 | default text on light surfaces |
| `color.text.secondary` | #041f1e | headings, emphasis |
| `color.text.tertiary` | #666666 | secondary/metadata text |
| `color.text.inverse` | #ffffff | text on dark surfaces only |
| `color.surface.base` | #000000 | dark surfaces (headers, footers) |
| `color.surface.muted` | #f48721 | brand/accent actions, badges |
| `color.surface.strong` | #fbf9f5 | page/card backgrounds |

Contrast constraints (computed, must hold in implementation):

| Pair | Ratio | Constraint |
|---|---|---|
| `color.text.primary` on `color.surface.strong` | 14.1:1 | passes AA normal text |
| `color.text.primary` on `color.surface.muted` | 5.9:1 | passes AA normal text |
| `color.text.tertiary` on `color.surface.strong` | 5.5:1 | passes AA normal text |
| `color.text.tertiary` on `color.surface.base` | fails | tertiary text must never sit on `color.surface.base` |
| `color.text.inverse` on `color.surface.muted` | fails (2.5:1) | inverse text must never sit on `color.surface.muted`; use `color.text.primary` |
| `color.text.inverse` on `color.surface.base` | 21:1 | passes; default pairing for dark surfaces |
| `color.text.secondary` on `color.surface.strong` | 16.4:1 | passes AA normal text |

### 2.3 Spacing

`space.1=2px`, `space.2=5px`, `space.3=6px`, `space.4=8px`, `space.5=10px`, `space.6=12px`, `space.7=16px`, `space.8=17px`.

Rules:
- All paddings, margins, and gaps must be composed from these tokens; no arbitrary pixel values.
- Nested components must space with `space.4`–`space.6`; page sections must space with `space.7`–`space.8`.
- Touch targets must be ≥ 44×44 CSS px (WCAG 2.2 AA target size). Use padding from the scale to reach this — do not rescale fonts to compensate.

### 2.4 Radius, Shadow, Motion

`radius.xs=4px`, `radius.sm=6px`, `radius.md=8px`, `radius.lg=100px` (pills) | `motion.duration.instant=300ms`, `motion.duration.fast=400ms`.

- Motion must be applied only for state transitions and must never block interaction (`pointer-events` must stay live during transitions).
- Motion must honor `prefers-reduced-motion: reduce` — fall back to instant swaps.

## 3. Component-Level Rules

### 3.1 Navigation (2 per page)

**Anatomy:** Header nav with logo link (skip links), primary menu (desktop), hamburger trigger (mobile), cart link with count badge, and a footer nav.

**Variants:** top header nav; footer nav; mobile drawer (must reuse one implementation).

**States:**
- **default:** links styled as `Link`; current page labeled `aria-current="page"` and `aria-current` styling.
- **hover:** only on pointer devices; underline or bg shift from `space` tokens; must not change layout box size.
- **focus-visible:** 2px outline in `color.text.primary` offset 2px, visible on keyboard focus only (use `:focus-visible`, never remove it).
- **active:** pressed state via bg overlay token-less — use `color.surface.strong` invert or opacity 0.8 — must remain ≥ 4.5:1 on `color.surface.base`/`color.surface.strong` respectively.
- **disabled:** nav items must never be disabled silently; if a destination is unavailable, render as link to an empty-state page with explanation.
- **loading:** nav must not show loading spinners; cart count may show skeleton dot on `color.surface.muted`.
- **error:** broken links may show a fallback message "Try again later" on an error card; the header must still render.

**Keyboard:** tab order must be header → main → footer, with skip link first ("Skip to main content"); hamburger toggles the drawer with Escape closing and focus return to trigger.
**Pointer/touch:** 44px targets for all nav links and icons; drawer swipe-close must not conflict with page scroll.

**Responsive/edge:** below container breakpoint (e.g., 768px), menu collapses to drawer; cart badge must truncate counts ≥ 1000 to "999+" with full count in `aria-label`.

**Spacing/typography:** nav links `font.size.lg`; gaps `space.5`–`space.7`.

### 3.2 Buttons (205 per page)

**Anatomy:** label + optional icon + optional loading/error slot. Must have a visible label — icon-only buttons must carry `aria-label` and `title`.

**Variants:** primary (`color.surface.muted` bg, `color.text.primary` text), secondary (outline), tertiary (ghost/plain), and destructive (must use `color.text.secondary` with a destructive border style — never a disabled shade).

**States (all variants):**
- **default / hover:** hover only on pointer devices; background shift stays ≥ 4.5:1 contrast with text.
- **focus-visible:** 2px `color.text.primary` outline, 2px offset; must be visible on keyboard tab; never removed on mouse click for keyboard users.
- **active:** `motion.duration.instant` press feedback (200%? no — press scale within `space.1` or bg lighten); must not shift layout more than 1px.
- **disabled:** `aria-disabled="true"` and should use opacity shift; a disabled button must never be focusable by keyboard; provide an enabled alternative with explanation in `aria-describedby`.
- **loading:** spinner replaces icon (keep label visible or swap label to "Loading…" with `aria-live="polite"`), button must be `aria-busy="true"` and the interaction disabled to prevent double-submit.
- **error:** on failed submission, button re-enables with error message via toast or inline `role="alert"`; label must describe retry ("Try again").

**Keyboard:** Enter/Space activate; focus stays in the flow; keyboard activation must fire on keyup for Space (avoid double-fire).
**Pointer/touch:** ≥ 44×44px, no ghost clicks in touch when double-tap zoom is disabled — use `touch-action: manipulation`.

**Responsive/edge:** full-width on smallest screens with `space.5` horizontal padding; labels must truncate with `max-width` + ellipsis, never wrap to two lines unexpectedly.

**Spacing/typography:** primary padding `space.5 × space.7`; label `font.size.lg` weight base.

### 3.3 Links (290 per page)

**Anatomy:** text or image-link (product cards, category tiles, banner CTAs) with `href`; every link must be distinguishable from non-link text (underline or `color.surface.muted` color). Links should always have visible affordance.

**States:** hover (underline), `focus-visible` 2px outline, active/inactive (visited state may use `color.text.tertiary`), loading (for in-page navigation show skeleton of target area; for external navigation, non-blocking), error (links must never redirect to 404 — must point to a valid empty-state page).

**Keyboard:** Tab enters links in DOM order; Enter activates. Skip link must be first Tab stop.
**Pointer/touch:** ≥ 44px targets; inline links may be exempt if surrounded by ≥ 24px spacing with `text-decoration` underline.

**Responsive/edge:** long link labels must truncate with ellipsis + `title` attribute for full text; links to cart/checkout must never be hidden on mobile; empty state — a broken category must render "No products yet" card, not a dead link.

**Spacing/typography:** inline links inherit `font.size.lg` body; thumbnails use `radius.md` and `space.7` gaps.

### 3.4 Inputs (102 per page)

**Anatomy:** label + control + optional hint/error text; label must be programmatically associated (`for`/`id` or wrapping). Every input must have a label — placeholder alone is prohibited as a label.

**Variants:** text/email/password (with show/hide toggle), select, search, checkbox → search uses icon + clear button; selects must use native or ARIA-compliant custom listbox.

**States:**
- **default:** border 1px `color.text.tertiary`-derived line (use token only), bg `color.surface.strong`.
- **hover/active/focus-visible:** focus ring 2px `color.surface.muted` + 1px border shift; no `box-shadow` exceptions.
- **disabled:** `aria-disabled="true"`, reduced opacity; disabled inputs must remain findable via fieldset/legend context.
- **loading (search/async):** debounced spinner in the input via `role="status"` for screen readers.
- **error:** `aria-invalid="true"` + `aria-describedby` error text `<div id>`, error text 4.5:1 contrast, presented inline, not just red-border color change (color must never be the sole indicator).

**Keyboard/pointer/touch:** Tab navigates, Enter submits forms; show/hide password toggle must be ≤ 44px and focusable; search clear button focusable.

**Responsive/edge:** inputs full-width ≤ 480px; label above control; hint below; auto-complete `autocomplete` attributes; long placeholder must truncate with ellipsis; empty-state: fields render with empty placeholder + hint.

**Spacing/typography:** `font.size.lg` input text; padding `space.6 × space.5`; labels `font.size.md` weight 500.

### 3.5 Cards (78 per page)

**Anatomy:** image/media, title, price/metadata, CTA, optional badge. Must be a single clickable region (`<a>` wrapping) or structured sections with links.

**Variants:** product card, category card, banner card, info/empty card.

**States:** hover (lift/underline only — no spinning or parallax), `focus-visible` outline on whole card, active press, disabled (sold-out shows badge "Out of stock" — card still renders with metadata), loading (skeleton: shimmer blocks of surface tones, `aria-busy="true"`), error (image `alt` fallback block on failed load).

**Keyboard/pointer/touch:** whole-card link gives single Tab stop, 44px CTA; nested interactive elements inside a card (wishlist button, add-to-cart) must have separate focus stops with distinct outline.

**Responsive/edge:** grid `auto-fill, minmax(240px, 1fr)`; long titles clamp to 2 lines with ellipsis; price overflow → `font.size.xl` with baseline alignment; empty-state: category grid renders empty card "No products in this category yet" with a CTA link instead of a blank grid.

**Spacing/typography:** card padding `space.7`; padding between media/title `space.5`; title `font.size.xl` weight base; price `font.size.2xl`; badges `radius.lg` pill with `font.size.sm`.

### 3.6 Lists (30 per page)

**Anatomy:** ordered/unordered list markup (`<ul>`/`<ol>`); list items may contain cards, filters, or breadcrumbs.

**Variants:** product list, filter chips, breadcrumbs, footer links.

**Keyboard:** `aria-current` on active chip/breadcrumb; no list may intercept Tab — tab order is DOM-ordered list items.
**Pointer/touch:** chips ≥ 44px; breadcrumb separators must be non-focusable spans.

**Responsive/edge:** lists must wrap gracefully; filter chips overflow → horizontal scroll with visible scroll affordance on touch; empty-state: filters list of zero items renders "No filters available" text or hides with an explanatory note — never an empty box.

**Spacing/typography:** list gaps `space.5`; bullet spacing `space.4`; roles must not be faked — use semantic `<ul>`/`<ol>` + `li` only.

## 4. Accessibility Requirements (WCAG 2.2 AA)

Every rule below is testable:

| ID | Requirement | Pass check (automated or manual) |
|---|---|---|
| A11Y-1 | Body text ≥ 4.5:1 contrast | Axe/Pa11y scan: `color-contrast` failures = 0 |
| A11Y-2 | No text on `color.surface.muted` with `color.text.inverse` | Scan for pair; contrast fails |
| A11Y-3 | All interactive elements reachable via Tab in DOM order | Keyboard walkthrough deep-links every CTA |
| A11Y-4 | Visible `focus-visible` outline (2px, 2px offset) on all interactive elements | Tab through page; outline must be visible on each stop |
| A11Y-5 | Touch targets ≥ 44×44 CSS px | Automated target-size audit on main nav, buttons, chips |
| A11Y-6 | All inputs have programmatic labels | `label[for]`/`aria-labelledby` present on 100% of inputs |
| A11Y-7 | Error messages linked via `aria-describedby` + `aria-invalid` | Automated check of form error association |
| A11Y-8 | No motion without `prefers-reduced-motion` handling | Motion queue disabled under `reduce`; page function unaffected |
| A11Y-9 | Loading/error states announced (`aria-live`, `role="status"`, `role="alert"`) | Run SR check: states announced for buttons, search, cards |
| A11Y-10 | Icon-only elements have `aria-label` | Automated: no unnamed icons flagged |
| A11Y-11 | `aria-current` on nav/active list items | Automated per active item |

WCAG 2.2 AA target-size exemption: inline links within a paragraph are exempt when the line-height/spacing creates ≥ 24px effective hit area; this exemption must be documented per page, not implied.

## 5. Content and Tone Standards

Tone: concise, confident, customer-first. Default language is English; currency and units shown in locale format.

**Do (patterns):**
- Primary action labels: verb + noun → "Add to cart", "Checkout", "Track order".
- Empty states: what + why + next step → "No products in this category yet. Browse all products →".
- Errors: what failed + what to do → "Payment failed. Try a different card or contact support."
- Loading: "Loading…" or "Adding to cart…" (progressive, not spinner-only if announced).

**Don't (prohibited):**
- Vague actions: "Click here", "More", "Go".
- ALL-CAPS microcopy except the brand wordmark.
- Ambiguity in buttons like "Submit" without context — add `aria-label` + visible context.
- Using placeholder as label or hint duping the label.

## 6. Anti-Patterns and Prohibited Implementations

| # | Prohibited | Instead |
|---|---|---|
| 1 | Raw hex values in components (`#f48721` inline in a style) | Semantic tokens only |
| 2 | One-off spacing/typography (inline margins, arbitrary font-size) | Scale tokens |
| 3 | Hidden focus (outline removed or 0 visibility) | 2px outline + offset |
| 4 | Low-contrast text (tertiary on `color.surface.base`, inverse on muted) | Enforced pairs from §2.2 |
| 5 | Disabled buttons that silently block features | Disabled + `aria-disabled` + accessible explanation path |
| 6 | Placeholder-as-label forms | Explicit programmatic labels |
| 7 | Red-only error indication | `aria-invalid` + text message |
| 8 | Unlabeled icon-only controls | `aria-label` (and `title` for pointer) |
| 9 | Color-only status (stock, discounts, ratings) | Behavior: color + text/icon/symbol |
| 10 | Motion that blocks interaction or ignores `prefers-reduced-motion` | Short, unblocked transitions |

## 7. QA Checklist

**Pre-merge (automated):**
- [ ] Contrast scan on changed pages: 0 AA failures.
- [ ] Keyboard walk: Tab order, visible focus, no traps.
- [ ] Target sizes ≥ 44px on all interactive elements (nav, buttons, chips, icons).
- [ ] All inputs labeled; error messages associated.
- [ ] No raw hex, one-off spacing, or arbitrary font sizes in changed code.
- [ ] `aria-live`/`aria-busy` present on loading and error paths.

**Per-page (manual):**
- [ ] Mobile ≤ 480px: inputs full-width; nav collapses to drawer; no horizontal page scroll.
- [ ] Empty states for search, category, cart, and filters render guidance + CTA.
- [ ] Long content: titles clamp, prices align, link labels truncate with `title`.
- [ ] `prefers-reduced-motion` on: no motion; all interactions function.
- [ ] SR spot-check: page title, skip link, cart count, form errors announced.

**Regression:**
- [ ] Density budget respected: no new component families beyond the six (links 290 / buttons 205 / inputs 102 / cards 78 / lists 30 / nav 2 per page) without a system-level review.
- [ ] New component uses same state matrix (default/hover/focus-visible/active/disabled/loading/error).