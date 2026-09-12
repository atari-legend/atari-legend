# Column type consistency

*2026-09-11*

Successor to the [timestamp type consistency](2026-09-06-timestamp-type-consistency.md)
campaign, which converted every column holding a moment in time. Three earlier
plans deferred the rest of the type work by name: the
[entity names and pivot order](2026-09-02-entity-names-and-pivot-order.md) plan
called it category B and left `image_ext` ("type and spelling drift across nine
`imgext` columns"), the `menu_sets.sort_direction` enum members, `changelogs.action`,
and the nine boolean columns; the
[column name consistency](2026-08-31-column-name-consistency.md) sweep renamed
the `imgext` columns without touching their types. This plan takes the image
extension columns — their spelling and their type — the `name` columns, the
remaining string widths, four numeric columns and two boolean columns, and
aligns every file upload in the application with the column it writes to.

Every figure below was measured on 2026-09-11 against the dev MariaDB 10.11
database (114 tables, 497 columns), and each query is written out so it can be
re-run.

End state, clause by clause:

- Every column holding an image extension is called `imgext` and is an `ENUM`
  of exactly the formats that column accepts, and no two of them disagree about
  `jpg` and `jpeg`: wherever `jpg` is a member, `jpeg` is a member. Checked by
  the extension census in "Verification", which returns twelve rows, every
  `column_name` `imgext`, every `data_type` `enum` and a `column_type` to read
  the members off, and by the `jpg`/`jpeg` pairing query beside it.
- Every image upload validates the extension it is about to store against the
  same list its column will accept. Checked by the eighteen feature tests
  Unit 1 adds, one per upload path, each asserting a rejection. The two dump
  uploads are separate: they validate against `Dump::FORMATS` and
  `MenuDiskDump::EXTENSIONS`, which Unit 1 makes agree, and only the menu-disk
  one rejects out loud. The release path's silent skip is left as it is — see
  "Out of scope".
- Every upload site stores a lowercase extension. `UploadedFile::extension()`
  is a MIME-map lookup that already returns lowercase, so the nine sites
  reading it need nothing; the case-preserving readers are
  `getClientOriginalExtension()`, `pathinfo(..., PATHINFO_EXTENSION)` and the
  `File::extension()` facade, and they are what this clause is about. Checked
  by `grep -rnE "getClientOriginalExtension\(\)|PATHINFO_EXTENSION|File::extension\(" app --include=*.php`,
  whose every hit is wrapped in `strtolower()`; on 2026-09-11 it returns six
  lines, of which two wrap in `strtoupper()` instead and three in nothing.
- One spelling exists for a disk image format: `dumps.format`,
  `menu_disk_dumps.format` and the disk-image members of `screenshots.imgext`
  are all lowercase. Checked by the format census in "Verification".
- Every column called `name` is `varchar(255)`. Checked by the `name` census in
  "Verification", which returns 42 rows and one distinct `column_type`.
- No column other than the two `database_changes` columns named under "Out of
  scope" is declared `varchar(256)`. Checked by the width census in
  "Verification", which returns those two rows and nothing else.
- No column holding a short single-line string is a `TEXT` variant, and no
  column holding a body of prose is narrower than `MEDIUMTEXT`. Checked by the
  two body-column queries in "Verification".
- A column holding a true/false fact is `tinyint(1)` and its model casts it to
  `boolean`. Checked by the boolean census in "Verification" and by
  `grep -rn "'boolean'" app/Models/*.php`, which returns nine lines; on
  2026-09-11 it returns two.

**The delivery unit is one commit per unit, based on `development`, not one
pull request.**

| Unit | Tables | Migrations | Rollback |
|---|---|---|---|
| 1 | none (application code only) | none | revert the commit |
| 2 | the twelve extension columns | `2026_09_11_100000_extension_column_cleanup.php`, `2026_09_11_100050_extension_columns_rename.php`, `2026_09_11_100100_extension_columns_to_enums.php` | `--step=3` |
| 3 | `dumps`, `menu_disk_dumps` | `2026_09_11_100200_dump_formats_lowercase.php` | `--step=1` |
| 4 | the 42 tables carrying a `name` column | `2026_09_11_100300_name_columns_to_255.php` | `--step=1` |
| 5 | twelve `varchar(256)` columns, four oversized text columns, two undersized ones | `2026_09_11_100400_string_column_widths.php` | `--step=1` |
| 6 | `dumps`, `menu_disk_dumps`, `menu_disk_contents`, `users` | `2026_09_11_100500_numeric_column_types.php` | `--step=1` |
| 7 | `reviews`, `game_submissions` | `2026_09_11_100600_review_submission_boolean.php`, `2026_09_11_100700_game_done_to_reviewed.php` | `--step=2` |

Reverting a unit's commit removes all of that unit's migration files at once,
so Unit 2's revert removes three, Unit 7's two and Units 3 to 6 one each.
Unit 2's first migration deletes a row its `down()` cannot restore, so rolling
it back returns the schema, not that row.

Units ship in order. **Unit 1 precedes Unit 2**: once a column is an enum, an
unlisted extension is a database error rather than a form rejection, so the
validation that produces the clean rejection has to be deployed first. **Unit 3
follows Unit 2** because `screenshots.imgext` gains lowercase `st`, `msa` and
`stx` members in Unit 2, which is the spelling Unit 3 then gives
`dumps.format`. Units 4, 5, 6 and 7 are independent of each other and of the
first three.

## Unit 1 — every upload validates what it stores

### The upload sites

Eighteen places in the application take a file, read its extension and store
both the file and the extension. Four validate the file type; fourteen do not.
The last column is what each reads the extension *from*, which is what decides
its case.

| Site | Column written | Validates type | Extension read from |
|---|---|---|---|
| `GameController.php:212-220` | `screenshots.imgext` | no | `extension()` |
| `Auth/UserController.php:36-39` | `users.avatar_ext` | `nullable\|image` (`UserHelper.php:23`) | `extension()` |
| `Admin/User/UserController.php:40,48-51` | `users.avatar_ext` | `nullable\|image` (`UserHelper.php:23`) | `extension()` |
| `Admin/Games/GameScreenshotsController.php:33,39-42` | `screenshots.imgext` | `screenshot.* => image` | `extension()` |
| `Admin/Games/GameFactsController.php:146-148` | `screenshots.imgext` | no | `extension()` |
| `Admin/Articles/ArticleController.php:142,146-151` | `screenshots.imgext` | `image => array` only | `extension()` |
| `Admin/Interviews/InterviewsController.php:137,141-146` | `screenshots.imgext` | `image => array` only | `extension()` |
| `Admin/Other/SpotlightController.php:145,155-158` | `screenshots.imgext` | no | `extension()` |
| `Admin/Menus/MenuDisksController.php:123-126` | `menu_disk_screenshots.imgext` | no | `extension()` |
| `Admin/Menus/MenuCrewController.php:217-220` | `crews.logo` | no | `extension()` |
| `Admin/Links/LinkController.php:23,175-182` | `links.imgext` | `nullable\|image` | `extension()` |
| `Admin/Games/GameCompanyController.php:61-62,108-109` | `companies.imgext` | no | `extension()` |
| `Admin/Games/GameIndividualController.php:88-89,138-139` | `individuals.imgext` | no | `extension()` |
| `Admin/Magazines/MagazineIssuesController.php:139-143` | `magazine_issues.imgext` | no | `extension()` |
| `Admin/News/NewsController.php:154,167-170` | `news_images.imgext` | no | `extension()` |
| `Admin/Games/Releases/ReleaseMediasScansController.php:21,33-42` | `media_scans.imgext` | `file => required\|array` only | `File::extension()` |
| `Admin/Games/Releases/ReleaseScansController.php:88,99-116` | `game_release_scans.imgext` | `file => required\|array` only | `File::extension()` |
| `Admin/Magazines/MagazineIssuesController.php:147-158` | `magazine_issues.imgext` | no | HTTP `Content-Type` |

`image => array` on the article and interview paths, and `file => required|array`
on the two scan paths, validate that the request field is an array, not that its
members are images. Neither constrains the file type.

**Fifteen of the eighteen already store lowercase and cannot do otherwise.**
`UploadedFile::extension()` is `guessExtension()`, a lookup in Symfony's MIME
map keyed on the sniffed content type, and every value in that map is lowercase:
a `FOO.JPG` upload yields `jpg` whether or not the call site wraps it in
`strtolower()`. The six sites that wrap it are not doing anything the other nine
are missing, and none of the fifteen can store `JPG`.

The three that can are the ones reading something else. The two scan paths store
through `Storage::disk('public')->put()` and read the extension from the FilePond
temp path with the `File::extension()` facade, which is `pathinfo()`; FilePond
names that temp file `getClientOriginalName()`
(`vendor/sopamo/laravel-filepond/src/Http/Controllers/FilepondController.php:47`),
so the client's case survives into the column.
`game_release_scans.imgext` is already an enum, so it is the one column where an
unlisted extension is a database error today — the behaviour Unit 2's conversion
gives the other eleven. Its `utf8mb4_unicode_ci` collation is what keeps an
uppercase `JPG` from being the error too; `media_scans.imgext` is still a
`varchar` and would simply store it.

**The eighteenth site is not a file upload at all.**
`MagazineIssuesController::fetchImage()` (`:147-158`) downloads an archive.org
cover and derives the extension from the response header:

```php
$mimeType = explode(';', $response->header('Content-Type'))[0];
$ext = explode('/', $mimeType)[1];
$issue->update(['imgext' => $ext]);
```

Nothing validates it, nothing lowercases it, and nothing checks the response
succeeded. This is where all 66 `jpeg` values in `magazine_issues` came from —
`extension()` returns `jpg` for a JPEG and can never produce `jpeg` — and it is
why the `jpg`/`jpeg` pairing rule below exists. After Unit 2 it is the sharpest
edge in the plan: an archive.org 404 sends `text/html` and writes `html`, a
WebP cover writes `webp`, and either is a strict-mode error on an admin action.
It is treated as an upload site here and validated like one.

The public game-submission form is the one with no validation of any kind, and
it is where every non-image row in `screenshots` came from — see Unit 2.

The dump uploads already validate, against a constant rather than the column:
`ReleaseMediasDumpsController.php:41,75` tests `in_array(strtoupper($ext), Dump::FORMATS)`
and `MenuDisksController.php:176,199` tests `MenuDiskDump::EXTENSIONS`. The two
constants disagree — `Dump::FORMATS` is `['MSA', 'SCP', 'ST', 'STX']` and
`MenuDiskDump::EXTENSIONS` is `['STX', 'MSA', 'RAW', 'SCP', 'ST']` — so a `.raw`
dump is accepted for a menu disk and silently skipped for a game release, while
the `dumps.format` enum accepts `RAW` in both cases. `Dump::FORMATS` gains `RAW`
here, which makes the two constants and the two enums agree.

**Each upload validates against the list its own column accepts, and the list is
declared once per column.** A single shared "is it an image" rule is what the
`image` rule already does on three paths, and it is why `webp` reaches columns
whose enum will not list it. The per-column lists are in Unit 2.

**The lists live on the models as constants, not in the controllers.** Eleven
columns across nine models each get an `EXTENSIONS` constant naming its enum
members, the validation rule is built from that constant, and Unit 2's migration
is written from the same list. One edit widens a column and its form together.

### The changes

- `Screenshot`, `MenuDiskScreenshot`, `NewsImage`, `MediaScan`, `MagazineIssue`,
  `Link`, `Company`, `Individual`, `Crew`, `User` and `GameReleaseScan` — eleven
  models, one for each enum'd column that has an upload — each gain an
  `EXTENSIONS` constant. The twelfth column, `game_galleries.image_ext`, has no
  model and no upload site; Unit 2 writes its enum from the plan.
- Each of the eighteen sites gains a validation rule built from the constant of
  the column it writes, replacing the `image` rule where one exists.
  `fetchImage()` checks its derived `$ext` against `MagazineIssue::EXTENSIONS`
  and flashes an error instead of writing, which also covers the case where
  archive.org answers with something that is not an image at all.
- The three case-preserving sites gain `strtolower()`:
  `ReleaseMediasScansController.php:33`, `ReleaseScansController.php:99` and
  `fetchImage()`'s `$ext`. This covers the filename too — all three build the
  stored path from the same value — so the file on disk takes the same case as
  the column.
- The fifteen `extension()` sites are left alone. Six wrap the call in
  `strtolower()` today and nine do not, and the difference has no effect: the
  value is lowercase either way. Adding the other nine wrappers would only
  suggest the case hazard lives where it does not.
- `MenuDisksController.php:126` is the one `storeAs()` that re-reads
  `$screenshotFile->extension()` instead of the value it just stored at `:123`.
  It becomes `$screenshot->imgext`, so the filename cannot drift from the column
  even if the two calls ever disagree — the shape `GameController.php:219` and
  `MagazineIssuesController.php:143` already use.
- `Dump::FORMATS` gains `'RAW'`.
- `GameController::submit()` accepts images, archives and disk images, matching
  the fifteen members `screenshots.imgext` carries in Unit 2. Contributors are
  already sending disk images and map archives through this form, and the enum
  is written to keep accepting them.

### Acceptance

Eighteen feature tests, one per upload path, each posting a file whose extension
is not in that column's list and asserting a validation error rather than a
stored row. These run on SQLite and pass there, because the rule is applied in
the application. The `fetchImage()` one fakes an archive.org response with a
`Content-Type` of `text/html` and asserts `magazine_issues.imgext` is untouched.

```bash
# Every case-preserving read is wrapped in strtolower(). On 2026-09-11 this
# returns six lines: two wrapped in strtoupper(), three in nothing, one
# lowercasing its path instead.
grep -rnE "getClientOriginalExtension\(\)|PATHINFO_EXTENSION|File::extension\(" app --include=*.php
```

`DumpHelper.php:45` satisfies it by lowercasing the path rather than the result,
which is equivalent and is left as it is. The other two hits are Unit 3's.

`php artisan test` passes.

## Unit 2 — the image extension columns are named `imgext` and become enums

### The columns

Twelve columns hold a file extension. They carry six distinct types, and the
longest value stored in any of them is four characters.

| Column | Type today | Rows with a value | Values held |
|---|---|---|---|
| `screenshots.imgext` | `varchar(11)` | 28,727 | png 27,765, jpg 877, gif 57, bmp 11, zip 9, webp 3, pdf 2, bin 2, txt 1 |
| `menu_disk_screenshots.imgext` | `varchar(4)` | 3,957 | png 3,949, bmp 6, zip 1, jpg 1 |
| `game_release_scans.imgext` | `enum('png','jpg','jpeg')` | 3,467 | jpg 2,140, png 1,327 |
| `news_images.imgext` | `varchar(64)` | 264 | png 218, jpg 43, gif 2, webp 1 |
| `links.imgext` | `varchar(11)` | 188 | png 174, jpg 14 |
| `companies.imgext` | `varchar(50)` | 150 | png 143, jpg 3, gif 3, webp 1 |
| `game_galleries.image_ext` | `varchar(11)` | 118 | jpg 118 |
| `individuals.imgext` | `varchar(50)` | 75 | jpg 54, png 20, webp 1 |
| `magazine_issues.imgext` | `varchar(11)` | 66 | jpeg 66 |
| `media_scans.imgext` | `varchar(11)` | 43 | jpg 38, png 5 |
| `users.avatar_ext` | `varchar(11)` | 35 | jpg 19, png 12, gif 4 |
| `crews.logo` | `varchar(255)` | 8 | png 8 |

```sql
-- The census the table above is built from, per column:
SELECT IFNULL(imgext, '(NULL)') AS val, COUNT(*) FROM screenshots GROUP BY imgext ORDER BY 2 DESC;
```

### The three names

Nine of the twelve columns are called `imgext`. Three are not:

| Column | Recorded as |
|---|---|
| `game_galleries.image_ext` | `2026-08-31-column-name-consistency.md:634-637` — "the campaign deliberately leaves the two remaining outliers"; `2026-09-02-entity-names-and-pivot-order.md:1471` records it as **B5** |
| `users.avatar_ext` | the same 2026-08-31 sentence, as the second outlier |
| `crews.logo` | renamed from `crew_logo` by the 2026-08-31 sweep, which stripped table prefixes; the result names what the file is rather than what the column holds |

**All three become `imgext`.** The 2026-09-02 plan filed this under category B as
"type *and spelling* drift across nine `imgext` columns", which is this plan.
Each of the three is already being rewritten by this unit's enum conversion, so
the rename travels in the same migration.

`Company` already has the shape the other two take: the column is `imgext`
(`Company.php:15`) and the reading accessors are `file`, `path` and `logo`
(`:27,32,37`). `$developer->logo` on a game page reads that accessor, not a
column, and is unaffected by any rename here.

The code each rename reaches:

- **`game_galleries.image_ext`** — nothing.
  `grep -rn "image_ext" app resources routes` returns no application reference;
  its seven hits are historical migrations, which are not edited.
- **`users.avatar_ext`** — 21 application references: `User.php:28,74,75`,
  `Auth/UserController.php:39,41,42`, `Admin/User/UserController.php:47,57,111,112`,
  `Livewire/Admin/UsersTable.php:40`,
  `resources/views/auth/card_profile.blade.php:20,21`,
  `resources/views/layouts/nav.blade.php:55,56,57`, plus
  `database/factories/UserFactory.php:38` and four assertions in
  `tests/Feature/Public/ProfileTest.php:185,201,208,215`.
  `User::getAvatarAttribute()` keeps its name and reads `$this->imgext`; its
  `!== ''` guard at `:74` becomes redundant once this unit's cleanup nulls the
  272 empty strings, and is removed with it.
- **`crews.logo`** — 11 application references: `Crew.php:19`,
  `Admin/Menus/MenuCrewController.php:217,229,240`,
  `Livewire/Admin/CrewsTable.php:31,32` — `:31` guards on the value, `:32`
  builds the `<img>` `src` from it —
  `resources/views/admin/menus/crews/card_logo.blade.php:29`,
  `database/factories/CrewFactory.php:19` and three assertions in
  `tests/Feature/Admin/Menus/MenuCrewTest.php:170,179,194`.
  `Crew::getLogoFileAttribute()` keeps its name and reads `$this->imgext`.
  The `logo` file input (`card_logo.blade.php:25`,
  `MenuCrewController.php:214-215`) is an HTTP field name, not a column, and
  keeps its name.

The `images/crew_logos/` and `images/user_avatars/` directories keep their
names. `2026-09-02-entity-names-and-pivot-order.md` ("The storage directories")
already ruled that files on disk are a separate inventory from tables and
columns.

**Wherever `jpg` is a member, `jpeg` is a member.** The two spellings name one
format, and `magazine_issues` stores `jpeg` for all 66 of its rows while every
other column stores `jpg`. The split has a single cause: those 66 rows were
written by `MagazineIssuesController::fetchImage()`, which splits `jpeg` out of
an `image/jpeg` response header, while every other column is written from
`UploadedFile::extension()`, which maps `image/jpeg` to `jpg` and can never
produce `jpeg` (Unit 1, "The upload sites"). Pairing the two spellings is what
lets this plan convert `magazine_issues` without renaming its 66 `N.jpeg` files
on disk.

**`game_galleries.image_ext` is renamed and converted with the rest.** The
[entity names and pivot order](2026-09-02-entity-names-and-pivot-order.md) plan
kept `game_galleries` as a historical record table with no model —
`grep -rn "game_galler\|GameGaller" app resources routes` returns nothing — and
renamed the table itself in its Unit 9 while deferring the column's spelling to
category B. Nothing reads the column, so both changes are free; leaving it would
make it the only extension column out of step on both axes. Its enum is
`'png','jpg','jpeg'`, matching `game_release_scans`, which holds the same kind
of image.

The twelve enums, under the names they carry after the rename:

| Column | Enum |
|---|---|
| `screenshots.imgext` | `'png','jpg','jpeg','gif','bmp','webp','zip','pdf','txt','bin','st','msa','stx','raw','scp'` |
| `menu_disk_screenshots.imgext` | `'png','jpg','jpeg','bmp'` |
| `game_release_scans.imgext` | `'png','jpg','jpeg'` (unchanged) |
| `news_images.imgext` | `'png','jpg','jpeg','gif','webp'` |
| `links.imgext` | `'png','jpg','jpeg'` |
| `companies.imgext` | `'png','jpg','jpeg','gif','webp'` |
| `individuals.imgext` | `'png','jpg','jpeg','webp'` |
| `magazine_issues.imgext` | `'png','jpg','jpeg'` |
| `media_scans.imgext` | `'jpg','jpeg','png'` |
| `crews.imgext` (was `logo`) | `'png','jpg','jpeg','gif','webp'` |
| `users.imgext` (was `avatar_ext`) | `'png','jpg','jpeg','gif'` |
| `game_galleries.imgext` (was `image_ext`) | `'png','jpg','jpeg'` |

`screenshots.imgext` carries the fifteen members because the public
game-submission form writes it and contributors use that form to send disk
images and archives. Of its fourteen non-image rows, four are disk images (an
MSA, an STX mislabelled `.bin`, and two `.st` images inside zips), seven are
zips of game-map PNGs, one is a 29-page PDF, one is a text file, and one
(`id` 30376) references a file that is not on disk. Every one of those values is
an enum member, so no row changes and nothing is deleted.

### The two rows that block the conversion

`users.avatar_ext` holds 272 empty strings, which is not a member of any enum:

```sql
SELECT COUNT(*) FROM users WHERE avatar_ext = '';   -- 272 on 2026-09-11
```

`menu_disk_screenshots` holds one `zip` row, `id` 3882 on `menu_disk_id` 6796,
and `zip` is not a member of that column's enum. The file is
`images/menu_screenshots/3882.zip` and contains `AWSM30_.MSA` — a disk image
uploaded through the screenshot form, where menu disks already have a dump
upload of their own.

**The row is deleted and the file is left on disk.** Nothing is lost: the MSA
stays at `images/menu_screenshots/3882.zip` for an admin to ingest through the
dump upload, and the directory already holds one such unreferenced file
(`3900.zip`, containing `DD099V03.MSA`, with no row at all). Setting `imgext` to
`NULL` instead would leave a row that cannot reconstruct its own filename through
`Screenshot::getFileAttribute()`.

### The migrations

`2026_09_11_100000_extension_column_cleanup.php`:

- `UPDATE users SET avatar_ext = NULL WHERE avatar_ext = ''`.
- `DELETE FROM menu_disk_screenshots WHERE id = 3882`.
- `down()` restores neither. The empty strings are indistinguishable from the
  NULLs already present, and the deleted row's id would not be reused.

`2026_09_11_100050_extension_columns_rename.php`: three `renameColumn` calls —
`game_galleries.image_ext`, `users.avatar_ext` and `crews.logo` all to `imgext`.
`down()` reverses all three. No table gains a duplicate: none of the three
already carries an `imgext`.

```sql
-- 0 rows before the rename. Any of the three tables already holding `imgext`.
SELECT table_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'imgext'
  AND table_name IN ('game_galleries', 'users', 'crews');
```

`2026_09_11_100100_extension_columns_to_enums.php`: twelve `->change()` calls,
one per column under its new name, each declaring that column's enum, nullable
exactly as it is today. `down()` restores the six original types from the table
above.

Every column is `utf8mb4_unicode_ci`, which is case-insensitive, so `JPG` sent
by a client matches the `jpg` member and is stored as `jpg`. Unit 1's
`strtolower()` makes that explicit rather than relying on it.

### Acceptance

```sql
-- The extension census: 12 rows, every column_name `imgext`, every data_type `enum`.
SELECT table_name, column_name, data_type, column_type
FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'imgext'
ORDER BY table_name;

-- The old-name census: 0 rows.
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND column_name IN ('image_ext', 'avatar_ext')
  OR (table_schema = DATABASE() AND table_name = 'crews' AND column_name = 'logo');
```

```sql
-- The jpg/jpeg pairing: 0 rows. Any enum listing one spelling without the other.
SELECT table_name, column_name, column_type
FROM information_schema.columns
WHERE table_schema = DATABASE() AND data_type = 'enum'
  AND (column_type LIKE "%'jpg'%") <> (column_type LIKE "%'jpeg'%");
```

```sql
-- No row was lost: 28,727 screenshots, 3,956 menu disk screenshots, 0 empty strings.
SELECT (SELECT COUNT(*) FROM screenshots)           AS screenshots,
       (SELECT COUNT(*) FROM menu_disk_screenshots) AS menu_disk_screenshots,
       (SELECT COUNT(*) FROM users WHERE imgext = '') AS empty_imgext,
       (SELECT COUNT(*) FROM crews WHERE imgext IS NOT NULL)          AS crew_logos,   -- 8
       (SELECT COUNT(*) FROM game_galleries WHERE imgext IS NOT NULL) AS gallery_rows; -- 118
```

`grep -rn "avatar_ext\|image_ext\|->logo\b" app resources database/factories tests`
returns only the `Company::logo` accessor reads. On 2026-09-11 it returns 41
lines: 21 for `avatar_ext`, 10 for `crews.logo`, and 10 reading the
`Company::logo` accessor, which this unit leaves in place. `->logo` does not
reach `CrewFactory.php:19`, which writes `'logo' => null` as an array key; it is
renamed with the rest and the suite is what gates it.

**No PHPUnit test can gate this unit.** The suite runs on SQLite in-memory
(`phpunit.xml:47-48`), which has no `ENUM` type: Laravel maps it to a varchar
and an unlisted value inserts without complaint. The schema queries above, run
against MariaDB, are the gate for the enum itself; Unit 1's eighteen tests are
what cover rejection, and they pass on SQLite because they exercise the
application rule.

## Unit 3 — the dump formats become lowercase

### The two columns

`dumps.format` and `menu_disk_dumps.format` are both
`enum('STX','MSA','RAW','SCP','ST')`, the only uppercase format values in the
schema. Unit 2 gives `screenshots.imgext` lowercase `st`, `msa`, `stx`, `raw`
and `scp` members, so without this unit the same five formats are spelled two
ways in three columns.

```sql
SELECT 'dumps' AS t, IFNULL(format,'(NULL)') AS f, COUNT(*) AS n FROM dumps GROUP BY format
UNION ALL
SELECT 'menu_disk_dumps', IFNULL(format,'(NULL)'), COUNT(*) FROM menu_disk_dumps GROUP BY format;
```

| Table | MSA | STX | ST | Total |
|---|---|---|---|---|
| `dumps` | 143 | 68 | 6 | 217 |
| `menu_disk_dumps` | 3,820 | — | 5 | 3,825 |

4,042 rows. `RAW` and `SCP` are declared members with no rows.

**The stored value becomes lowercase and the display uppercases it.** The column
holds a filename suffix — `DumpHelper.php:70` and `:112` already build paths
from it, one of them wrapping the value in `strtolower()` to do so — while the
two places a user reads it want `STX`.

### The changes

- The migration rewrites both enums to `'stx','msa','raw','scp','st'` and
  lowercases the 4,042 stored values. `down()` reverses both.
- `Dump::FORMATS` and `MenuDiskDump::EXTENSIONS` become lowercase and identical,
  `RAW` included (Unit 1 adds it).
- `DumpHelper::detectFormat()` (`DumpHelper.php:23-49`) returns lowercase: the
  three sniffed literals `'SCP'`, `'MSA'` and `'STX'` at `:31,33,35`, and the
  fallback `strtoupper($ext)` at `:46`.
- `DumpHelper.php:70` drops the now-redundant `strtolower($dump->format)`.
  `:112` builds an HxC temp filename from `$dump->format` and follows the value
  down to lowercase; hxcfe sniffs the content, not the suffix.
- `ReleaseMediasDumpsController.php:41` compares `strtolower($ext)`.
- **`MenuDisksController::storeDump()` stores the value, not just compares it.**
  Four sites build what lands in `menu_disk_dumps.format`, and all four
  uppercase today:
  `:166` `$clientExt = strtoupper($dumpFile->getClientOriginalExtension())`,
  `:197` `$zipEntryExt = strtoupper(pathinfo(...))`,
  `:207` `$dumpFormat = strtoupper($zipEntryExt)` (already redundant, and
  deleted rather than lowercased) and `:213` `$dumpFormat = $clientExt`.
  `:166` and `:197` become `strtolower()`; leaving them is a strict-mode error
  on every menu-disk dump upload once the enum is lowercase, and the suite
  cannot catch it — on SQLite the enum is a varchar, the same reason Unit 2
  gives for why no PHPUnit test gates the enums.
- Both `'ZIP'` literals follow `$clientExt` down: the guard at `:176` and the
  branch at `:182`. Missing `:182` is the quieter failure of the two — the
  ZIP branch simply stops being taken, so every ZIP upload falls through to
  `:213` and tries to store `zip`.
- The two flash messages at `:177` and `:200` interpolate those values, so
  "Unsupported file extension: TXT" becomes `txt`; `MenuDisksTest.php:214`
  asserts that string.
- `GenerateDumpTrackPictures.php:34` becomes `whereIn('format', ['stx', 'scp'])`.
- Three templates render a stored value and uppercase for display:
  `resources/views/games/releases/card_media.blade.php:41`,
  `resources/views/admin/games/games/releases/medias/card_media.blade.php:166`
  and `resources/views/admin/menus/disks/card_dump.blade.php:24`. `:143` on the
  admin release card renders the `Dump::FORMATS` constant rather than a stored
  value, and is covered by that constant becoming lowercase — the accepted-format
  hint reads `stx, msa, ...` unless it uppercases too.
- `AdminStatisticsHelper::dumpsByFormat()` (`:384-386`) uppercases its chart labels,
  which `resources/views/admin/others/statistics/card_catalogue.blade.php:25`
  renders.
- `DumpFactory` and `MenuDiskDumpFactory` produce lowercase; the assertions in
  `MenuDisksTest`, `ReleaseMediaTest`, `DumpHelperTest`, `DumpHelperZipTest` and
  `MaintenanceCommandsTest` follow.

The two historical migrations that create these enums uppercase
(`2020_10_17_161643_create_dump_table.php:19`,
`2020_12_20_141639_create_new_menu_structure.php:72`) are not edited. They run
before this one on `migrate:fresh` and are correct as written.

### Acceptance

```sql
-- The format census: 2 rows, both column_type enum('stx','msa','raw','scp','st').
SELECT table_name, column_type FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'format'
  AND table_name IN ('dumps', 'menu_disk_dumps');

-- No uppercase value survives: 0 rows.
SELECT 'dumps' AS t, COUNT(*) FROM dumps WHERE BINARY format <> LOWER(format)
UNION ALL SELECT 'menu_disk_dumps', COUNT(*) FROM menu_disk_dumps WHERE BINARY format <> LOWER(format);
```

`grep -rnE "'(STX|MSA|RAW|SCP|ST)'" app resources tests database/factories` returns
nothing. On 2026-09-11 it returns 29 lines across 11 files.

`php artisan test` passes. The enum itself is not gated by the suite, for the
SQLite reason given in Unit 2, but the lowercase values and every call site are.

## Unit 4 — the `name` columns

### The columns

42 columns are called `name`. They carry seven distinct types. The longest value
in any of them is 81 characters, in `games.name`.

```sql
SELECT column_type, COUNT(*) FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'name'
GROUP BY column_type ORDER BY 2 DESC;
```

| Type | Count | Tables |
|---|---|---|
| `varchar(64)` | 10 | `engines`, `languages`, `locations`, `magazine_index_types`, `menu_disk_conditions`, `menu_sets`, `menu_software_content_types`, `news_images`, `programming_languages`, `sound_hardware` |
| `varchar(255)` | 8 | `companies`, `crews`, `game_releases`, `games`, `individuals`, `menu_software`, `resolutions`, `systems` |
| `varchar(128)` | 8 | `categories`, `game_akas`, `game_series`, `genres`, `individual_roles`, `link_submissions`, `links`, `magazines` |
| `varchar(45)` | 6 | `controls`, `copy_protections`, `disk_protections`, `emulators`, `memories`, `ports` |
| `varchar(50)` | 4 | `andreas`, `article_types`, `developer_roles`, `tos` |
| `varchar(250)` | 4 | `enhancements`, `game_progress_systems`, `media_scan_types`, `media_types` |
| `varchar(256)` | 2 | `game_release_akas`, `trainer_options` |

**Every `name` becomes `varchar(255)`.** It is the largest existing group, it is
what `Schema::string()` produces without a length, and no column shrinks below
its current capacity — 32 widen, eight are already `varchar(255)`, and two narrow
by one character from `varchar(256)`, where `game_release_akas.name` at 37
characters and `trainer_options.name` at 15 are the longest values. Nothing needs
a pre-flight length check.

Nullability is not touched. Nineteen of the 42 are nullable, including lookup
tables where a null label is meaningless, and changing that is a question about
each table's data rather than about its type.

### The migration

`2026_09_11_100300_name_columns_to_255.php`: 42 `->change()` calls in one
migration, each `$table->string('name', 255)` with the column's existing
nullability. `down()` restores the seven types from the table above.

### Acceptance

```sql
-- The name census: 42 rows, one distinct column_type, varchar(255).
SELECT column_type, COUNT(*) FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'name'
GROUP BY column_type;
```

```sql
-- No value was truncated: 81, unchanged.
SELECT MAX(CHAR_LENGTH(name)) FROM games;
```

`php artisan test` passes.

## Unit 5 — the remaining string widths

### The `varchar(256)` columns

Fourteen columns are `varchar(256)`, one past the 255 that 26 other columns use.

```sql
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_type = 'varchar(256)';
```

Twelve are converted: `changelogs.section`, `changelogs.section_name`,
`changelogs.sub_section`, `changelogs.sub_section_name`, `changelogs.action`,
`engines.description`, `game_release_akas.name`, `magazine_issues.label`,
`media.label`, `programming_languages.description`, `sound_hardware.description`,
`trainer_options.name`. The longest value across all twelve is 128 characters,
in `changelogs.section_name` and `changelogs.sub_section_name`.

`game_release_akas.name` and `trainer_options.name` are also in Unit 4 and reach
`varchar(255)` there; whichever unit ships first, the other is a no-op on those
two columns.

`database_changes.implementation_state` and `database_changes.update_filename`
are the other two and are left — see "Out of scope".

### The oversized columns

Four columns declared as a `TEXT` variant hold a short single-line string:

| Column | Type | Longest value | Becomes |
|---|---|---|---|
| `articles.title` | `mediumtext` | 85 | `varchar(255)` |
| `spotlights.link` | `mediumtext` | 65 | `varchar(255)` |
| `magazine_issues.archiveorg_url` | `text` | 85 | `varchar(255)` |
| `magazine_issues.alternate_url` | `text` | 39 | `varchar(255)` |

`mediumtext` is 16 MB. A title and a URL are neither long nor searched as
prose, and a `TEXT` variant cannot carry a plain index.

### The undersized columns

Two columns holding a body of prose are `TEXT`, which is 65,535 **bytes**, not
characters, on a `utf8mb4` charset. Every other body column in the schema is
`mediumtext`.

| Column | Longest value | Share of ceiling |
|---|---|---|
| `interviews.text` | 47,149 bytes | 72% |
| `menu_disks.scrolltext` | 17,872 bytes | 27% |

Both become `mediumtext`, which makes all 35 body columns in the schema uniform.
The count holds steady across this unit: `articles.title` and `spotlights.link`
leave `mediumtext` in the same migration that these two join it.

`2026-08-24-main-text-table-merge.md` (Decision 3, hazard 5) measured
`interview_text` at 47,149 bytes and kept `TEXT`, on the grounds that strict
mode (`config/database.php:58`) makes an over-long save a loud `SQLSTATE 22001`
rather than a silent truncation, and asked for a re-measure "if interviews start
getting longer". Re-measured on 2026-09-11: 47,149 bytes, unchanged. The
widening happens here on the consistency argument rather than the safety one.

### The migration

`2026_09_11_100400_string_column_widths.php`: twelve `varchar(256)` to
`varchar(255)`, four `TEXT` variants to `varchar(255)`, two `TEXT` to
`mediumtext`. `down()` restores all eighteen.

The four oversized columns are the only ones that could truncate. `up()` asserts
first, and fails the migration rather than cutting a value:

```php
foreach ([['articles','title'], ['spotlights','link'],
          ['magazine_issues','archiveorg_url'], ['magazine_issues','alternate_url']] as [$t, $c]) {
    $over = DB::table($t)->whereRaw("CHAR_LENGTH(`$c`) > 255")->count();
    if ($over > 0) {
        throw new RuntimeException("$t.$c has $over rows longer than 255 characters");
    }
}
```

### Acceptance

```sql
-- The width census: 2 rows, both database_changes.
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_type = 'varchar(256)';

-- The short-string census: 4 rows, every column_type varchar(255).
SELECT table_name, column_name, column_type FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (table_name, column_name) IN (
    ('articles','title'), ('spotlights','link'),
    ('magazine_issues','archiveorg_url'), ('magazine_issues','alternate_url'));

-- The body-column census: 0 rows. Any body column still narrower than mediumtext.
-- `data_type` is the exact type name, so `tinytext` has to be named too: there
-- are none today, and the census is what keeps it that way.
SELECT table_name, column_name, data_type FROM information_schema.columns
WHERE table_schema = DATABASE() AND data_type IN ('tinytext', 'text');
```

```sql
-- Nothing truncated.
SELECT (SELECT MAX(CHAR_LENGTH(title)) FROM articles)          AS article_title,   -- 85
       (SELECT MAX(LENGTH(text)) FROM interviews)              AS interview_text,  -- 47149
       (SELECT MAX(LENGTH(scrolltext)) FROM menu_disks)        AS scrolltext;      -- 17872
```

`php artisan test` passes.

## Unit 6 — the numeric columns

### The columns

Four numeric columns are typed for something other than what they hold.

**`dumps.sha512` is `varchar(128)`** while `users.sha512_password` and
`users.salt` are `char(128)`. All three hold a hex SHA-512, and every value in
all three is exactly 128 characters:

```sql
SELECT 'dumps.sha512' AS col, MIN(CHAR_LENGTH(sha512)), MAX(CHAR_LENGTH(sha512)) FROM dumps
UNION ALL SELECT 'users.sha512_password', MIN(CHAR_LENGTH(sha512_password)), MAX(CHAR_LENGTH(sha512_password)) FROM users
UNION ALL SELECT 'users.salt', MIN(CHAR_LENGTH(salt)), MAX(CHAR_LENGTH(salt)) FROM users;
```

It becomes `char(128)`, matching the other two.

**`dumps.size` is `int(50)` and `menu_disk_dumps.size` is `int(11)`**, both
signed, both holding a byte count. `int(50)` is a display width MySQL 8
deprecated and MariaDB ignores; neither column can hold a negative value. Both
become `int unsigned`. Measured maxima: 1,901,002 and 935,254, against an
`int unsigned` ceiling of 4,294,967,295.

**`menu_disk_contents.position` is `tinyint(4)`**, signed, ceiling 127. The
largest position stored is 106, on `menu_disk_id` 1421, which has 106 entries:

```sql
SELECT MAX(position) FROM menu_disk_contents;                                   -- 106
SELECT menu_disk_id, COUNT(*) c FROM menu_disk_contents GROUP BY menu_disk_id
ORDER BY c DESC LIMIT 1;                                                        -- 1421, 106
```

It becomes `smallint unsigned`. This is the only column in the plan with a live
failure mode: a menu disk with 128 entries fails to insert its last row under
strict mode, and menu disks with over a hundred entries already exist.

**`users.permission` is `int(2)`**, holding `1` for an administrator and `2` for
a user across 767 of the 771 rows in `users`, with 4 NULLs:

```sql
SELECT permission, COUNT(*) FROM users GROUP BY permission;   -- NULL: 4, 1: 23, 2: 744
```

It becomes `tinyint unsigned`.
`User::PERMISSION_ADMIN` and `User::PERMISSION_USER` keep their values, so
`AppServiceProvider.php:41`, `Admin.php:23`, `UsersTable.php:80`,
`RegisterController.php:78` and `AdminStatisticsHelper.php:142` are unchanged.

### The migration

`2026_09_11_100500_numeric_column_types.php`: five `->change()` calls. `down()`
restores `varchar(128)`, `int(50)`, `int(11)`, `tinyint(4)` and `int(2)`.

No value changes. `int(50)` to `int unsigned` and `tinyint(4)` to
`smallint unsigned` both widen the range; `varchar(128)` to `char(128)` is a
no-op for values that are already exactly 128 characters, and MariaDB
right-pads nothing because none is short.

### Acceptance

```sql
-- The numeric census: 5 rows with the types below.
SELECT table_name, column_name, column_type FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (table_name, column_name) IN (
    ('dumps','sha512'), ('dumps','size'), ('menu_disk_dumps','size'),
    ('menu_disk_contents','position'), ('users','permission'))
ORDER BY table_name, column_name;
```

| Column | Expected |
|---|---|
| `dumps.sha512` | `char(128)` |
| `dumps.size` | `int(10) unsigned` |
| `menu_disk_dumps.size` | `int(10) unsigned` |
| `menu_disk_contents.position` | `smallint(5) unsigned` |
| `users.permission` | `tinyint(3) unsigned` |

```sql
-- No value changed.
SELECT (SELECT COUNT(*) FROM dumps WHERE CHAR_LENGTH(sha512) <> 128) AS bad_hashes,   -- 0
       (SELECT MAX(size) FROM dumps)                                 AS max_dump,      -- 1901002
       (SELECT MAX(position) FROM menu_disk_contents)                AS max_position,  -- 106
       (SELECT COUNT(*) FROM users WHERE permission = 1)             AS admins;
```

`php artisan test` passes.

## Unit 7 — the boolean columns

### The columns

Nine columns hold a true/false fact — the nine the
[entity names and pivot order](2026-09-02-entity-names-and-pivot-order.md) plan
counted when it deferred them. Seven are `tinyint(1)`; two are not. Two of the
nine are cast to `boolean` on their model; seven are not.

| Column | Type | Cast |
|---|---|---|
| `articles.draft` | `tinyint(1)` | no |
| `interviews.draft` | `tinyint(1)` | no |
| `reviews.draft` | `tinyint(1)` | no |
| `links.inactive` | `tinyint(1)` | no |
| `users.inactive` | `tinyint(1)` | no |
| `dumps.track_picture` | `tinyint(1)` | `boolean` |
| `game_releases.hd_installable` | `tinyint(1)` | `boolean` |
| `reviews.submission` | `int(1)` | no |
| `game_submissions.game_done` | `char(1)` | no |

**`reviews.submission` becomes `tinyint(1)`.** It holds 0 and 1 across 127 rows
and sits beside `reviews.draft`, which is already `tinyint(1)`.
`Review::REVIEW_PUBLISHED` is `0` and `REVIEW_UNPUBLISHED` is `1`, and both keep
their values.

Seven of the eight comparison sites are `where()` calls that compare in SQL and
are unaffected. The eighth is not: `GameController.php:85` is
`$review->submission !== Review::REVIEW_PUBLISHED`, a PHP strict comparison
inside the `reject()` that filters the game page's review list. Once the cast
lands, both `false !== 0` and `true !== 0` are `true` and every review is
rejected from every game page. It becomes `$review->submission`, which reads
the flag as what it now is — the constants stay for the seven query sites.

**`game_submissions.game_done` becomes `reviewed`, `tinyint(1)` NOT NULL
DEFAULT 0.** It is `char(1)` holding the strings `'1'` for reviewed (2,868 rows)
and `'2'` for new (5 rows), through `GameSubmission::SUBMISSION_REVIEWED = '1'`
and `SUBMISSION_NEW = '2'`, and `GameSubmissionsTable.php:56` already renders it
as a `BooleanColumn` headed "Reviewed". The values invert: `'1'` becomes `true`,
`'2'` becomes `false`.

```sql
SELECT game_done, COUNT(*) FROM game_submissions GROUP BY game_done;  -- '1': 2868, '2': 5
```

`database/seeders/E2ESeeder.php:320` writes `'N'`, which matches neither
constant and renders as not-reviewed by falling through the comparison. It
becomes `false`.

### The casts

The seven uncast columns gain `'boolean'` in their model's `$casts`.

**`inactive` is cast last and with its comparisons.** `User::ACTIVE` is `0` and
`User::INACTIVE` is `1`, and five places compare the attribute in PHP with
`===` or `!==`. All five break, in both directions:

- `app/Models/User.php:49` — `$this->inactive === User::ACTIVE`. `false === 0`
  is `false`, so `isActive()` returns `false` for every active user.
- `app/Providers/Auth/UserProvider.php:34` — `$user->inactive === User::ACTIVE`,
  the same failure on the login path: nobody could log in.
- `app/Http/Middleware/EnsureEmailIsVerifiedAndAccountIsActive.php:31` —
  `$request->user()->inactive === User::INACTIVE`. `true === 1` is `false`, so
  this one fails open: a disabled account is no longer held back. The class is
  the `verified` alias (`bootstrap/app.php:27`) guarding `routes/web.php:50` and
  `routes/admin.php:67`, and
  `tests/Feature/Public/AuthTest.php:354` covers it.
- `resources/views/admin/users/users/card_edit.blade.php:109` —
  `@if (old('active', $user->inactive) !== 1) checked @endif`. `true !== 1` is
  `true`, so the Active box shows ticked for a disabled account — and
  `Admin/User/UserController.php:62` writes `$request->active ? '0' : '1'`
  back, so opening an inactive user and saving reactivates them. This is the
  only one of the five that corrupts data rather than a check.
- `resources/views/links/card_links.blade.php:36` — `$link->inactive === 1`, on
  the public links page. The "Appears to be inactive" warning stops appearing.
  `Link::inactive` does have a strict comparison; it is just not in `app/`.

Each becomes a plain truth test — `! $this->inactive`, `$user->inactive`,
`old('active', ! $user->inactive)` — rather than a comparison against a
constant. The query builder calls in
`app/Http/Middleware/OnlineUsers.php:26,32` and
`AdminStatisticsHelper.php:139-141` are unaffected: they compare in SQL, not in
PHP, and they are `where()` calls rather than `===` in the first place.
`NonDraftScope.php:16` already uses `where('draft', false)`, which works either
way.

The pattern behind all five is that a cast changes what a *comparison* means,
not what a *read* returns, and three of the five are in Blade. An inventory
built by grepping the column name finds the reads and misses these; the gate the
acceptance section adds greps the column name **next to a `===` or `!==`**,
which is the shape that breaks.

### The migrations

`2026_09_11_100600_review_submission_boolean.php`: `reviews.submission` from
`int(1)` to `tinyint(1)`, values unchanged. `down()` restores `int(1)`.

`2026_09_11_100700_game_done_to_reviewed.php`:

- Adds `reviewed` `tinyint(1)` NOT NULL DEFAULT 0.
- `UPDATE game_submissions SET reviewed = (game_done = '1')`.
- Drops `game_done`.
- `down()` re-adds `game_done` `char(1)` and writes `'1'` or `'2'` back. The
  five `'N'` rows the seeder produces are not restored as `'N'`; no production
  row holds that value.

### The changes

- `GameSubmission::SUBMISSION_NEW` and `SUBMISSION_REVIEWED` are removed. The
  sites that use them — `GameSubmissionController.php:43,57`,
  `GameController.php:206`, `GameSubmissionsTable.php:57,88`,
  `admin/games/submissions/card_show.blade.php:15,67` — read and write
  `reviewed` as a boolean. `GameSubmissionsTable.php:56` keeps its
  `BooleanColumn` and loses the `setCallback()` at `:57`, which only existed to
  turn `'1'` into `true`.
- `E2ESeeder.php:320` writes `'reviewed' => false`.
- Seven columns gain a `'boolean'` cast across six models — `Review` gains two,
  for `draft` and `submission` — and the five PHP comparisons above change with
  them.
- **The test suite pins the old shapes and is updated in the same commit.**
  Twelve lines across five files still name the removed constants or the old
  column:

  | File | Lines |
  |---|---|
  | `tests/Feature/Admin/Games/GameSubmissionTest.php` | 37, 44, 91, 100, 106, 147 |
  | `tests/Feature/Admin/Tables/AdminTablesTest.php` | 199, 205, 221 |
  | `tests/Feature/Public/GamePageTest.php` | 367 |
  | `tests/Feature/Admin/Games/GameControllerTest.php` | 283 |
  | `tests/Feature/Admin/AdminRenderTest.php` | 103 |

  `GameSubmissionTest.php:37` and `AdminTablesTest.php:199` are helper
  signatures, `string $done` and `int $done`, that become `bool $reviewed = false`
  — the `int` one is already wrong today, coerced from a string constant.

  Five more assertions compare a cast attribute against an int constant with
  `assertSame`, which is type-strict and fails on `0` against `false`:

  | File | Line | Assertion |
  |---|---|---|
  | `tests/Feature/Public/GamePageTest.php` | 80 | `assertSame(Review::REVIEW_PUBLISHED, ...->submission)` |
  | `tests/Feature/Public/ReviewPagesTest.php` | 190 | `assertSame(Review::REVIEW_UNPUBLISHED, ...)` |
  | `tests/Feature/Admin/Reviews/ReviewsControllerTest.php` | 77 | `assertSame(Review::REVIEW_PUBLISHED, ...)` |
  | `tests/Feature/Admin/Reviews/ReviewsControllerTest.php` | 105 | `assertSame(Review::REVIEW_UNPUBLISHED, ...)` |
  | `tests/Feature/Public/AuthTest.php` | 102 | `assertSame(User::ACTIVE, $user->inactive)` |

  Each becomes `assertFalse`/`assertTrue` on the attribute. `ReviewFactory.php:23,42`
  and the `where()` calls keep the constants, which still name the stored values.

### Acceptance

```sql
-- The boolean census: 9 rows, every column_type tinyint(1), and no game_done.
SELECT table_name, column_name, column_type FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND column_name IN ('draft','inactive','track_picture','hd_installable',
                      'submission','reviewed','game_done')
ORDER BY table_name, column_name;
```

```sql
-- The values carried over: 2,868 reviewed, 5 not.
SELECT reviewed, COUNT(*) FROM game_submissions GROUP BY reviewed;
```

`grep -rn "'boolean'" app/Models/*.php` returns nine lines. On 2026-09-11 it
returns two.

```bash
# The old name and constants: nothing. On 2026-09-11, 23 lines across 11 files.
grep -rn --exclude-dir=migrations "SUBMISSION_NEW\|SUBMISSION_REVIEWED\|game_done" app resources database tests

# Every strict comparison on a boolean attribute: one line. On 2026-09-11, nine.
grep -rnE "(draft|inactive|track_picture|hd_installable|submission|reviewed)\b.{0,40}(===|!==)|(===|!==).{0,40}\b(draft|inactive|track_picture|hd_installable|submission|reviewed)\b" app resources
```

The `--exclude-dir=migrations` is what makes the first gate passable:
`2020_10_17_161643_create_game_submitinfo_table.php:22` creates `game_done` and
is a historical migration, which this plan does not edit. `database/seeders`
stays in scope, because `E2ESeeder.php:320` is renamed here.

The second gate is the one this unit is short of without it. Of its nine lines
today, five are the breakages above and two are the `card_show.blade.php` sites
already in the changes list; the one that survives is
`ReleaseSystemInfoController.php:76`, `$request->hd === 'true'`, which compares
a request value rather than the attribute and is correct as it stands.
`resources` is in the gate's scope because three of the seven real hits are in
Blade, which is exactly where grepping for the column name alone misses them.

`php artisan test` passes, including `tests/Feature/Public/AuthTest.php` — the
login path is what the `users.inactive` cast would break, and it is covered at
`:321` and `:354`.

## Verification

Run against MariaDB after all seven units. Each is the end-state form of a
per-unit gate above.

```sql
-- Extensions: 12 rows, every column_name `imgext`, every data_type `enum`, and
-- every column_type the member list Unit 2 gives that column.
SELECT table_name, column_name, data_type, column_type FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'imgext';

-- Old extension names: 0 rows.
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (column_name IN ('image_ext','avatar_ext')
       OR (table_name = 'crews' AND column_name = 'logo'));

-- jpg/jpeg pairing: 0 rows.
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND data_type = 'enum'
  AND (column_type LIKE "%'jpg'%") <> (column_type LIKE "%'jpeg'%");

-- Formats: 2 rows, both enum('stx','msa','raw','scp','st').
SELECT table_name, column_type FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'format';

-- Names: 1 row, varchar(255), count 42.
SELECT column_type, COUNT(*) FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_name = 'name' GROUP BY column_type;

-- Widths: 2 rows, both database_changes.
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND column_type = 'varchar(256)';

-- Body columns: 0 rows.
SELECT table_name, column_name FROM information_schema.columns
WHERE table_schema = DATABASE() AND data_type IN ('tinytext', 'text');

-- Booleans: 9 rows, every column_type tinyint(1).
SELECT table_name, column_name, column_type FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND column_name IN ('draft','inactive','track_picture','hd_installable','submission','reviewed');
```

```bash
# Every case-preserving extension read is lowercased (DumpHelper.php:45
# lowercases its path instead, which is equivalent).
grep -rnE "getClientOriginalExtension\(\)|PATHINFO_EXTENSION|File::extension\(" app --include=*.php

grep -rnE "'(STX|MSA|RAW|SCP|ST)'" app resources tests database/factories  # nothing
grep -rn "'boolean'" app/Models/*.php                               # 9 lines
grep -rn --exclude-dir=migrations "SUBMISSION_NEW\|SUBMISSION_REVIEWED\|game_done" app resources database tests  # nothing

# Strict comparisons on a boolean attribute: only ReleaseSystemInfoController.php:76,
# which compares a request value.
grep -rnE "(draft|inactive|track_picture|hd_installable|submission|reviewed)\b.{0,40}(===|!==)|(===|!==).{0,40}\b(draft|inactive|track_picture|hd_installable|submission|reviewed)\b" app resources

./vendor/bin/sail artisan test
```

`php artisan migrate:fresh --seed` followed by `php artisan migrate:rollback --step=9`
and `php artisan migrate` returns the same schema, with the two exceptions the
cleanup migration records: the 272 empty `avatar_ext` strings return as NULL and
`menu_disk_screenshots` row 3882 does not return.

## Deploying

Units ship in order, one commit each, on `development` first. Unit 1 must reach
production before Unit 2: between them an unlisted extension is rejected by the
form, which is the behaviour both units want; the other way round it is a 500.

Unit 2's cleanup migration deletes a row and rewrites 272 values. Take the usual
database snapshot before it, because its `down()` restores neither.

Unit 3 changes what users see in three templates — the public release card, the
admin release card and the admin menu-disk card — plus the admin statistics
chart labels and the two menu-disk upload flash messages. All are display-only
and land in the same commit as the data change, so there is no window where a
page shows `stx`. The flash messages are the exception by design: they echo what
the user uploaded, and after this unit they say `txt` rather than `TXT`.

## Out of scope

Examined and deliberately left alone.

- **`game_galleries.game_description_gallery`** — the other column on that
  table, examined and left with a recorded reason by
  [column name consistency](2026-08-31-column-name-consistency.md). Renaming
  `image_ext` in Unit 2 does not reopen it. The table keeps its status as a
  historical record with no model: this plan changes two columns on it and adds
  no model, no route and no reader.
- **`database_changes.implementation_state` and `.update_filename`** — the
  CPANEL schema-change ledger, kept as a historical record with no model by
  the [schema consistency sweep](2026-08-26-schema-consistency-sweep.md) and the
  [column name consistency](2026-08-31-column-name-consistency.md) sweep. They
  keep `varchar(256)`, which is why the width census expects two rows rather
  than none.
- **NULL against empty string** — sixteen nullable string columns use both to
  mean absent. The largest are `game_releases.name` (14,661 NULL, 2,296 empty),
  `users.avatar_ext` (464 / 272), `sndhs.ripper` (386 / 206) and `users.email`
  (4 / 68). Only `users.avatar_ext` is normalised here, because its enum cannot
  accept an empty string. The rest is a data normalisation with an `UPDATE`
  behind each column and a question per column about which value the
  application should write.

  ```sql
  -- Re-measure: every nullable string column holding both.
  -- Generated per column as:
  SELECT COUNT(*) AS n, SUM(col IS NULL) AS nulls, SUM(col = '') AS empties FROM tbl;
  ```
- **`sndhs.year`** — `int(11)` holding a calendar year, with 14 rows outside
  `AdminStatisticsHelper::YEAR_MIN`/`YEAR_MAX` (1980/2030): one `0`, two `198`,
  nine `199` and two `2107`. Narrowing the type is a decision about those
  values first, and `YEAR` cannot hold any of them. The timestamp campaign
  already recorded this column as correctly typed for its role.
- **`changelogs.action`** — holds six values where `Changelog` defines three
  constants, because CPANEL wrote 146 `Add`, 12 `Delete shot` and 6 `Edit`
  rows. Making it an enum needs those 164 rows normalised first.
  [entity names and pivot order](2026-09-02-entity-names-and-pivot-order.md)
  reached the same conclusion. Its width is fixed in Unit 5; its type is not.
- **`menu_sets.sort_direction`** — already `enum('asc','desc')` NOT NULL with a
  default. Correct as it stands.
- **The all-NULL columns** — `sndhs.default_subtune` (10,371 rows, no value) and
  `magazine_issues.circulation` (69 rows, no value). Dropping a column is the
  [dead tables and columns](2026-08-28-dead-tables-and-columns.md) campaign's
  subject, not this one's.
- **The fourteen non-image `screenshots` rows** — inventoried in Unit 2, under
  "The twelve enums". Every value is an enum member there, so none is touched.
  Ingesting the disk images into `dumps` and unpacking the map archives into
  real screenshot rows is editorial work with no schema change in it.
- **The release dump upload's silent skip** —
  `ReleaseMediasDumpsController.php:41,75` wrap the store in
  `in_array(..., Dump::FORMATS)` with no `else`, so an unlisted file in a
  dropped ZIP disappears without a message. Unit 1 makes `Dump::FORMATS` agree
  with `MenuDiskDump::EXTENSIONS`, which is what stops a `.raw` dump being
  accepted for a menu disk and skipped for a release, but it does not give this
  path the flash-and-redirect the menu-disk one has
  (`MenuDisksController.php:176`, tested at `MenuDisksTest.php:214`). Turning a
  silent skip into a rejection changes what a bulk ZIP upload does — today a
  mixed archive stores what it can — and that is a question about the admin
  workflow rather than about a column type.
- **`int(11)` against `bigint(20) unsigned`** — the legacy tables use signed
  `int(11)` primary keys and the nine Laravel-created `menu*` tables plus
  `crew_menu_set`, `game_videos`, `game_votes`, `game_vs` and
  `magazine_index_types` — fourteen in all — use `bigint(20) unsigned`. No
  foreign key spans the two:

  ```sql
  -- 0 rows. Every FK matches its parent's type.
  SELECT k.table_name, k.column_name, c1.column_type, k.referenced_table_name, c2.column_type
  FROM information_schema.key_column_usage k
  JOIN information_schema.columns c1 ON c1.table_schema = k.table_schema
   AND c1.table_name = k.table_name AND c1.column_name = k.column_name
  JOIN information_schema.columns c2 ON c2.table_schema = k.table_schema
   AND c2.table_name = k.referenced_table_name AND c2.column_name = k.referenced_column_name
  WHERE k.table_schema = DATABASE() AND k.referenced_table_name IS NOT NULL
    AND c1.column_type <> c2.column_type;
  ```

  Converting the legacy keys would rewrite every foreign key in the schema.
- **Charset, collation and engine** — uniform already. All 114 tables are
  InnoDB and `utf8mb4_unicode_ci`, and all 165 string columns are
  `utf8mb4_unicode_ci`. Nothing to do.

  ```sql
  SELECT engine, table_collation, COUNT(*) FROM information_schema.tables
  WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
  GROUP BY engine, table_collation;   -- 1 row: InnoDB, utf8mb4_unicode_ci, 114
  ```
- **Temporal columns** — the
  [timestamp type consistency](2026-09-06-timestamp-type-consistency.md)
  campaign's subject, including `database_changes.execute_timestamp`, which it
  kept as `int(11)`.
- **`magazine_indices.score`** — `varchar(11)` holding `4/5`, `80%`, `3/5` and
  other free-text scores across 638 rows. It is genuinely a string; splitting it
  into a value and a scale is a data-modelling question.
- **Nullability** — nineteen `name` columns are nullable, as are most of the
  columns this plan retypes. Nullability is left exactly as found everywhere,
  including where a lookup table's label should plainly be NOT NULL.
- **Boolean column names** — `draft`, `inactive`, `track_picture` and
  `hd_installable` carry no prefix, and
  [entity names and pivot order](2026-09-02-entity-names-and-pivot-order.md)
  deferred adopting one across all of them. `game_done` is renamed in Unit 7
  because its type change rewrites every one of its call sites anyway; no
  prefix convention is adopted.
