---
name: frontend-design
description: >-
  ChronoLogic design system and responsive-UI conventions for this Laravel +
  Livewire + Bootstrap app. Use whenever styling, theming, or improving
  responsiveness/density of any Blade view, Livewire component, or the
  x-ui.* component library — not generic frontend advice, this is specific
  to this codebase's established look (dark ambient, Space Grotesk/JetBrains
  Mono, blue→purple gradient) and its known performance traps.
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
- `--bg-body: #070a13`, `--bg-card: rgba(30, 41, 59, 0.5)`,
  `--text-main: #f1f5f9`, `--text-muted: #94a3b8`, `--glass-border: rgba(255,255,255,0.1)`.
- Fonts: **Space Grotesk** is the global body/display font (loaded via
  `<link>` in `layouts/app.blade.php` + `layouts/guest.blade.php`, applied via
  `* { font-family: 'Space Grotesk', 'Inter', ... }`). **JetBrains Mono** is
  for data/numbers/mono UI via the `.font-mono` / `.tg-mono` utility classes
  (includes `font-variant-numeric: tabular-nums`).

Where headers already get the gradient automatically: any `<h1>`–`<h6>`
inside a `<header>` element gets `background: linear-gradient(135deg, var(--primary), var(--accent))`
text-clip styling for free (`app.css` "header h1..h6" rule) — don't re-add
gradient text manually on page titles, it's already global.

## Ambient background (global, already applied everywhere)

Three layers, all in `app.css`, none of them animate anything expensive:

1. `body::before` / `body::after` — fixed, `z-index: -1`, `contain: strict`
   grid + grain texture, viewport-sized (not content-height-sized).
2. `.app-content-wrapper::before` — the wrapper's own crisp grid pattern.
3. `.cl-cursor-glow` — one JS-injected div (`resources/js/app.js`), position
   driven by a single `requestAnimationFrame`-throttled `mousemove` listener.

**Known trap — don't repeat it**: `.app-content-wrapper::before` also does
`backdrop-filter: blur(12px)` to blur whatever is *behind* it (needed so
fixed-position modals don't get trapped by it as a containing block). Since
`.app-content-wrapper` covers ~the whole viewport on every page, anything
relying on being visible *behind* a blurred/translucent container will look
like it "disappeared" globally. Fix: draw texture/pattern *on* the blurring
element's own `background-image` (painted on top of its own blur, stays
crisp), not on an ancestor further back.

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

**4. Collapsible filter panel behind one button** (when a toolbar has more
than ~3 filter controls, they must NOT all stack full-width on mobile by
default). Alpine `x-data="{ filtersOpen: window.innerWidth >= 768 }"` on the
wrapper, `x-show="filtersOpen"` on the filter grid, a `d-md-none` toggle
button showing an active-filter-count badge. See `employees-table.blade.php`
and the more elaborate grouped version in `tg-filter-panel.blade.php` /
`/recruitment-processes` (grouped sections instead of a flat wall of toggles
— prefer this over one-checkbox-per-concept when there are many filter
*types*, not just many filter *values*).

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
