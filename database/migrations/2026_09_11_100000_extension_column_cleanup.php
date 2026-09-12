<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Clear the two values that no image extension enum can accept, ahead of the
 * conversion that follows.
 *
 * users.avatar_ext holds 272 empty strings, which mean the same as the NULLs
 * beside them: no avatar. User::getAvatarAttribute() already tests for both.
 *
 * menu_disk_screenshots row 3882 holds `zip`, on menu disk 6796. The file is
 * images/menu_screenshots/3882.zip and contains AWSM30_.MSA - a disk image
 * uploaded through the screenshot form, where menu disks have a dump upload of
 * their own. The row goes and the file stays on disk for an admin to ingest
 * through that upload; the directory already holds one such unreferenced file
 * (3900.zip, with no row at all). Setting imgext to NULL instead would leave a
 * row that cannot reconstruct its own filename.
 *
 * down() restores neither. A restored empty string is indistinguishable from
 * the NULLs already there, and the deleted row's id would not be reused.
 *
 * See docs/plans/2026-09-11-column-type-consistency.md, Unit 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('avatar_ext', '')->update(['avatar_ext' => null]);

        DB::table('menu_disk_screenshots')->where('id', 3882)->delete();
    }

    public function down(): void
    {
        // Nothing to restore: see the note above.
    }
};
