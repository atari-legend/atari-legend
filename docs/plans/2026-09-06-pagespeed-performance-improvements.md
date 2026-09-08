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
stylesheet — `app-*.css`, ~78.6 KiB transferred / 65.6 KiB gzip — bundles
Bootstrap 5.2, all three FontAwesome icon families, and `flag-icons`, and is
loaded via `@vite(['resources/sass/app.scss'])` in `<head>` on every
front-end page regardless of what that page actually uses. The JS side
already has per-page Vite entry points (`menus.js`, `charts.js`,
`tabulator.js`, `game/music.js`); the CSS side has no equivalent split.

**Investigated: per-page splitting by FontAwesome family/flag-icons doesn't
pay off.** Compiling each piece of `app.scss` in isolation to measure its
real contribution:

| Piece | gzip size | Share |
|---|---|---|
| Bootstrap | 27.5 KiB | 42% |
| FontAwesome (all 3 families) | 22.9 KiB | 35% |
| flag-icons | 2.3 KiB | 4% |
| All custom partials combined (887 lines: `common`, `header-footer`, `nav`, `game`, `menu`, `magazine`, `home`, `about`, `link`, etc.) | ~13 KiB | 19% |

Bootstrap and FontAwesome are 77% of the bundle, and both are genuinely
sitewide, not just "the biggest pages happen to use them": `layouts/footer.blade.php`
(`fab fa-github`) and `layouts/online_users.blade.php` (`far fa-user`) are
both `@include`d in `layouts/app.blade.php` on every page, so all three
FontAwesome families load regardless of page content — there's no per-page
family to split out. (The family-specific files are tiny anyway — `solid.min.css`
and `regular.min.css` gzip to ~340 bytes each; the 22.9 KiB is almost
entirely `fontawesome.css`, the shared icon-name-to-glyph map that any use of
`fa-solid`/`fa-regular`/`fa-brands` requires.) Bootstrap is pulled in via the
site's global grid/nav/forms/cards, also genuinely sitewide.

What *is* page-scoped: `flag-icons` (2.3 KiB, used only by
`games/card_magazines.blade.php`, `games/card_releases.blade.php`, and
`magazines/card_list.blade.php`), and the fully page-specific partials
(`game`, `menu`, `magazine`, `home`, `about`, `link` — as opposed to
sitewide ones like `common`/`header-footer`/`nav`), which sum to only ~6 KiB
gzip *combined*. Splitting those into per-page Vite CSS entries mirroring the
JS pattern is possible, but recovers at most a few KiB on any single page —
not enough on its own to move a 150ms-2,530ms `render-blocking-insight`
penalty, which is mostly the fixed cost of one extra blocking round trip, not
raw byte count. It also isn't free: each per-page entry would need Bootstrap's
variables/mixins available to it independently, and 6 KiB doesn't obviously
justify maintaining N Sass entry points.

**Revised approach, in two independent steps:**

(A third step — trimming `@import 'bootstrap/scss/bootstrap'` to only the
components actually used — was considered and measured as feasible, but
deliberately not taken: it would mean hand-maintaining a list of "enabled"
Bootstrap components going forward, and every future feature that reaches
for a Bootstrap component not on that list would silently render unstyled
until someone remembered to add it. Not worth that ongoing tax for one
component category's share of the bundle.)

1. **Extract `flag-icons` into its own small stylesheet**, loaded only by the
   three templates that use flag classes. Low risk: flags don't interact with
   the cascade of anything else, so there's no ordering/specificity concern
   to work around. **Done** (`8606a130`), and kept.
2. **Fix the actual render-blocking mechanism.** Since Lighthouse is penalizing
   the blocking round trip itself rather than the byte count, load the main
   bundle non-blocking via the standard preload/swap pattern — `<link
   rel="preload" as="style" onload="this.rel='stylesheet'">` with a
   `<noscript>` fallback — and inline a small critical-CSS block in `<head>`
   covering the header and nav. **Tried in `8606a130`/`f4a1b100` and reverted;
   see below.**

**Not recommended:** splitting the page-specific partials (game/menu/magazine/home/about/link)
into separate per-page Vite entries. The measured saving (~6 KiB gzip,
thinly spread) doesn't clear the bar for the added maintenance surface.

**Step 2 was implemented and then reverted — the async load traded a
render-blocking penalty for a much worse layout-shift one.** Re-running PSI
against production on 2026-09-08, same four URLs, mobile and desktop:
Cumulative Layout Shift came back at **0.78-0.97 on 7 of 8 runs** ("poor"
starts at 0.25), against 0.00-0.16 on the baseline above, and performance
scores fell on 6 of 8 runs even though `render-blocking-insight` now passed on
6 of 8. Lighthouse's `layout-shifts` audit named `body > main.container-xxxl`
— the content shell every template renders inside — as the largest shift on
every affected run (review/mobile: score 0.933, an 8173px shift). The reason
is structural: the inlined critical CSS covered `header`/`nav.navbar` only,
while Bootstrap's grid, container and card rules that `<main>` depends on
arrived with the swapped-in stylesheet, so the whole content area rendered
unstyled and snapped into its final layout in one frame.

Broadening the critical CSS by hand doesn't fix that. `_grid.scss` and
`_containers.scss` are pure layout and would import cleanly, but `_card.scss`
and `_reboot.scss` mix layout and paint in the same shorthand declarations
(`.card`'s `border: var(--bs-card-border-width) solid var(--bs-card-border-color)`),
which can't be split without hand-authoring and hand-maintaining the split
against Bootstrap's internals.

Generating the critical subset at build time instead was designed out in full:
`beasties` (the maintained fork of GoogleChromeLabs' archived `critters`,
htmlparser2-based, no headless browser) compiling the built `app.css` once,
scanning a handful of real pages served from the E2E fixtures, and merging
their document tokens into a single generated bundle. It works, and it is the
right shape — nothing hand-authored, nothing to drift. It was **not taken**
because of what it costs to stand up: a Node build script, a new dependency, a
hand-maintained page list, two CI steps, a config-driven runtime path in the
base layout with a fallback branch, and a feature test for that branch. That
is a lot of machinery to carry for one Lighthouse metric.

**So `app.scss` loads as a plain blocking stylesheet again, and
`render-blocking-insight`'s 150ms-2,530ms is a knowingly accepted cost, not an
open finding.** Anyone reopening this should start from the generated-bundle
design above rather than a hand-written critical block, and should re-measure
CLS, not just `render-blocking-insight`.

Two pieces of `8606a130`/`f4a1b100` survive the revert because neither depends
on the async load: the `flags.scss` split (step 1), and the four Sass values
promoted into `_variables.scss` (`$header-gradient-top`, `$header-height`,
`$header-max-width`, `$nav-border-color`) that `_header-footer.scss` and
`_nav.scss` now share.

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
displayed dimensions").

**Investigated further (2026-09-08) and decided not worth it for game
screenshots specifically.** The 25,908 files in `game_screenshots/` are almost
entirely native Atari ST resolution (91% sampled at 320×200 or within a few
px), averaging 12 KB — there's no oversizing in the usual sense to fix by
resizing; `w-100 pixelated` upscales these via CSS for display, it doesn't
downscale an oversized source. Format conversion was tested directly against
this corpus through the app's actual GD/Intervention stack (inside Sail):

| Encoding | Avg size vs PNG |
|---|---|
| WebP quality=100 (the encoding `GameReleaseResourcesController` already uses) | **+123% bigger** |
| WebP lossless (`IMG_WEBP_LOSSLESS`, not reachable via Intervention's `encode()` — requires dropping to the raw GD resource) | **-48% smaller** |

So the existing lossy-WebP pattern would make screenshots worse, not better —
flat-color, hard-edged pixel art compresses badly under lossy block
prediction. Lossless WebP does save ~48%, but capturing it means bypassing
Intervention's public API, and — measured in the same environment — costs
~9ms of CPU per image to decode+re-encode versus <0.3ms to stream the file
as-is, which rules out doing it on-the-fly per request the way box scans are
done today (screenshots are the single highest-request-volume image type on
the site: game show carousel, cards, search results, "similar games", release
cards). Making it cheap would require a one-off backfill converting all
25,908 files plus an upload-time conversion hook — real engineering for
~6 KB/image, on files that are already small in absolute terms. Decided not
worth the added complexity. Not pursuing further.

**Magazine cover scans remain a separate, worthwhile case.** Only 66 files in
`magazine_scans/`, but they're conventional photo scans (~1000×1400px,
120-225 KB JPEGs) with no resize route at all — the exact profile the
existing `GameReleaseResourcesController` resize+lossy-WebP pattern was built
for, and the low file count/traffic means on-the-fly conversion is fine as-is.

**Impact:** direct KiB savings on magazine listing pages only (screenshots
dropped, see above). **Effort:** small — apply the existing box-scan pattern
to magazine covers; no new route needed for screenshots.

### 7. The LCP element isn't discoverable until CSS parses — layout header

`lcp-discovery-insight` fails on 6 of 8 runs. On most pages the LCP element is
the header's CSS `background-image` (`css_top_bg-*.webp`, applied inside the
same render-blocking stylesheet from finding 5) — the browser can't start
fetching it until it has parsed that CSS, and there's no `fetchpriority=high`
hint on it either way. Once finding 5 is addressed this may resolve on its own;
if not, a `<link rel="preload" as="image">` for this specific asset in
`resources/views/layouts/app.blade.php` addresses it directly.

**Done** (`8606a130`): the preload is in the base layout and stays there.
Finding 5's step 2 was reverted, so the stylesheet blocks again and the preload
is now the whole of this fix rather than half of it — it still starts the image
fetch while the stylesheet is downloading and parsing, instead of after.

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
4. Finding 5's step 1 (flag-icons extraction) — small, low-risk, no
   dependency on anything else.
5. ~~Finding 5's step 2 + finding 7 together (preload/swap + critical CSS +
   LCP image)~~ — done and then reverted; only the LCP image preload survives.
   See finding 5 for the CLS regression that reversed it and for what a
   further attempt would have to look like.
6. Finding 6, magazine covers only (screenshots ruled out, see above) — small
   effort, follows the existing box-scan pattern exactly; no urgency, do when
   convenient.

## Raw data

The 8 raw PSI API responses (JSON) were saved to a scratch directory for this
session and were not committed to the repository — none of the figures above
depend on them existing afterward; re-running the same 4 URLs through the
PageSpeed Insights API (`https://www.googleapis.com/pagespeedonline/v5/runPagespeed`)
reproduces them.
