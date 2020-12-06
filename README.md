# The AtariLegend Project

[![Build Status](https://github.com/atari-legend/laravel-prototype/workflows/Build/badge.svg)](https://github.com/atari-legend/laravel-prototype/actions)
[![Style CI](https://github.styleci.io/repos/291270023/shield)](https://github.styleci.io/repos/291270023)
[![Quality Score](https://scrutinizer-ci.com/g/atari-legend/laravel-prototype/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/atari-legend/laravel-prototype/)

This is the source code for [AtariLegend](https://www.atarilegend.com/), a website for Atari ST enthusiasts.

It is built with [Laravel](https://laravel.com/) and [Bootstrap](https://v5.getbootstrap.com/).

This repository contains the source code for the public site. It is a 2020 re-implementation
of the legacy site with Laravel and Bootstrap. The administration part (Control Panel aka CPANEL)
is still running on the legacy codebase (See the [Legacy](https://github.com/atari-legend/legacy)
repository).

## Build & Development

This is a typical Laravel application:

- Run `composer install` to install PHP dependencies
- Run `npm install` to install NPM dependencies
- Run `npm run dev` or `npm run prod` to generate the static resources (JS, CSS)

## Laravel Environment

In addition to the standard Laravel settings in your `.env` file, the following specific
settings are supported:

```
# Base URL for the legacy site, used to make links to CPANEL
AL_LEGACY_BASE_URL=http://legacy.atarilegend.com

# hCaptcha configuration. Use the configuration below for development and testing
# See: https://docs.hcaptcha.com/#integrationtest
CAPTCHA_SITEKEY=10000000-ffff-ffff-ffff-000000000001
CAPTCHA_SECRET=0x0000000000000000000000000000000000000000

# Google Analytics ID. Leave blank to not include the Google Analytics tag in
# the pages
GOOGLE_ANALYTICS_ID=UA-12345-67
```
