---
name: frontend-design
description: >-
  ChronoLogic design system and responsive-UI conventions for this Laravel +
  Livewire + Bootstrap app. Use whenever styling, theming, or improving
  responsiveness/density of any Blade view, Livewire component, the x-ui.*
  library, the public landing (`/`), or guest auth (`/login` and related) —
  not generic frontend advice. Specific to this codebase's dark ambient,
  Space Grotesk/JetBrains Mono, blue→purple gradient, and known performance
  traps.
---

# ChronoLogic frontend design system

This is a Laravel 10 + Livewire 3 app, Bootstrap-based, with a shared
`x-ui.*` Blade component library (`resources/views/components/ui/`) used by
~200+ views. Almost all visual styling lives in `resources/css/app.css`
(global overrides), not in the component files themselves — editing that one
file (or `resources/js/app.js` / `resources/views/layouts/app.blade.php`)
propagates to most of the app. Don't re-derive the visual language from
scratch — it already exists; extend it consistently.

## Design tokens (already defined, reuse — don't invent new colors)

Defined in `resources/css/app.css` `:root` (~line 8):

- `--primary: #3b82f6` (blue), `--accent: #a855f7` (purple) — the ChronoLogic
  identity color pair. Every gradient/accent in the app is
  `linear-gradient(135deg, var(--primary), var(--accent))` — this exact pair,
  never a flat single color, never a different hue (no teal/cyan — that was
  the original `chronologic-landing.html` reference but was deliberately
  recolored to this blue→purple pair to match the rest of the app).
- `--bg-body: #070a13`, `--bg-card: rgba(13, 18, 30, 0.52)` (glass: dark
  enough that badges don't blow the fill out, open enough that the page
  grid and `.cl-cursor-glow` show through). Do **not** drop an opaque
  `#070a13` panel behind cards (`.app-page-shell` used to, matching `/tasks2`,
  and it killed the flashlight). Keep `.app-content-wrapper` as a light tint,
  not an `rgba(..., 0.8)` slab, and don't put `backdrop-filter` on that
  full-viewport wrapper — it frosts the glow before any `.card` can pick it
  up. Card blur stays modest (`8px`).
  `--text-main: #f1f5f9`, `--text-muted: #94a3b8`, `--glass-border: rgba(255,255,255,0.1)`.
- Fonts: **Space Grotesk** is the global body/display font (loaded via
  `<link>` in `layouts/app.blade.php` + `layouts/guest.blade.php`, applied via
  `* { font-family: 'Space Grotesk', 'Inter', ... }`). **JetBrains Mono** is
  for data/numbers/mono UI via the `.font-mono` / `.tg-mono` utility classes
  (includes `font-variant-numeric: tabular-nums`).

Where headers already get the gradient automatically: any `<h1>`–`<h6>`
inside a `<header>` element gets `background: linear-gradient(135deg, var(--primary), var(--accent))`
text-clip styling for free (`app.css` "header h1..h6" rule) — don't re-add
gradient text manually on **in-app** page titles, it's already global.

**Trap — never put landing/auth titles in `<header>`.** That same rule is
`font-size: 2.5rem !important` + full-heading gradient clip. The public
hero needs a large display `h1` with only the italic `<em>` in gradient
(`.cl-landing h1 em`). Use `<nav class="cl-landing-nav">` + `<section>`
for hero/auth copy, not `<header>`.

## Ambient background (global, already applied everywhere)

Three layers, all in `app.css`, none of them animate anything expensive:

1. `body::before` / `body::after` — fixed, `z-index: -1`, `contain: strict`
   grid + grain texture, viewport-sized (not content-height-sized).
2. `.app-content-wrapper::before` — the wrapper's own crisp grid pattern.
3. `.cl-cursor-glow` — one JS-injected div (`resources/js/app.js`), position
   driven by a single `requestAnimationFrame`-throttled `mousemove` listener.

**Known trap — don't repeat it**: `backdrop-filter` on an element that covers
the viewport (`.app-content-wrapper` or its `::before`) frosts the cursor
glow *and* becomes a containing block for `position: fixed` descendants
(modals jump to the middle of a long page). Grid/grain belong on that
layer as `background-image` (stays crisp). Leave frosting to the cards
themselves. Never paint an opaque panel (`.app-page-shell { background:
#070a13 }`) between the glow (`z-index: -1` on `body`) and `.card` —
cards then sample a solid slab, not the flashlight.

**Perf trap — don't repeat it**: never `querySelectorAll` + `getBoundingClientRect()`
on every `mousemove` for more than a handful of elements. A magnetic-button
hover effect looks great on 2 header buttons; the same code applied to every
`.btn-primary` site-wide will visibly lag on any CRUD table with dozens of
per-row action buttons. Keep motion effects **opt-in via a class**
(`.xuiv2-magnetic`) on curated CTAs only. The cursor glow itself is safe
globally because it's O(1) — one element, no DOM scanning.

## Buttons, cards, headers

- `.btn-primary` — always the primary→accent gradient (`app.css` ~line 260).
- `.card:hover::before` — a 2px top-edge accent bar (primary→accent gradient,
  `scaleX` reveal) on every `.card`, globally. Don't add per-page hover
  accents; it's already there.
- `.app-header .btn-outline-secondary` — mono font + purple
  border/text on hover (`app.css`, "Przyciski w nagłówku strony"). Applies to
  every page header automatically; don't re-add this per view.

## Public landing + guest auth (must stay on the app theme)

`/` (`welcome.blade.php`) and all guest auth screens live in
`<x-guest-layout>` so they get `app.css` + `app.js` (ambient grid/grain +
cursor glow). Never go back to a standalone HTML page with its own CDN
CSS (that was the old MK TECHNIC welcome). Guest layout has no
`.app-content-wrapper` — don't duplicate global ambient layers on these
pages.

**Reference file, not a source of identity color:** the sample
`chronologic-landing.html` (structure, sections, marquee, module grid) was
teal/cyan. Recolor to primary→accent. Do **not** port GSAP preloader,
scroll-pinned data-flow, or per-mousemove magnetic on many elements.

Scoped styles live under `.cl-landing` in `app.css`. Shared Blade:

- `x-landing.nav` — fixed glass nav, logo + Chrono/`Logic` gradient
  wordmark, optional slot (module links + live clock), CTA button.
  On `< 860px` hide `.cl-landing-nav__menu`, keep the CTA.
- `x-landing.footer` — same logo row, used only on the landing.
- `x-landing.auth-shell` — same nav + split copy/form. Used by
  `login`, `register`, `forgot-password`, `reset-password`,
  `confirm-password`, `verify-email`. Form in `<x-ui.card>` + `x-ui.input`
  / `x-ui.button`, Polish labels. Login passes `cta-label=""` so the nav
  doesn't repeat „Zaloguj się”.

Clock: one tiny `setInterval` in `layouts/guest.blade.php` for
`[data-cl-clock]` — not a GSAP timeline. Marquee is CSS-only
(`@keyframes cl-marquee`); respect `prefers-reduced-motion`.

Local landing surfaces (`--cl-line`, `--cl-surface`) are dark layout
tokens for 1px-gap grids, not a second identity palette. Module/tile
hover top-bar uses the same primary→accent gradient as `.card::before`.

## Logo (clock at 4:00) — `x-brand-mark`

All marks live in one component: `resources/views/components/brand-mark.blade.php`
+ per-variant partials in `resources/views/partials/brand-mark/`. Variants:
`dial` (plain clock), `aperture` (segmented ring), `bot` (Chrono's face —
bottom half of the dial doubles as a smile), `monogram` (**current app
default** — letter C built from the dial, hour hand pointing into the
opening), `pulse` (dial crossed by an ECG line), `timer` (kitchen-timer knob
+ countdown arc, used by the boot screen). All share a `0 0 40 40` viewBox,
the brand gradient on the minute hand, `--warning` (`#f59e0b`) on the hour
hand, and 4:00 as the time. Every render gets a unique `<linearGradient id>`
so nav + footer on the landing don't clash. Preview grid: `/2`.

Variants whose dial isn't centered (`bot`, `timer`) set their own rotation
origin via `--cl-mark-pivot`; `.cl-mark__hand` reads it. Add the variable to
any new off-center variant or its hands will spin around a point next to the
dial.

`x-application-logo` is a thin wrapper picking the app-wide variant — change
it there and navbar, landing nav and footer follow. It deliberately does
**not** pass `$attributes` through: callers hand it `class="navbar-logo"`
(`height: 5rem`), which would blow the mark up to 80px. Size landing SVGs
with `.cl-landing-logo svg`. Keep `public/favicon.svg` redrawn to match
whatever variant is current. Navbar wordmark: `Chrono` +
`<span class="navbar-brand-name__accent">Logic</span>` (same gradient as page
titles), never flat `#a855f7`.

## Boot screen (`x-boot-screen`)

Full-screen "Inicjalizacja systemu": breathing mark, orbiting arc, progress
bar, four steps lighting up green in sequence (~3.5s, CSS-only).

**Trap that caused a visible "reload"**: it originally fired on the login
form's `submit`, so the POST navigation killed the animation mid-way and the
destination restarted it. A full-page navigation always destroys a running
animation — so the sequence plays on the **destination**:
`AuthenticatedSessionController::store()` flashes `cl_boot`,
`layouts/app.blade.php` then renders `<x-boot-screen auto />` already visible,
and `.cl-boot--auto` fades itself out with a delayed CSS animation (not a
`setTimeout`, which a slow first paint could desync). `window.clShowBootScreen(auto)`
in `app.js` exists for the `/2` demo; it forces a reflow before re-adding the
class so the animation can restart.

## `x-ui.tabs` — strip that wraps, not a row of cards

User rejected card-like tabs (they competed with warehouse picker cards
and wrapped badly on employee profile). `.nav-tabs-ui` is a **tab strip**:
baseline, gradient underline/label on `.active`, small icons, badges.
`flex-wrap: wrap` — never horizontal scroll. `compact-mobile` keeps the
existing dropdown on `< md` (`ui-compact-nav`); pass `mobile-label`
when the control isn't a profile section (warehouse uses „Zakładka”).

## Warehouse picker cards (`/equipment/tab/stock` and issues)

`.eq-wh-card` — icon + label, primary→accent active state (not teal).
There is **no** fake „Wszystkie magazyny” card with
`data-warehouse-id="*"`. `highlightAll` lights up every real warehouse
card. Mobile: the same compact dropdown as tabs. On `< md`, stock/issues
also render duplicate **cards** (`_stock-item-card.blade.php`,
`_qty-tile.blade.php`) like `/tasks2`; the desktop table stays.

Stock list has **no action icons**. The whole card / table row is the hit
target (`stretched-link` on the name, `data-eq-stock-href` on the `<tr>`).
The only control that must stay above that overlay is the variant toggle
(`.eq-stock-item__toggle` — `position: relative; z-index: 2` +
`stopPropagation`). Edit, withdraw and restore live on `equipment/show`.
Mobile card header is a left-aligned photo slot (same box for
with/without variants) + name + compact meta (category as text, small
toggle — not fat `x-ui.badge`s). Photos `align-items: flex-start` so a
taller variant row does not drop the thumbnail.

## Responsive / mobile patterns — pick based on row complexity

The app is desktop-first with many dense `<table>`s. Four established
patterns, pick the cheapest one that solves the actual problem:

**1. Sticky first column** (simplest — inventory/stock-style tables where a
name + several numeric columns don't fit, but per-row markup is complex
enough that duplicating it is risky, e.g. `equipment/_stock-item.blade.php`):

```css
@media (max-width: 767.98px) {
    .my-table td:first-child, .my-table th:first-child {
        position: sticky; left: 0; z-index: 2; background: #0d1424;
        box-shadow: 3px 0 8px rgba(0,0,0,0.35);
    }
}
```

**2. `data-label` table→rows transform** (same Blade markup, no duplication —
use when a table's `<td>`s already have heavy per-cell logic, like clickable
badges with computed states — see `weekly-overview/index.blade.php`'s
"Przypisani pracownicy" table + matching CSS in `app.css`):

```html
<td data-label="Rola w projekcie">...badge logic...</td>
```
```css
@media (max-width: 767.98px) {
    .my-table thead { display: none; }
    .my-table, .my-table tbody, .my-table tr { display: block; width: 100%; }
    .my-table tr { border: 1px solid var(--glass-border); border-radius: 12px; margin-bottom: .65rem; padding: .15rem .85rem; }
    .my-table td { display: flex !important; justify-content: space-between; border-top: 1px solid var(--glass-border) !important; padding: .5rem 0 !important; }
    .my-table td::before { content: attr(data-label); font-size: .68rem; text-transform: uppercase; color: var(--text-muted); }
    .my-table td:first-child { border-top: 0 !important; }
    .my-table td:first-child::before { content: none; }
}
```

**3. Real mobile cards, duplicate render** (worth the duplication when a row
represents one clear "entity" with a handful of fields — task rows, employee
rows): render `<table class="d-none d-md-block">` for desktop and a separate
`@forelse` loop wrapped in `<div class="d-md-none">` producing `<x-ui.card>`
per item, reusing the exact same `$item`/computed variables from the same
data source (see `resources/views/livewire/tasks-grid.blade.php` and
`resources/views/livewire/employees-table.blade.php`). Don't refetch data
twice — loop the same collection twice, computing per-row `@php` values in
each loop body (cheap, no extra queries).

**Preferred shell for new list tables:** `<x-data-table>` (`resources/views/components/data-table.blade.php`) + per-entity partials `livewire/partials/{entity}-row.blade.php` and `{entity}-row-card.blade.php`. Desktop table in `head`/`body` slots; mobile cards in `cards` slot. Each mobile card is `<x-ui.card class="dt-card">` with title link `class="stretched-link"` (whole card clickable — no separate "Zobacz" eye button). Label/value rows use `.dt-card__row` / `.dt-card__label` / `.dt-card__value` (grid + separator in `app.css` `.dt-*`). Sort/filter Livewire: trait `InteractsWithSortableTable`; logistics lists share `FiltersLogisticsEvents` + `logistics-events-filters` partial. Reference: `locations-table`, `projects-table`, `departures-table`.

**4. Filter toolbar above a list table:** use `<x-data-table>` with three
slots: `filters`, `activeFilters`, and table slots (`head`/`body`/…).
`<x-data-table-filters>` goes inside the `filters` slot — compact row of
`form-control-sm` / `form-select-sm` fields side by side (`.dt-toolbar`,
no labels above fields), count on the right as generic **„Rekordów: N"**
(never repeat entity name — page header already says „Lokalizacje" etc.).
`<x-data-table-active-filters>` + `<x-data-table-filter-chip>` go in the
`activeFilters` slot — shown only when `:has-filters="true"` on
`<x-data-table>`; renders „Filtry:" + removable chips + „Wyczyść" between
the two cards (same `.rp-active-filters` as `/tasks2`). Layout:
**karta filtrów → pasek aktywnych filtrów → karta tabeli** (`.dt-shell`).
Never repeat page title in filter card. Requires `clearFilters()` on the
Livewire component. Reference: `rotations-table.blade.php`,
`logistics-events-filters.blade.php` + `logistics-events-active-filters.blade.php`.

**5. Collapsible filter panel behind one button** (when a toolbar has more
than ~3 filter controls, they must NOT all stack full-width on mobile by
default). This behavior is built into `<x-data-table-filters>` (see above)
for standard list-table toolbars; for anything not using that component,
replicate the same Alpine pattern manually: `x-data="{ filtersOpen:
window.innerWidth >= 768 }"` on the wrapper, `x-show="filtersOpen"` on the
filter grid, a `d-md-none` toggle button showing an active-filter-count
badge. See `employees-table.blade.php` and the more elaborate grouped
version in `tg-filter-panel.blade.php` / `/recruitment-processes` (grouped
sections instead of a flat wall of toggles — prefer this over
one-checkbox-per-concept when there are many filter *types*, not just many
filter *values*).

**6. Overlapping avatars for a "participants" column:** when a table row
has multiple related people (departures/return-trips/transfers
"Uczestnicy" column) and full `x-employee-cell` rows per person would be
too tall, use `<x-ui.avatar-stack :employees="$collection" size="30px"
:max="4" />` (`resources/views/components/ui/avatar-stack.blade.php`).
Renders overlapping circular avatars (negative margin + ring via
box-shadow, `.avatar-stack` in `app.css`) with a native `title` tooltip
per avatar (no extra JS/tooltip component — keep it cheap since it renders
per row) and a `+N` overflow bubble past `max`. Pass any collection of
`Employee` models (or nullable — falsy entries are filtered).

## Recruitment process UI (`/recruitment-processes`)

Workspace for a single process is `/recruitment-processes/{id}` (`.rp-modal.rp-modal--page` in flow, not an overlay). List stays at `/recruitment-processes`.

**Comments:** never rebuild a mini note list (avatars + truncated body + custom modal). Use `<x-comments>` — likes, `@` mentions, replies, attachments, author-only edit. On the candidate card they sit in `.rp-profile__aside` **under** Edytuj / Ulubiony / Oznacz / Zadzwoń (`<x-comments embedded>`). Process-level comments stay as a full `<x-comments>` further down the page. Pass `embedded` so the component gets `.comments-card--embed` (transparent, no nested glass card inside `.rp-card`). Thread in the aside scrolls (`.rp-profile__comments .comments-thread`); don't grow the profile card without a max-height.

Contact-attempt notes are a different model (`RecruitmentContactAttempt.comment`) — only the author may edit/delete, including admins.

**`:has()` must not lock the list page:** `body:has(.rp-modal-wrap) { overflow: hidden }` froze `/recruitment-processes` because Narzędzia teleports an always-mounted `.rp-modal-wrap` (hidden with Alpine `x-show`, still in the DOM — `:has()` still matches). Lock only when a dialog is actually open: `body:has(.rp-modal-wrap[role="dialog"])` (contact/task `@if` wrappers) and `body:has(.rp-tools-shell[data-open="true"])`. Don't reuse `.rp-modal-wrap` inside an always-present teleport without a second, open-only selector.

## Three more traps worth knowing about

**Livewire silently breaks `@endif` glued directly to a word character**:
Livewire 3's `ExtendBlade` mechanism rewrites every top-level `@if`/`@endif`
pair (it wraps them in `<!--[if BLOCK]>`/`<!--[if ENDBLOCK]>` HTML comment
markers for DOM diffing). Its parser fails to recognize `@endif` as a
directive — leaving it as literal, uncompiled text — when the character
immediately before it in the source is a word character (letter/digit/`_`)
with **no space or newline**, e.g. `@if($x)some text@endif`. This blows up
at runtime as `syntax error, unexpected end of file, expecting "elseif" or
"else" or "endif"`, pointing at some unrelated later line (because the
compiled PHP is now missing one `endif;`), which makes it confusing to
debug — the real bug is the *first* glued `@endif` in the file, not the
line the error reports. It only manifests inside Livewire components, not
plain Blade views. `@endif` preceded by `}}`, `)`, `,`, or any other
non-word character compiles fine. Fix: always put a space (or newline)
before `@endif`/`@endforeach`/`@endunless` when they immediately follow
inline text, e.g. `@if($x)some text @endif`. If something like this ever
breaks again, dump the compiled view (`Blade::compileString(...)`) and
`php -l` it to see exactly which `@endif` stayed as literal text.

## Two more traps worth knowing about

**Sticky header cells silently losing `position: sticky`**: if a `<th>` (or
any sticky element) also has an inline `style="position:relative"` (common
when a child needs `position:absolute`, e.g. a column-resize handle), that
inline declaration wins over a non-`!important` CSS class rule setting
`position: sticky`, even if the class rule has higher specificity — inline
style always beats an external rule unless the external rule uses
`!important`. Symptom looked exactly like "only one weird square stays
pinned while scrolling, not the whole header row": in `tasks-grid.blade.php`
only the empty checkbox `<th>` (no inline `position`) actually stuck; every
other `<th>` had `style="position:relative"` (for `.tg-resize-handle`) which
silently overrode the sticky rule. Fix: `position: sticky !important` on the
class rule (sticky, like relative, still establishes a containing block for
absolutely-positioned children, so nothing else needs to change).

**Fixed-position dropdown/panel math must match the panel's actual responsive
width**: teleported panels positioned via `:style="`left:${left}px`"` computed
from `getBoundingClientRect()` (see the filter-panel pattern in
`tasks-grid.blade.php` / `tg-filter-panel.blade.php`) must clamp `left`
against the *real* rendered width, not a hardcoded desktop number. If the
panel's CSS is `width: min(600px, calc(100vw - 24px))` but the JS clamps with
a stale constant like `window.innerWidth - 620`, on a narrow phone that goes
deeply negative and shoves most of the panel off-screen. Compute the same
formula in JS (`Math.min(600, window.innerWidth - 24)`) before clamping.

## Filter-panel UX rule: no silent default filters

A grouped filter panel (pattern 4 above) is only trustworthy if the active-filter
chip strip never lies. Two failure modes to avoid, both hit in `TasksGrid`
(`/tasks2`) and fixed once — don't reintroduce them elsewhere:

1. **A default value that narrows results must still show a chip.** It's
   tempting to treat the "normal"/default filter state (e.g. status defaults
   to "active only", hiding completed/cancelled rows) as "no filter" and skip
   its chip — but the user has no way to tell "15 of 129 shown" from "15
   total, nothing hidden". Rule: a chip's visibility must be driven by
   *whether that dimension currently excludes any possible value*, not by
   *whether the property differs from its PHP default*. In `TasksGrid`,
   `status` shows a chip for every value except `'all'` (the one value that
   truly excludes nothing) — not "every value except the default `''`".
2. **"Clear filters" must reset to the maximally-permissive state, not back
   to the app's smart default.** If the default on page load already narrows
   results (sensible — e.g. hide closed tasks, hide recruitment-callback
   noise by default), clicking "Wyczyść" has to land on "show literally
   everything" (`status = 'all'`, all type checkboxes checked, etc.), not
   silently bounce back to that same narrowed default. Otherwise the escape
   hatch the user is explicitly asking for doesn't exist.

Prefer one generic multi-select/dropdown per *dimension* (type, assignee,
status) over a pile of single-purpose booleans named after one specific value
(`hideCallbacks`, `myTasksOnly`) — a checkbox list of `WorkItemType::cases()`
scales to new types for free and is self-documenting in the UI; a boolean
named after the one type someone wanted to hide first doesn't.

## Shared Blade components are the highest-leverage fix

Before styling a "one-off" element, check whether it's actually a shared
`x-ui.*` component used across several unrelated pages — fixing the component
once is far cheaper than patching N pages, and is what the user explicitly
expects when they say "this is a component, fixing it in one place should fix
the whole system." Example: `x-ui.period-nav` (prev/title/next row) is reused
in 6 places (`weekly-overview` index + planner2, `dashboard/profitability`,
`recruitment/analytics`, `time-logs/analytics` + `monthly-grid`) — it was a
bare Bootstrap grid with no background, so it looked "disconnected" from the
glass-card aesthetic everywhere it appeared. Wrapping it in `.ui-period-nav`
(glass background, border, top gradient accent bar) and adding a
`.ui-period-nav__title` class around the slot (for scoped gradient-text /
mono-date styling) fixed the look on all 6 pages from one file. Only touch a
page's own slot content (e.g. add a missing `bi-chevron-right` icon) when it's
a page-specific inconsistency, not a component-wide one.

Same idea for raw-Bootstrap badges (`bg-secondary-subtle text-secondary-emphasis`
etc.): those are Bootstrap's *light-theme* "subtle" classes and look washed out
against this app's dark cards. Always use `<x-ui.badge variant="...">` instead
— its `.badge-*` classes are already themed for the dark palette.

## Adding a read-only "preview" page for a resource that only has an editor

Pattern (see `ProcedureTemplateController::show()` / `procedures/show.blade.php`,
added because `procedure-templates` only had an editor, no lightweight preview
with run counts/details): if the resource route was registered with
`Route::resource(...)->except([...])` omitting `show`, check the `except()`
list before adding a controller `show()` method + view — you must remove
`'show'` from it (and drop `'edit'` too if there's no real `edit` view, or the
auto-generated `.../{id}/edit` route will 404/500 on the missing method). Link
to the preview from the index cards (make the title a `stretched-link` so the
whole card is clickable, keep the dropdown/footer actions clickable above it
via `position: relative; z-index: 2`) and from inside the editor's toolbar
(a "Podgląd" button) so the two views can be reached from each other.

## Performance checklist before shipping a visual change

1. Never scale a `position: absolute` background layer with page content
   height — use `position: fixed` + `contain: strict` for ambient/decorative
   layers so cost is bounded by viewport size, not scroll length.
2. Never add a `mousemove` handler that queries more than a few elements;
   throttle with `requestAnimationFrame`, cache element lists and only
   re-scan on `click`/`livewire:morphed`, not every frame.
3. Check for N+1s introduced by per-row permission/edit checks — reuse
   already-loaded models (see `TasksGrid::canEditWorkItem()`) instead of
   re-fetching by id inside a loop. Add a regression test asserting query
   count doesn't scale with row count (pattern in `WorkItemBacklogTest`).
4. Run `./vendor/bin/sail npm run build` after any `resources/css/app.css`
   or `resources/js/app.js` change — Laravel serves the built
   `public/build/manifest.json`, not source files, unless `npm run dev` (Vite
   HMR) is running.

## Verifying a change

- `./vendor/bin/sail test` (full suite) — a handful of known-flaky/pre-existing
  failures unrelated to frontend work: Pulse dashboard "Users by route", one
  `WarehouseEquipmentTest` 500 + a filesystem-permissions teardown error. If in
  doubt whether a failure is pre-existing, `git stash` your changes and rerun
  just that test to confirm before assuming you broke it.
- `./vendor/bin/sail php ./vendor/bin/pint --dirty`
- For a quick authenticated smoke test of a page, seed `UserRoleSeeder` and
  assign the `administrator` role in a throwaway Feature test (most pages are
  permission-gated), e.g.:

```php
$this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
$user = User::factory()->create();
$user->assignRole(\Spatie\Permission\Models\Role::where('name', 'administrator')->first());
$this->actingAs($user)->get('/some-page')->assertOk();
```

Delete throwaway smoke tests before finishing — they're for verification
during the session, not to be committed.
