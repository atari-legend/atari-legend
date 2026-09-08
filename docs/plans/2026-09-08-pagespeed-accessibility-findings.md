# PageSpeed accessibility findings

*2026-09-08*

The 2026-09-06 PageSpeed Insights audit
(`docs/plans/2026-09-06-pagespeed-performance-improvements.md`) tested four
production pages (home, a game detail page, a menu set page, a review page,
mobile + desktop = 8 runs) across all four Lighthouse categories. Its
"Accessibility findings" section named five checks that failed incidentally,
never scoped or fixed:

- `landmark-one-main` — no `<main>` element in the base layout (7/8 runs)
- `color-contrast` — insufficient contrast somewhere on nearly every page (7/8 runs)
- `target-size` — touch targets too small/close on mobile (4/8 runs)
- `link-name` — links with no accessible name, menu set pages specifically (2/8 runs)
- `frame-title` — YouTube iframe missing a `title` (mentioned separately, tied to finding 1)

`frame-title` is already resolved: finding 1 of that audit (the YouTube
facade) shipped in commit `024c5e70`, and the injected iframe sets
`iframe.title = el.dataset.youtubeTitle` (`resources/js/game/video.js:11`).
No work needed for it.

This plan fixes the remaining four. Each traces to a small number of root
causes rather than one-off page content problems, so the fix is structural
(a layout change, a shared class, two Sass variables) rather than a long
tail of individual edits.

**End state:** `landmark-one-main`, `link-name`, `target-size`, and
`color-contrast` no longer reproduce when the same four pages are re-run
through PageSpeed Insights.

**Units**, independent of each other, one commit each:

| Unit | Fixes | Files |
|---|---|---|
| Main landmark | `landmark-one-main` | `resources/views/layouts/app.blade.php` |
| Icon-only interactive elements | `link-name`, `target-size` | `resources/views/menus/partial_menudisk.blade.php`, `resources/views/menus/partial_menudisk_content.blade.php`, `resources/views/components/cards/menu.blade.php`, `resources/views/components/cards/latest-menus.blade.php`, `resources/views/layouts/footer.blade.php`, `resources/sass/_common.scss`, `resources/sass/menu/_menu.scss` |
| Color contrast | `color-contrast` | `resources/sass/_variables.scss` |

No dependency order between the three. Base on `development`.

## Main landmark

### The gap

`resources/views/layouts/app.blade.php:101-120` renders `header` → `nav` →
a plain `<div class="container-xxxl">` wrapping `layouts.alert` and
`@yield('content')` → `online_users` → `footer`. No `<main>` anywhere.
`grep -rn "<main" resources/views/` finds exactly one hit,
`resources/views/admin/layouts/admin.blade.php:32` — a fully independent
layout (own `<html>`/`<head>`/`<body>`, no `@extends('layouts.app')`), so
there is no nesting conflict with the public layout.

### The change

Replace the wrapping `<div class="container-xxxl">` with `<main
class="container-xxxl">`:

```blade
<main class="container-xxxl">
    @include('layouts.alert')

    @yield('content')
</main>
```

### Acceptance

`grep -c "<main" resources/views/layouts/app.blade.php` returns 1. A fresh
PageSpeed Insights run against the four audited URLs no longer lists
`landmark-one-main`.

## Icon-only interactive elements

### The gap

Several `<a>` elements sitewide render only a FontAwesome `<i>` glyph, with
no text, `aria-label`, or `alt` — no accessible name, and no clickable area
beyond the ~16px glyph (well under the 24×24 CSS px minimum). PSI's
`link-name` finding named the menu set pages specifically; all instances
found trace to menu templates plus the shared footer, all with the same
shape:

- `resources/views/menus/partial_menudisk.blade.php:30-32` — contributor-only edit icon
- `resources/views/menus/partial_menudisk.blade.php:35-38` — permalink icon, additionally `display: none` outside `h3:hover` (`resources/sass/menu/_menu.scss:24-26`), so it is undiscoverable by keyboard/touch entirely, not just unlabeled
- `resources/views/menus/partial_menudisk.blade.php:115` — copy-checksum icon
- `resources/views/menus/partial_menudisk.blade.php:120-124` — download icon
- `resources/views/menus/partial_menudisk_content.blade.php:9-11` — info-popover trigger icon
- `resources/views/components/cards/menu.blade.php:69-73`, `:77` — same download/copy pair, "random menu disk" card
- `resources/views/components/cards/latest-menus.blade.php:50-54` — same download pattern
- `resources/views/layouts/footer.blade.php:6-8` — GitHub icon link; its `title` sits on the child `<i>`, not the `<a>`, so it doesn't reliably name the link
- `resources/views/layouts/footer.blade.php:5` — CC-license link has a name already (image `alt="Creative Commons License"`), but at 80×15px, adjacent to the GitHub link with no separating padding, it fails on size alone

### Decisions

Add one shared utility class rather than hand-sizing each instance:

```scss
// resources/sass/_common.scss
.icon-link {
    position: relative;
    text-decoration: none; // an icon glyph is real text content, so the
                            // default link underline would otherwise
                            // render under it

    &::after {
        content: '';
        position: absolute;
        inset: -.375rem; // enlarges the hit area to >=24x24 without
                          // resizing the link's own box - the visible
                          // icon/image keeps its natural size and default
                          // baseline alignment with any surrounding text
    }
}
```

Apply `icon-link` plus an `aria-label` to every anchor listed above (footer
CC-license link gets `icon-link` only, its name is already correct). Move
the footer GitHub link's name from the child `<i title>` to `aria-label` on
the `<a>`.

The footer's copyright line mixes three kinds of content on one line — the
plain copyright text, the CC badge `<img>`, and the GitHub `<i>` icon —
each with different font/box metrics, so no single `vertical-align`
keyword lines all three up against each other (measured: image and icon
visual centers landed 0.5-1.3px off the text's under every keyword tried).
Its `<p>` gets a dedicated `.footer-copyright` class instead
(`resources/sass/_header-footer.scss`) — `display: flex; align-items:
center; justify-content: center; gap: .5rem;` — which centers by each
item's actual box height rather than font baseline metrics. This is its
own class, not folded into `.icon-link`, since `.icon-link` is shared by
a dozen icon-only links elsewhere (menu disk cards, etc.) that only ever
contain a single icon glyph and were already aligned correctly by
default.

Change the permalink icon's hover-only visibility to also respond to
keyboard focus, so it's reachable by something other than a mouse:

```scss
// resources/sass/menu/_menu.scss
.menu-link {
    opacity: 0; // not display:none - that would remove it from the tab
                // order, making it unreachable by keyboard in the first
                // place and the :focus-within rule below unfireable
}

h3:hover .menu-link,
h3:focus-within .menu-link {
    opacity: 1;
}
```

Out of scope: `resources/views/games/card_search.blade.php` has the same
icon-only-anchor shape (dropdown chevrons, e.g. line 53) but sits on a page
outside the four PSI audited — left for a future pass rather than fixed
speculatively here.

### Acceptance

Every file:line above carries both `icon-link` and a non-empty
`aria-label` (or, for the CC-license link, `icon-link` alone). Tabbing
through a menu disk card reaches the permalink icon and it becomes visible
on focus. A fresh PageSpeed Insights run against the four audited URLs no
longer lists `link-name` or `target-size`.

## Color contrast

### The gap

`resources/sass/_variables.scss` overrides `$body-bg` (`#000`) and
`$body-color` (`#fff`) for the site's dark theme, but never overrides
Bootstrap 5.2's `$card-bg` (`$white`) or `$card-cap-bg` (`rgba($black,
.03)`, confirmed in `node_modules/bootstrap/scss/_variables.scss:1245,1249`)
or `$text-muted` (`$gray-600`, `#6c757d`). `.card-header` always paints its
own background (`node_modules/bootstrap/scss/_card.scss:101-106`), so this
holds even for cards whose body sets `.bg-dark` explicitly — e.g. the menu
disk card (`partial_menudisk.blade.php:1-2`) still gets a near-white header
underneath its `text-primary` title link and `.text-muted` permalink icon.

Contrast ratios computed by the standard WCAG relative-luminance formula
(2026-09-08):

| Pair | Ratio | AA (4.5:1) |
|---|---|---|
| `$primary` link (`#00d3d3`) on white card body | 1.87:1 | fail |
| White card-header text/links on `$card-cap-bg` (`~#f7f7f7`) | 1.07:1 | fail |
| `text-muted` (`#6c757d`) on `$body-bg` (`#000`) | 4.48:1 | fail (narrowly) |
| `text-muted` on `$dark-light` (`#0d2e2e`, e.g. `.bg-darklight` card footers) | 3.09:1 | fail |
| `text-muted` on the about-page timeline card (`#294f4f`) | 1.93:1 | fail |

### Decisions

Theme the two Bootstrap card variables and `text-muted` to the site's dark
palette, in `resources/sass/_variables.scss`:

```scss
$card-bg: $dark;      // #122323
$card-cap-bg: $dark;  // #122323 - same as $card-bg, not $dark-light: a
                       // header lighter than its body would collide with
                       // any adjacent row/footer already styled
                       // .bg-darklight ($dark-light), losing the
                       // alternation between them
$text-muted: #999999;  // dark enough to stay visually distinct from
                        // $body-color (#fff), light enough for AA
                        // against every background text-muted sits on
```

`text-muted` is not a Bootstrap gray step: every step light enough to clear
AA against the about-page timeline card's original background (`#294f4f`,
see below) sits within 1.5:1 of white — indistinguishable from regular
body text at a glance. `#999999` gives 2.85:1 separation from
`$body-color`, close to the 4.69:1 separation Bootstrap's own `$gray-600`
(`#6c757d`) had against a white body before this site's dark theme was
applied. `$link-color` (inherits `$primary`) is not overridden separately;
the card-body failure was the white background, not the link color, and
`$primary` already clears 8:1+ against every dark background in use.

Retire the about-page timeline card's one-off `#294f4f` background in
favor of the site's own `$dark-light` token
(`resources/sass/about/_history.scss:28`) — it was the only background in
use dark enough to force `text-muted` this close to white, and reusing the
existing dark-surface token instead of a custom color also brings the
timeline in line with how every other dark surface on the site is themed
(e.g. `.bg-darklight` card footers).

Recomputed ratios: `$primary` on `$card-bg`/`$card-cap-bg` 8.72:1, white on
the same 16.27:1, `text-muted` on `$body-bg` 7.37:1, on `$dark` 5.71:1, on
`$dark-light` (including the retiled timeline card) 5.09:1 — all clear AA
with margin.

Out of scope: `.text-contributor` (`#d3006a`, `resources/sass/_common.scss:82-84`)
also fails contrast against these backgrounds (2.75-3.98:1), but every use
found is gated behind `@contributor` (`Auth::user()->permission ===
PERMISSION_ADMIN`, `app/Providers/AppServiceProvider.php:40-42`) — not
visible to PSI's anonymous crawl, and not one of the findings it reported.
Left for a future pass.

### Acceptance

`grep -n "card-bg\|card-cap-bg\|text-muted" resources/sass/_variables.scss`
shows the three new lines. A visual pass over the home, game detail, menu
set, review, and about pages shows no card rendering with a white or
near-white background, and `text-muted` text (e.g. "Condition:" labels on
a menu set page) reads visibly dimmer than the surrounding white text
rather than blending into it. A fresh PageSpeed Insights run against the
four audited URLs no longer lists `color-contrast`.

## Verification

Re-run PageSpeed Insights (or local Lighthouse) against the same four URLs
used in the 2026-09-06 audit — home, `/games/super-stario-land`,
`/menusets/1`, `/reviews/133` — mobile and desktop. Confirm
`landmark-one-main`, `link-name`, `target-size`, and `color-contrast` are
absent from all 8 runs, and that no other accessibility score regresses.

## Out of scope

- `resources/views/games/card_search.blade.php` icon-only dropdown toggles — same pattern, page not in the audited set.
- `.text-contributor` color contrast — admin-only, not in the audited scope.
- `frame-title` — already fixed by commit `024c5e70`; no work here.
