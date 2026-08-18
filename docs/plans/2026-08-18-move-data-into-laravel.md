# Move the site data into the Laravel tree

*2026-08-18*

## Why

`storage/app/public` is not a directory on either server. It is a symlink the
deploy script creates, pointing at the legacy site's data folder:

| | Laravel tree | Data actually lives in |
|---|---|---|
| dev | `~/dev.atarilegend.com` | `~/legacydev.atarilegend.com/data` |
| prod | `~/www.atarilegend.com` | `~/legacy.atarilegend.com/data` |

That arrangement dates from when the legacy CPANEL wrote the same files. It no
longer does — it is not used any more — so the only thing still tying eleven
gigabytes of screenshots, scans and dump ZIPs to a deployment we do not run is
history. Moving them makes this repo's deployment self-contained, and follows
the database dumps, which moved to `public/data/database-dumps` in e95a09c.

Nothing in `app/` needs to change. Every read and write already goes through
`Storage::disk('public')`, whose root is `storage_path('app/public')`, and the
site already serves the files itself through `public/storage`. The whole job is
a rename plus the deploy plumbing that recreates the symlink.

## What changed in this repo

Landed with this plan, and safe to deploy before the servers move:

- **`.github/workflows/deploy.sh`** — the `LEGACY_PATH` parameter and the block
  that creates the symlink are gone, replaced by a `mkdir -p
  storage/app/public` for a target that has no data folder yet. It is
  indifferent to which layout the server has, since `mkdir -p` follows the
  symlink. `--exclude storage/app/public` and `--exclude public/storage` stay:
  today they protect a symlink, afterwards they are the only thing standing
  between `rsync --delete` and eleven gigabytes.
- **`.github/workflows/build-and-deploy.yml`** — the fourth argument is dropped
  from both deploy invocations.
- **`README.md`, `CLAUDE.md`** — `storage/app/public` described as the real
  directory it becomes (and named correctly: `CLAUDE.md` had it as
  `storage/public`).

## The move, per server

Land the repo change first. The new script is safe against both layouts — it
does not care whether `storage/app/public` is a symlink or a directory — whereas
the current one is not: run against a server where the move has already
happened, `ln -s ../../../$LEGACY_PATH/data public` with `public` an existing
directory does not fail, it silently creates a broken `storage/app/public/data`
link inside it. So: merge, let dev deploy, then move dev, verify, then prod.

Per server, with the site down for the few seconds it takes:

```bash
# 1. Confirm both paths are on one filesystem. Equal numbers mean the mv below
#    is a rename: instantaneous, and no free space required. Different numbers
#    mean a copy of ~11.5 GB instead, and the plan needs revisiting.
stat -c %d ~/www.atarilegend.com/storage/app ~/legacy.atarilegend.com/data

cd ~/www.atarilegend.com
php8.4-cli artisan down

# 2. Replace the symlink with the real thing.
rm storage/app/public
mv ~/legacy.atarilegend.com/data storage/app/public

php8.4-cli artisan up
```

Substitute `dev.atarilegend.com` and `legacydev.atarilegend.com` for dev, where
this gets rehearsed first.

Nothing is left behind in the legacy tree — no symlink back, because nothing
reads it any more. Its own deploy script excludes `data`, so it will neither
recreate nor miss it.

## Then

- **Check the images export reads the Laravel path.** The crons that generate
  the database dumps and ZIP the images are already updated for
  `public/data/database-dumps`. If the images one reads its source through
  `~/www.atarilegend.com/storage/app/public/images`, it is transparent to this
  move — that path is the symlink today and the directory afterwards, and the
  job never notices. It only needs touching if it still names
  `legacy*.atarilegend.com/data` directly.
- **Re-point any backup job** covering `legacy*.atarilegend.com/data`.
- **Verify**, on dev before prod: a game page's screenshots and a release's box
  scans load; an upload through the admin lands and comes back (`Storage::disk`
  round trip); a menu dump ZIP downloads; `artisan menus:check-dumps` reports
  what it did before; `artisan sndh:fetch` still writes into `sndh/`.

## Rollback

Reverse the two commands — `mv storage/app/public ~/legacy.atarilegend.com/data`
and recreate the symlink — until the deploy plumbing is merged. After that,
rolling back means reverting the repo change too, so verify on dev and give
prod its own window rather than doing both in one sitting.

## Notes

Permissions need no thought here, which is worth stating explicitly rather than
discovering: the Laravel PHP user already creates and deletes files in this
exact tree through the symlink, and `mv` within a filesystem preserves ownership
and modes on every file. What could write before can still write after.

Apache is likewise a non-issue: `public/storage` resolves in one hop instead of
two, and the requirement on symlink following only relaxes. The one thing the
move does change is that the data now sits inside an `rsync --delete` target,
guarded by two exclude lines. A deploy-time check on the data was considered and
dropped: by the time it could fire the files are already gone, and it would read
as protection it cannot give. What protects the data is a backup that does not
live on the same host.

The legacy deployment keeps running as a code tree with no data in it. Whether
it gets retired altogether — the vhost, the workflow, the repo — is a separate
decision this plan does not take.
