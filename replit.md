# Moodle Stream Theme

## Project Overview

This is the **Moodle Stream Theme** — a Boost child theme for Moodle 4.5+. The theme plugin code lives in `/home/runner/workspace/` and is installed inside a full Moodle instance.

## Architecture

- **Theme Plugin**: PHP/Mustache/SCSS Moodle theme plugin (`theme_stream`)
- **Moodle Core**: Installed at `/home/runner/moodle/`
- **Theme Location**: `/home/runner/moodle/theme/stream/` (symlinked from workspace)
- **Moodle Data**: `/home/runner/moodledata/`
- **Database**: PostgreSQL at `helium:5432`, database `moodle`
- **Web Server**: PHP built-in dev server on port 5000

## Running the Application

The workflow runs `/home/runner/start-moodle.sh` which starts:
```bash
php -d max_input_vars=5000 -d upload_max_filesize=100M -d post_max_size=100M -S 0.0.0.0:5000
```

## Moodle Configuration

Key settings in `/home/runner/moodle/config.php`:
- `dbtype = pgsql` (PostgreSQL)
- `dbhost = helium`, `dbname = moodle`
- `wwwroot = https://<replit-domain>`
- `sslproxy = true` (Replit handles SSL termination)
- **No `reverseproxy`** — PHP serves directly, Replit proxies SSL

## Admin Credentials

- Username: `admin`
- Password: `Admin1234!`

## Theme Features

- Homepage hero slider (up to 5 slides)
- Social media links
- Course header images
- Frontpage widgets (categories, promobox, featured courses)
- Login page layout
- Activity navigation
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
