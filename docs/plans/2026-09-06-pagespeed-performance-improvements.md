# PageSpeed Insights audit of www.atarilegend.com

*2026-09-06*

Google PageSpeed Insights (Lighthouse 13.4.1) was run against the production
site — homepage, a game detail page (`/games/super-stario-land`), a menu set
page (`/menusets/1`), and a review page (`/reviews/133`) — on both mobile and
desktop, all four categories. This is analysis only; nothing here has been
implemented yet.

Two things worth knowing about the data before the findings: field data (real
Chrome-User-Experience-Report numbers) came back identical across all four
pages, which means Chrome doesn't have enough per-URL traffic to report
page-level field data for this site and is falling back to origin-level
numbers for every page tested — treat the "field data" figures below as
site-wide, not page-specific. And time-to-first-byte reads "Average" rather
than "Fast" in that field data; lab tests here show `server-response-time` and
`network-rtt`/`network-server-latency` all scoring perfectly, and the deploy
script (`.github/workflows/deploy.sh`) already runs `artisan optimize` (config,
route and view caching) on every deploy, so this isn't a caching gap in the
app — it reads as ordinary network latency to a single shared-hosting origin,
not something a code change here fixes.

## Scores

| Page | Perf (mobile / desktop) | Accessibility (m/d) | Best Practices (m/d) | SEO (m/d) |
|---|---|---|---|---|
| Home | 77 / 87 | 93 / 90 | 96 / 100 | 100 / 100 |
| Game detail | 92 / **64** | 87 / 86 | 92 / 92 | 100 / 100 |
| Menu set | 82 / 98 | 86 / 85 | 96 / 100 | 100 / 100 |
| Review | 96 / 92 | 92 / 87 | 96 / 100 | 100 / 100 |

The game detail page's desktop run is the outlier (**64**), and it isolates
cleanly to one cause (finding 1 below), not a general desktop problem — its
mobile score is a healthy 92.

## Findings, prioritized by impact vs. effort

### 1. Eager YouTube embeds load ~850 KiB of JS unconditionally — `resources/views/games/card_videos.blade.php:10-14`

This is what drives the game page's desktop performance score down to 64: total
blocking time 970ms, `bootup-time` and `mainthread-work-breakdown` both score
**0** (2.3s / 5.2s of JS execution), plus a `forced-reflow-insight` failure
(500ms+ of synchronous layout thrashing during load). The network log for that
run shows why:

```
472 KiB  Script  youtube-nocookie.com/s/player/f572e43c/player_embed_es6.vflset/...
220 KiB  Script  youtube-nocookie.com/s/_/ytembeds/_/js/k=ytembeds.base.en_US...
157 KiB  Script  youtube-nocookie.com/s/_/ytembeds/_/js/k=ytembeds.base.en_US...
 58 KiB  Style   youtube-nocookie.com/s/player/f572e43c/www-player.css
```

Every video on a game page renders as a live `<iframe src="youtube-nocookie.com/embed/...">`
immediately on page load, whether or not the visitor ever plays it. The fix is
the standard YouTube facade pattern: render a static thumbnail + play button by
default, and only inject the real iframe on click. That also fixes a second,
unrelated finding for free — PSI's `frame-title` accessibility check fails on
every game page because the iframe has no `title` attribute; the facade's
static placeholder sidesteps it, or add the attribute regardless.

**Impact:** likely +20-30 performance points on every page with an embedded
video. **Effort:** small — one blade file plus a small facade script.

### 2. `/storage/*` assets are served with zero cache lifetime — `public/.htaccess`

Confirmed on every single page tested (the `cache-insight` audit fails on all
8 runs, 33-346 KiB wasted per page). `public/storage` is a symlink straight to
`storage/app/public`, served by Apache directly — it never passes through a
Laravel controller, so the `Cache-Control: max-age=31536000` header that
`GameResourcesController` and `GameReleaseResourcesController` already set on
their own routes never applies here. Example from the data:

```
storage/images/menu_screenshots/1869.png   cacheLifetimeMs: 0   28,312 bytes
storage/images/menu_screenshots/78.png     cacheLifetimeMs: 0   22,887 bytes
```

`public/.htaccess-build` already solves exactly this problem for `/build/`:

```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresDefault "access plus 1 year"
</IfModule>
```

The same block, scoped to the storage path, in `public/.htaccess` closes this
gap for every menu screenshot, magazine scan, and other file served straight
out of storage.

**Impact:** removes a repeat-visit cost present on literally every page.
**Effort:** trivial — a few lines in one file.

### 3. FontAwesome ships without `font-display: swap` — `resources/sass/app.scss:11-14`, `resources/sass/admin/admin.scss:5-8`

Fails on all 8 runs (`font-display-insight`), up to 2,760ms wasted (game page,
mobile). Both stylesheets import FontAwesome's pre-built CSS:

```scss
@import '@fortawesome/fontawesome-free/css/fontawesome';
@import '@fortawesome/fontawesome-free/css/regular';
@import '@fortawesome/fontawesome-free/css/solid';
@import '@fortawesome/fontawesome-free/css/brands';
```

That package's compiled CSS hardcodes `font-display: auto` in its `@font-face`
rules. Switching the imports to FontAwesome's SCSS source instead
(`@fortawesome/fontawesome-free/scss/fontawesome`, etc.) exposes a
`$fa-font-display` variable that can be set to `swap` before the import; the
alternative, if switching the import path is unwanted, is a manual override
rule targeting the four `@font-face` blocks after them.

**Impact:** eliminates blocked text rendering while icon fonts load, worst on
slow connections. **Effort:** small.

### 4. Images ship with no intrinsic size and no lazy-loading — sitewide

`grep` confirms 74 of the 75 blade files containing `<img>` set neither
`width`/`height` nor `loading="lazy"` (the one exception is
`resources/components/cards/altv-feed.blade.php`). This is exactly what PSI's
`unsized-images` audit flags — it fails identically (score 0.5) on every one of
the 8 runs — and it's the leading contributor to the desktop CLS scores (0.178
on the homepage, the worst of the four pages). Most of these images use
Bootstrap's `img-fluid`/`w-100` classes for responsive display sizing; adding
`width`/`height` attributes doesn't fight that — the browser uses them only to
reserve the correct aspect ratio before the image loads, and CSS still governs
the rendered size.

In practice this is a smaller job than 74 files suggests: most of those hits
sit in a handful of shared components (`components/cards/*.blade.php`,
`menus/components/menu.blade.php`, `games/releases/card_game.blade.php`,
`magazines/card_issues.blade.php`) that many pages reuse, so fixing the shared
component fixes every page that renders it.

**How this coexists with responsive/mobile display.** Since ~2020, every
evergreen browser's default stylesheet includes
`img, video { aspect-ratio: attr(width) / attr(height) auto; }` — the `width`/
`height` attributes only ever set the *ratio* the browser reserves before the
image loads, not a fixed display size. Bootstrap's `.img-fluid` is
`max-width: 100%; height: auto;`, so the two compose cleanly: at any
breakpoint the box's width shrinks to fit its column and the browser derives
the height from the reserved ratio, same as it always would have — a
`width="1024" height="576"` image still renders at, say, 340×191 in a narrow
mobile column, with zero shift, because the ratio (not the pixel value) is
what's reserved.

**Where the actual dimensions come from.** Checked every migration in this
repo — no table stores image `width`/`height` anywhere; `screenshots` only has
`imgext`. Three tiers, cheapest first:

1. **Store real dimensions at upload time.** `Intervention\Image` is already a
   dependency, already used in this exact shape for the WebP pipeline
   (`GameReleaseResourcesController`), so reading them is a one-liner
   (`Image::make($path)->width()` / `->height()`). Add nullable `width`/
   `height` columns via one migration, populate them in the upload controllers
   (`GameScreenshotsController`, `MenuDisksController`, etc.) going forward,
   and backfill existing rows with a one-off Artisan command that reads each
   stored file's dimensions once.
2. **Where the display box's ratio is CSS-enforced regardless of the source
   file** (e.g. an avatar or a thumbnail cropped with `object-fit: cover`),
   skip chasing the real file dimensions — declare the *container's* ratio
   instead (hardcoded `width`/`height`, or CSS `aspect-ratio` directly on the
   element). The reserved space only has to match what's actually rendered.
3. **Until dimensions are backfilled**, a reasonable default pair (matching
   the typical screenshot shape) beats reserving no space at all — imprecise
   for outliers, but still kills most of the shift.

This isn't purely a Lighthouse-metric fix, either: `resources/js/menus.js`
initializes `isotope-layout` on the menu-set masonry grid without the
`imagesLoaded` plugin, so today it lays out cards from whatever height the DOM
has *before* images finish loading, and only self-corrects if a user happens
to trigger a re-`arrange()` (e.g. clicking a filter). Reserving the aspect
ratio up front fixes a real, visible masonry glitch there, not just the score.

**One caveat against `loading="lazy"` sitewide:** apply it below the fold
only. Finding 7 below shows the *first* visible image on a page can be the LCP
element itself — lazy-loading that one would make LCP worse, not better. Rule:
`loading="lazy"` below the fold, no `loading` attribute (or
`fetchpriority="high"`) on whatever renders first.

**Impact:** removes the sitewide CLS/unsized-images finding, and lets offscreen
images actually defer. **Effort:** medium — mechanical, but touches many files.

### 5. The entire CSS bundle blocks first render, on every page — `resources/sass/app.scss` via `resources/views/layouts/app.blade.php`

`render-blocking-insight` fails on 7 of 8 runs, 150ms-2,530ms wasted (worse on
the simulated mobile connection: 1,202ms home, 2,530ms menu set). One
stylesheet — `app-*.css`, ~78.6 KiB — bundles Bootstrap 5.2, all three
FontAwesome icon families, and `flag-icons`, and is loaded via
`@vite(['resources/sass/app.scss'])` in `<head>` on every front-end page
regardless of what that page actually uses. The JS side already has per-page
Vite entry points (`menus.js`, `charts.js`, `tabulator.js`, `game/music.js`);
the CSS side has no equivalent split. Worth an audit of which pages actually
need `flag-icons` and which FontAwesome icon families, to move what isn't
universal into page-specific entries.

**Impact:** removes the single largest, most consistent render-blocking
resource on the site. **Effort:** medium-to-large — requires auditing per-page
CSS usage before splitting anything.

### 6. Game screenshots have no resize/WebP pipeline — `app/Http/Controllers/GameResourcesController.php`

```php
return response()->file(Storage::path('public/' . $screenshot->getPath('game')), [
    'Cache-Control' => 'max-age=31536000',
]);
```

This streams the original stored file untouched. PSI's `image-delivery-insight`
catches it directly — e.g.
`/games/the-great-giana-sisters/screenshot-3798.png` flagged for both format
("Using a modern image format... could improve this image's download size")
and oversizing ("this image file is larger than it needs to be... for its
displayed dimensions"). The pattern to reuse already exists one controller
over, in `GameReleaseResourcesController`, which resizes and streams WebP via
`Intervention\Image`. Applying the same pattern here (and to magazine cover
images, which have no resize route at all) is the fix.

**Impact:** direct KiB savings on every page with game screenshots (i.e. most
of the site). **Effort:** medium — follow an existing pattern, but it's a
behavior change to a public route.

### 7. The LCP element isn't discoverable until CSS parses — layout header

`lcp-discovery-insight` fails on 6 of 8 runs. On most pages the LCP element is
the header's CSS `background-image` (`css_top_bg-*.webp`, applied inside the
same render-blocking stylesheet from finding 5) — the browser can't start
fetching it until it has parsed that CSS, and there's no `fetchpriority=high`
hint on it either way. Once finding 5 is addressed this may resolve on its own;
if not, a `<link rel="preload" as="image">` for this specific asset in
`resources/views/layouts/app.blade.php` addresses it directly.

**Impact:** improves LCP timing on nearly every page. **Effort:** small, but
best done after / together with finding 5.

## Not actionable from this repository

- **Brotli / server-level compression and caching.** Production runs on
  external shared/VPS hosting reached via rsync
  (`.github/workflows/build-and-deploy.yml`); nginx/Apache/PHP-FPM
  configuration lives on that host, not in this repository. `public/.htaccess`
  already enables gzip via `mod_deflate` — that's the ceiling for what's
  controllable from here without hosting-provider access.
- **Origin-level time-to-first-byte.** Reads "Average" in field data; lab
  metrics and the deploy script's `artisan optimize` step rule out an
  application-level cause. This is ordinary network distance to a single
  origin server, not a code fix.
- **A ZoomInfo tracking pixel** (`ws.zoominfo.com/pixel/collect`) appeared in
  the menu-set page's network dependency chain during one test run — as a
  phantom child of the navigation request, 0 bytes transferred, absent from
  that same run's own completed-`network-requests` table. Follow-up ruled out
  a real tracker: it didn't reproduce across 3 fresh re-runs of the same URL,
  never appeared on any of the other 7 runs, and doesn't appear in the live
  page's HTML (`curl`'d `/menusets/1` directly) or anywhere in this codebase.
  Read as noise in Google's PSI test infrastructure for that one run, not
  something the site serves.

## Accessibility findings (came back free, not requested but worth noting)

Several checks fail identically across nearly every page — sitewide layout
issues rather than page-specific content problems:

- `landmark-one-main` — no `<main>` landmark in the base layout (7 of 8 runs).
- `color-contrast` — insufficient contrast somewhere on nearly every page (7
  of 8 runs); needs a manual pass to identify which elements.
- `target-size` — touch targets too small/close together on mobile (4 of 8
  runs).
- `link-name` — links with no discernible accessible name, on the menu set
  pages specifically (2 of 8 runs).

These weren't part of the performance investigation above and would need their
own pass to scope properly, but the `<main>` landmark fix in particular is a
same-effort-as-finding-2 change to the base layout.

## Suggested order of work

1. Findings 1 and 2 (video facade, storage cache headers) — smallest effort,
   largest and most certain wins, no risk to layout.
2. Finding 3 (font-display) — small, independent, no risk.
3. Finding 4 (image dimensions) — mechanical but touches many files; do it as
   its own pass through the shared card/component partials.
4. Findings 5 and 7 together (CSS split + LCP preload) — needs a real
   per-page CSS usage audit first; higher effort, do after the quick wins land
   so the audit is measuring the post-fix baseline.
5. Finding 6 (screenshot WebP pipeline) — medium effort, follows an existing
   pattern exactly; no urgency, do when convenient.

## Raw data

The 8 raw PSI API responses (JSON) were saved to a scratch directory for this
session and were not committed to the repository — none of the figures above
depend on them existing afterward; re-running the same 4 URLs through the
PageSpeed Insights API (`https://www.googleapis.com/pagespeedonline/v5/runPagespeed`)
reproduces them.
