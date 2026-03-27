# Moodle Stream Theme — DanKa Redesign

## Project Overview

This is the **Moodle Stream Theme** — a Boost child theme for Moodle 4.5+. The workspace contains only theme plugin files. Changes are pushed to GitHub for installation on the client's external Moodle instance. A local Moodle instance exists at `/home/runner/moodle/` for preview purposes only.

**Client:** DanKa (Portuguese event staffing company). Design direction: dark-first, premium/modern, editorial typography, gold + teal accent palette.

## Architecture

- **Theme Plugin**: PHP/Mustache/SCSS Moodle theme plugin (`theme_stream`)
- **Moodle Core** (preview only): `/home/runner/moodle/`
- **Theme Location**: `/home/runner/moodle/theme/stream/` (symlinked from workspace)
- **Moodle Data**: `/home/runner/moodledata/`
- **Database**: PostgreSQL at `helium:5432`, database `moodle`

## Admin Credentials (local preview only)

- Username: `admin`
- Password: `Admin1234!`

---

## Design System

### Color Tokens

| Token | Value | Role |
|---|---|---|
| `--st-bg-base` | `#0D0F14` | Page body (deepest) |
| `--st-bg-surface` | `#13161D` | Cards, panels |
| `--st-bg-elevated` | `#1A1E27` | Modals, dropdowns |
| `--st-bg-subtle` | `#21252E` | Hover, zebra rows |
| `--st-bg-nav` | `#0A0C10` | Top navbar |
| `--st-accent` | `#DAAA00` | Gold — primary accent |
| `--st-teal` | `#298976` | Teal — secondary |
| `--st-text-primary` | `#ECEEF2` | Main text |
| `--st-text-secondary` | `#9BA3B2` | Descriptions |
| `--st-text-muted` | `#5A6173` | Captions, placeholders |

### Typography

Default font: **IBM Plex Sans** (loaded via Bunny Fonts). Fallback: Inter → system-ui → sans-serif. Fluid heading scale using `clamp()`. Headings use tight letter-spacing and heavy weights.

---

## SCSS File Structure

```
scss/
  stream.scss                    — Entry point. Dark-first Bootstrap variable overrides.
  _tokens.scss                   — CSS custom property design token system (100+ tokens).
  _dark.scss                     — Global dark mode component overrides (all Bootstrap/Moodle).
  _interface.scss                — Page-specific UI (frontpage, login, course header).
  _typography.scss               — Font loading, fluid type scale.
  _incourse.scss                 — Course index drawer + activity icons.
  _courseCompletionProgress.scss — Circular conic-gradient progress ring.
```

**SCSS compilation order in stream.scss:**
1. Bootstrap variable overrides (dark-first)
2. Moodle-injected variables (via `pre-scss` callback in `lib.php`)
3. `@import "../../boost/scss/fontawesome.scss"`
4. `@import "../../boost/scss/bootstrap.scss"`
5. `@import "../../boost/scss/moodle.scss"`
6. `@import "_tokens.scss"` — CSS custom properties
7. `@import "_typography.scss"` — Fonts
8. `@import "_dark.scss"` — Dark overrides
9. `@import "_interface.scss"` — Page-specific
10. `@import "_courseCompletionProgress.scss"`
11. `@import "_incourse.scss"`

---

## Redesign Roadmap

| Phase | Status | Focus |
|---|---|---|
| **1** | ✅ Complete | Dark design system + typography + token system |
| **2** | Planned | Front page — cinematic hero, dark widgets |
| **3** | Planned | Login page — full dark split-screen |
| **4** | Planned | Course page — Netflix-style header + layout |
| **5** | Planned | Dashboard, cards, category pages |
| **6** | Planned | JS interactions (parallax, hover rails) |

---

## Phase 1 Changes (Dark Design System)

### Files Changed

| File | Change |
|---|---|
| `scss/stream.scss` | Full dark-first Bootstrap variable overrides |
| `scss/_tokens.scss` | *(new)* CSS custom property design token system |
| `scss/_dark.scss` | *(new)* Dark mode overrides for all components |
| `scss/_interface.scss` | Dark UI, enlarged hero, token-based throughout |
| `scss/_typography.scss` | IBM Plex Sans, fluid type scale |
| `scss/_incourse.scss` | Dark course index, uses CSS variables |
| `scss/_courseCompletionProgress.scss` | Dark inner ring |
| `templates/core/full_header.mustache` | Dark cinematic gradient (not white-wash) |
| `classes/output/core_renderer.php` | Multi-weight font loading, variable font support |
| `settings.php` | IBM Plex Sans + modern fonts added to dropdown |

---

## Theme Features

- Homepage hero slider (up to 5 slides)
- Social media links footer
- Course header images with completion meter
- Frontpage widgets (categories, promobox, featured courses)
- Custom login page layout
- Activity navigation overrides
- Custom SCSS via theme settings

## Development Workflow

When modifying theme files in `/home/runner/workspace/`:
1. Files are mirrored at `/home/runner/moodle/theme/stream/`
2. Clear Moodle caches: `rm -rf /home/runner/moodledata/cache /home/runner/moodledata/localcache`
3. Or run: `php -d max_input_vars=5000 /home/runner/moodle/admin/cli/purge_caches.php`

## Dependencies

- PHP 8.2 with extensions: pgsql, gd, curl, xml, mbstring, zip, intl, soap
- PostgreSQL 16
- Moodle 4.5 (2024100700) at `/home/runner/moodle/`
