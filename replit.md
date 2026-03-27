# Moodle Atipico Theme — DanKa Redesign

## Project Overview

This is the **Moodle Atipico Theme** — a Boost child theme for Moodle 4.5+. The workspace contains only theme plugin files. Changes are pushed to GitHub for installation on the client's external Moodle instance. A local Moodle instance exists at `/home/runner/moodle/` for preview purposes only.

**Client:** DanKa (Portuguese event staffing company). Design direction: dark-first, premium/modern, editorial typography, gold (#DAAA00) + teal (#298976) accent palette.

## Architecture

- **Theme Plugin**: PHP/Mustache/SCSS Moodle theme plugin (`theme_atipico`)
- **Moodle Core** (preview only): `/home/runner/moodle/`
- **Theme Location**: `/home/runner/moodle/theme/atipico/` (symlinked from workspace)
- **Moodle Data**: `/home/runner/moodledata/`
- **Database**: PostgreSQL at `helium:5432`, database `moodle`

## Admin Credentials (local preview only)

- Username: `admin`
- Password: `Admin1234!`

---

## Design System

### SCSS Primitive Variables (`$at-*`)

Defined at the top of `scss/stream.scss` as Layer 0 single source of truth:

- Dark scale: `$at-dark-950` → `$at-dark-050` (12 steps)
- Gold: `$at-gold` (#DAAA00), `$at-gold-bright`, `$at-gold-dim`
- Teal: `$at-teal` (#298976), `$at-teal-bright`
- Semantic: `$at-success`, `$at-danger`, `$at-warning`, `$at-info`, `$at-cyan`

### CSS Custom Properties (`--at-*`)

Defined in `scss/_tokens.scss` using `#{$at-*}` SCSS interpolation — 109 tokens across:
- Background surfaces: `--at-bg-base/surface/elevated/subtle/nav`
- Text: `--at-text-primary/secondary/muted/inverse`
- Accent: `--at-accent`, `--at-accent-hover`, `--at-accent-subtle`, `--at-accent-glow`
- Borders: `--at-border-subtle`, `--at-border`, `--at-border-strong`, `--at-border-accent`
- Shadows: `--at-shadow-sm/md/lg/xl`, `--at-shadow-accent`
- Font sizes: `--at-fs-xs/sm/base/lg/xl/2xl/3xl/4xl/5xl` (prefix `fs` to avoid collision with text tokens)
- Spacing: `--at-space-1` → `--at-space-24`
- Z-index: `--at-z-base/raised/drawer/sticky/overlay/modal/toast`
- Motion: `--at-duration-fast/base/slow`, `--at-ease`
- Component: `--at-card-*`, `--at-input-*`, `--at-badge-*`

### Typography

Default font: **IBM Plex Sans** (loaded via Bunny Fonts). Fallback: Inter → system-ui → sans-serif. Fluid heading scale using `clamp()`.

---

## SCSS File Structure

```
scss/
  stream.scss                    — Entry point. $at-* primitives + dark-first Bootstrap overrides.
  _tokens.scss                   — 109 CSS custom properties (all rgba derived from $at-* primitives).
  _dark.scss                     — Global dark mode component overrides (Bootstrap/Moodle).
  _interface.scss                — Page-specific UI: frontpage, login, course header, nav.
  _typography.scss               — Font loading, fluid type scale.
  _incourse.scss                 — Course index drawer + activity icons.
  _courseCompletionProgress.scss — Circular conic-gradient progress ring.
```

**SCSS compilation order in stream.scss:**
1. `$at-*` primitives (Layer 0) + Bootstrap variable overrides (Layer 1)
2. Moodle-injected variables via `pre-scss` callback in `lib.php`
3. `@import "../../boost/scss/fontawesome.scss"`
4. `@import "../../boost/scss/bootstrap.scss"`
5. `@import "../../boost/scss/moodle.scss"`
6. `@import "_tokens.scss"` — CSS custom properties (Layer 2)
7. `@import "_typography.scss"` — Fonts
8. `@import "_dark.scss"` — Dark overrides
9. `@import "_interface.scss"` — Page-specific
10. `@import "_courseCompletionProgress.scss"`
11. `@import "_incourse.scss"`

---

## Frontpage Architecture

### Sections (in order, all optional via admin settings)

1. **Hero** (`at-section--hero`) — Full-viewport Bootstrap 5 carousel
   - Template: `templates/partials/main_slider.mustache`
   - Settings: `slidestotal` (1–5), per-slide: title, motto, link, button, image, overlay opacity
   - PHP context: `layout/frontpage.php` — `$data['slides'][]`, `$data['hasmultipleslides']`
   - CSS: `.at-hero` in `scss/_interface.scss`

2. **Categories** (`at-section--categories`) — CSS Grid image cards
   - Template: `templates/partials/widget_course_categories.mustache`
   - Settings: `catwidget` checkbox, `choosencats`, `catwidgetcolumns` (deprecated), `catwidgetimage`
   - CSS: `.at-cat-grid`, `.at-cat-card`

3. **Promo Box** (`at-section--promo`) — Full-bleed split-screen CTA
   - Template: `templates/partials/widget_promobox.mustache`
   - Settings: `homepagepromoboxwidget` checkbox, title, HTML text, button, URL, image
   - CSS: `.at-promo`

4. **Featured Courses** (`at-section--courses`) — CSS Grid course cards
   - Template: `templates/partials/widget_featured_courses.mustache`
   - Settings: `featuredcourseswidget` checkbox, max count, filters, show rating/date
   - CSS: `.at-course-grid`, `.at-course-card`

### Key Moodle Variables Injected Before SCSS Compilation (lib.php)

`$primary`, `$secondary`, `$sitefont`, `$isticky`, `$footercolor`, `$slideropacity0`–`$slideropacity4`

---

## Redesign Roadmap

| Phase | Status | Focus |
|---|---|---|
| **1** | ✅ Complete | Dark design system + typography + 109-token system |
| **2** | ✅ Complete | Front page — cinematic hero (BS5), premium dark widgets |
| **3** | ✅ Complete | Course page — cinematic hero, card grid, editorial sections |
| **4** | Planned | Login page — full dark split-screen |
| **5** | Planned | Dashboard, cards, category pages |
| **6** | Planned | JS interactions (parallax, hover rails) |

---

## Phase 3 Changes (Course Page)

| File | Change |
|---|---|
| `templates/core/full_header.mustache` | Full rewrite: overlay layout with `.course-head__top` (admin controls + completion ring) and `.course-head__bottom` (title + frosted-glass breadcrumb) |
| `templates/core_course/activity_navigation.mustache` | Rewrite: `.at-activity-nav` flex layout replacing `core/columns-1to1to1` — gold hover accent |
| `scss/_interface.scss` | Replaced fixed-height course-head with `min-height: 40/55vh` viewport-relative hero; cinematic flex layout; in-hero frosted-glass breadcrumb; `rgba($at-gold, 0.05)` shimmer via `::after`; `.page-context-header` stripped of card-box when inside hero |
| `scss/_dark.scss` | `.course-content .activity-list` CSS Grid (2-col on lg+); `.activity` left-accent-bar hover; section headers redesigned as gold-bordered editorial chapter markers; `.at-activity-nav` styled prev/next buttons |
| `scss/_courseCompletionProgress.scss` | All 101 `#298976` hardcoded hex replaced with `$at-teal`; ring resized from 42×32 to 60×46 px; `circle.per-50` typo fixed (was missing leading dot) |

### Course Page Architecture Notes

- **Hero**: `templates/core/full_header.mustache` renders `.course-head` with the course image as background via inline style. The CSS hero uses `display: flex; flex-direction: column; justify-content: space-between` to push controls to top and title/breadcrumb to bottom.
- **Activity grid**: `.course-content .activity-list { display: grid }` — applies to Moodle 4.x Topics format. Falls back gracefully to list view if selector doesn't match.
- **Section markers**: `.sectionname` gets gold left-border (`border-left: 3px solid var(--at-accent)`) replacing the default grey bottom border.
- **Token rule**: all `rgba()` in brand colors use `rgba($at-gold/teal, x)` form — never raw hex.

---

## Phase 2 Changes (Frontpage)

| File | Change |
|---|---|
| `templates/frontpage.mustache` | Semantic `<section class="at-section at-section--*">` wrappers (replaces `.widgets.container.mt-5`) |
| `templates/partials/main_slider.mustache` | Full rewrite: Bootstrap 5 carousel (`data-bs-*`, `<button>` indicators, `hasmultipleslides` flag) |
| `templates/partials/widget_course_categories.mustache` | `.at-section__header` + `.at-cat-grid` CSS Grid (replaces Bootstrap columns + `.card.shadow.mb-5`) |
| `templates/partials/widget_promobox.mustache` | Full-bleed `.at-promo` grid split-screen (replaces contained Bootstrap row) |
| `templates/partials/widget_featured_courses.mustache` | `.at-course-grid` CSS Grid + `.at-course-card` flex cards (replaces `col-md-4`) |
| `layout/frontpage.php` | Added `$data['hasmultipleslides']` flag to avoid per-item repetition of controls in Mustache |
| `scss/_interface.scss` | Full rewrite of `#page.homepage` block (305→ end): `.at-hero`, `.at-section`, `.at-cat-card`, `.at-promo`, `.at-course-card` |

---

## Phase 1 Changes (Dark Design System)

| File | Change |
|---|---|
| `scss/stream.scss` | 22 `$at-*` SCSS primitives + full dark-first Bootstrap variable overrides |
| `scss/_tokens.scss` | 109 CSS custom properties, all rgba derived from primitives |
| `scss/_dark.scss` | Dark mode overrides for all Bootstrap/Moodle components |
| `scss/_interface.scss` | Dark UI + token-based throughout |
| `scss/_typography.scss` | IBM Plex Sans, fluid type scale |
| `scss/_incourse.scss` | Dark course index, CSS variables |
| `scss/_courseCompletionProgress.scss` | Dark inner ring |

---

## Theme Features

- Homepage hero slider (up to 5 slides, Bootstrap 5)
- Social media links footer
- Course header images with completion meter
- Frontpage widgets: categories (CSS Grid), promo split-screen, featured courses (CSS Grid)
- Custom login page layout
- Activity navigation overrides
- Custom SCSS via theme settings

## Development Workflow

When modifying theme files in `/home/runner/workspace/`:
1. Files are mirrored at `/home/runner/moodle/theme/atipico/`
2. Clear Moodle caches: `rm -rf /home/runner/moodledata/cache /home/runner/moodledata/localcache`
3. Or run: `php -d max_input_vars=5000 /home/runner/moodle/admin/cli/purge_caches.php`

## Dependencies

- PHP 8.2 with extensions: pgsql, gd, curl, xml, mbstring, zip, intl, soap
- PostgreSQL 16
- Moodle 4.5 (2024100700) at `/home/runner/moodle/`
