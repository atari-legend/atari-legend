# The Atari Legend Project

[![Build Status](https://github.com/atari-legend/atari-legend/workflows/Build/badge.svg)](https://github.com/atari-legend/atari-legend/actions)
[![Style CI](https://github.styleci.io/repos/291270023/shield)](https://github.styleci.io/repos/291270023)
[![Quality Score](https://scrutinizer-ci.com/g/atari-legend/atari-legend/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/atari-legend/atari-legend/)

This is the source code for [Atari Legend](https://www.atarilegend.com/), a website for Atari ST enthusiasts.

It is built with [Laravel](https://laravel.com/) and [Bootstrap](https://v5.getbootstrap.com/).

This repository contains the source code for the public site and its administration part
(Control Panel). It is a 2020 re-implementation of the legacy site with Laravel and
Bootstrap. The legacy codebase (See the [Legacy](https://github.com/atari-legend/legacy)
repository) is no longer used: the site data - screenshots, scans, dump ZIPs -
lives in this site's `storage/app/public`.

## Build & Development

Development runs on [Laravel Sail](https://laravel.com/docs/11.x/sail), so the
only things needed on the host are Docker and PHP long enough to install the
dependencies. Everything else - PHP 8.4, MariaDB 10.11, Node, Composer - lives
in the containers.

```bash
cp .env.example .env
composer install            # or, without host PHP, the Sail bootstrap container:
                            # docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html \
                            #   laravelsail/php84-composer:latest composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

The site is then at http://localhost. Alias `sail` to `./vendor/bin/sail` and
the rest reads the way the Laravel documentation does:

| | |
|---|---|
| `sail artisan ...` | Artisan, against the container's PHP |
| `sail composer ...`, `sail npm ...` | Composer and NPM |
| `sail test` | the PHPUnit suite (SQLite in-memory) |
| `sail mariadb` | a SQL shell on the development database |
| `sail down` / `sail up -d` | stop and start; the database survives |

The database is empty until something is put in it. Restore a dump, or seed the
end-to-end fixture with `sail artisan db:seed --class=E2ESeeder` - not the
default `DatabaseSeeder`, which predates this schema.

Other things the stack publishes:

- **http://localhost:8025** - Mailpit, which catches every mail the site sends
  rather than delivering it
- **http://localhost:8081** - phpMyAdmin
- **3306** - MariaDB, for a native client

Every port is overridable in `.env`; see the block at the end of
`.env.example`.

### Native binaries

Two features shell out to binaries that are not in the repository, because they
are third-party builds rather than source. Both degrade quietly when absent, so
neither is needed to work on the site:

- `resources/bin/hxcfe/` - the [HxC Floppy
  Emulator](https://hxc2001.com/) binary and its two `.so` files, which
  `dump:trackpictures` uses to draw the track pictures of flux
  dumps. It must be a **glibc** build: the container is Ubuntu, and a
  musl-linked binary will not exec there.
- `resources/bin/unice68` or `resources/bin/icecat` - PACK-ICE decompression for
  `sndh:generate-json`. See `resources/bin/unice.sh`.

### Tests

`sail test` runs the PHPUnit suite. The Playwright end-to-end suite has its own
setup, database and conventions: see [tests/e2e/README.md](tests/e2e/README.md).

## Laravel Environment

In addition to the standard Laravel settings in your `.env` file, the following specific
settings are supported:

```
# hCaptcha configuration. Use the configuration below for development and testing
# See: https://docs.hcaptcha.com/#integrationtest
CAPTCHA_SITEKEY=10000000-ffff-ffff-ffff-000000000001
CAPTCHA_SECRET=0x0000000000000000000000000000000000000000

# Matomo analytics ID. Leave blank to not include the Matomo analytics tag in
# the pages
MATOMO_ID=12345
```
