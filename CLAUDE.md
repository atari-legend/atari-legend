# Atari Legend

Laravel 11 website for the Atari ST community - a comprehensive database of games, menu disks, magazines, and music.

## Tech Stack

- **Backend:** Laravel 11, PHP 8.4, MySQL
- **Frontend:** Vite, Bootstrap 5, Livewire 3
- **Testing:** PHPUnit 11 with SQLite in-memory; Playwright for end-to-end

## Project Structure

```
app/
├── Console/Commands/     # Artisan commands
├── Helpers/              # Utility classes (GameHelper, DumpHelper, BBCode, etc.)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/        # Admin panel controllers (auth: 'verified' + 'admin')
│   │   └── Ajax/         # AJAX endpoints for autocomplete/search
│   ├── Livewire/         # Livewire components (admin tables)
│   └── Middleware/
├── Models/               # Eloquent models
└── Rules/                # Custom validation (Slug, YoutubeUrl)
config/
├── al.php                # App-specific config (legacy site URL, Stonish, HxCFE)
routes/
├── web.php               # Public routes
├── admin.php             # Admin panel routes
storage/public/           # Public file storage (symlinked to public/storage)
```

## Database

200+ tables organized into:

- **Games:** `game`, `game_release`, `game_aka`, `game_genre_cross`, `game_individual`
- **Media:** `dump`, `media`, `media_scan`, `screenshot_main`, `game_release_scan`
- **Music:** `sndh`, `sndh_archive`, `game_music`
- **Menus:** `menu_sets`, `menus`, `menu_disks`, `menu_disk_dumps`, `menu_disk_contents`
- **Content:** `news`, `review_main`, `interview_main`, `article_main`
- **Reference:** `individuals`, `pub_dev`, `crew`, `genre`, `engine`
- **Users:** `users`, `game_vote`, `comments`

### Key Relationships

- Game → Releases → Media → Dumps
- Game → Individuals (with roles)
- MenuSet → Menus → MenuDisks → MenuDiskDumps
- Individual ↔ Nicknames (self-referential)

## File Storage

All in `storage/public/` (accessible via `Storage::disk('public')`):

| Path | Content |
|------|---------|
| `images/game_screenshots/` | Game screenshots |
| `images/game_fact_screenshots/` | Game fact screenshots |
| `images/article_screenshots/` | Article images |
| `images/interview_screenshots/` | Interview photos |
| `images/individual_screenshots/` | Individual avatars (keyed on `ind_id`) |
| `images/menu_screenshots/` | Menu disk screenshots |
| `images/magazine_scans/` | Magazine covers |
| `images/game_release_scans/` | Release box scans |
| `images/spotlight_screens/` | Spotlight images (keyed on `screenshot_id`) |
| `images/website_images/` | Link screenshots |
| `images/avatars/` | User avatars |
| `sndh/` | SNDH music files |
| `zips/menus/` | Menu dump ZIPs (`{menu_disk_dump_id}.zip`) |

## Artisan Commands

```bash
artisan menus:check-dumps      # Validate menu dump files exist
artisan links:check            # Check external links for dead URLs
artisan sndh:fetch             # Download SNDH archives
artisan sndh:generate-json     # Generate SNDH JSON index
artisan users:delete-unverified # Clean up unverified accounts
artisan filepond:discard       # Clean abandoned uploads
```

## Environment Variables

```env
AL_LEGACY_BASE_URL=       # Legacy site URL (database dumps download)
AL_HXCFE=                 # HxC Floppy Emulator path
STONISH_ROOT=             # Stonish menu data path
MATOMO_ID=                # Analytics tracking ID
CAPTCHA_SECRET=           # hCaptcha secret
CAPTCHA_SITEKEY=          # hCaptcha site key
```

## Build & Deploy

The project is using Docker Compose. Use `docker compose run --rm` to execute
tools such as `npm`, `artisan`.

```bash
npm run dev      # Development with hot reload
npm run build    # Production build
php artisan test # Run PHPUnit tests
```

## Tests

- `tests/Unit`, `tests/Feature` - PHPUnit, SQLite in-memory. `tests/Feature/Admin`
  extends `AdminTestCase`, which signs in an admin and offers `assertChangelog()`.
- `tests/e2e` - Playwright, organised as `public/` and `admin/`, one spec per
  section of the site. See `tests/e2e/README.md` for the conventions, how to run
  it, and what it does not cover yet.

**CI/CD:** GitHub Actions deploys `development` → dev.atarilegend.com, `master` → www.atarilegend.com

## Admin Architecture

The admin panel lives in this repo:
- Admin routes (`routes/admin.php`), behind the `verified` and `admin` middleware
- Livewire tables for data management

A legacy admin panel (CPANEL) still exists as a separate application sharing the
same database and data directory, but this app no longer links to it. Some
content was written by it and is normalised on read (see the `<br />` handling in
the News, Articles and Reviews edit forms).

### Admin Patterns

**Livewire Tables** (`app/Livewire/Admin/`):
- Extend `Rappasoft\LaravelLivewireTables\DataTableComponent`
- Define `configure()`, `columns()`, `builder()`, `filters()`
- Use `LinkColumn` for clickable rows, `BooleanColumn` for flags
- Render delete buttons via partial views (`datatable_actions.blade.php`)

**Content Sections** (Reviews, Articles, Interviews):
Each follows a consistent pattern:
- `app/Http/Controllers/Admin/{Section}/` - Resource controller with CRUD
- `app/Livewire/Admin/{Section}Table.php` - Livewire datatable
- `resources/views/admin/{section}/{section}/` - Views:
  - `index.blade.php` - Page wrapper
  - `card_list.blade.php` - Livewire table + Add button
  - `edit.blade.php` - Edit page wrapper
  - `card_edit.blade.php` - Form fields
  - `card_images.blade.php` - Image upload/management (Articles, Interviews)
  - `datatable_actions.blade.php` - Delete button

**Image Uploads** (Articles, Interviews):
- Separate routes: `POST .../image`, `PUT .../image`, `DELETE .../image/{image}`
- Controller methods: `storeImage()`, `updateImage()`, `destroyImage()`
- Images stored in `Screenshot` model with pivot tables (`screenshot_article`, `screenshot_interview`)
- Descriptions stored in comment tables (`article_comments`, `interview_comments`)

**Save Buttons**:
- Green "Save" with `name="stay" value="true"` - stays on edit screen
- Blue "Save & Close" - returns to list
- Controller checks `$request->stay` to determine redirect

**Changelog Tracking**:
Use `ChangelogHelper::insert()` for all INSERT/UPDATE/DELETE operations:
```php
ChangelogHelper::insert([
    'action'           => Changelog::INSERT, // or UPDATE, DELETE
    'section'          => 'Interviews',
    'section_id'       => $model->getKey(),
    'section_name'     => $model->name,
    'sub_section'      => 'Screenshots',
    'sub_section_id'   => $screenshot->getKey(),
    'sub_section_name' => $screenshot->file,
]);
```

## Key Controllers

**Frontend:**
- `GameController`, `GameSearchController` - Game browsing
- `GameReleaseController` - Release details
- `MenuSetController` - Menu disk browsing, EPUB export
- `GameMusicController` - SNDH playback (via ym2149-wasm)
- `ReviewController`, `InterviewController`, `ArticleController` - Content

**Admin:**
- `Admin/Games/` - Game, release, screenshot, music management
- `Admin/Menus/` - Menu disk and content management
- `Admin/Reviews/`, `Admin/Interviews/`, `Admin/Articles/`, `Admin/News/` - Content management

## External Integrations

- **hCaptcha** - Form spam protection
- **YouTube** - Game video embeds
- **Matomo** - Analytics (optional)
- **SNDH/ym2149-wasm** - Atari ST music playback in browser

## Supported Dump Formats

STX, MSA, RAW, SCP, ST (Atari ST floppy disk images)

## BBCode

The site uses BBCode for formatting content. Key tags:

**Interviews:**
- `[hotspotUrl=#1]Chapter title[/hotspotUrl]` - Chapter links (in Chapters field)
- `[hotspot=1]Question text[/hotspot]` - Section anchors (in Interview Text field)

**General:**
- Standard BBCode: `[b]`, `[i]`, `[url]`, `[img]`, etc.
- Processed by `Helper::bbCode()` in `app/Helpers/Helper.php`
