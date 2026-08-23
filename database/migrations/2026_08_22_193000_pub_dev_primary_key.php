<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename pub_dev.pub_dev_id to id.
 *
 * pub_dev_text.pub_dev_id, game_release.pub_dev_id, the
 * game_release_distributor pivot and game_developer.dev_pub_id are all foreign
 * keys and keep their names.
 *
 * Ajax/CompanyController serialises the model straight to JSON, so its payload
 * key moves with the column and the data-autocomplete-id on the developer field
 * moves in the same commit -- unlike Ajax/IndividualController, which builds an
 * array literal and so keeps its key. See Phase B step 8.
 *
 * See docs/plans/2026-08-17-primary-key-rename.md, Phase B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pub_dev', function (Blueprint $table) {
            $table->renameColumn('pub_dev_id', 'id');
        });
    }

    public function down(): void
    {
        Schema::table('pub_dev', function (Blueprint $table) {
            $table->renameColumn('id', 'pub_dev_id');
        });
    }
};
