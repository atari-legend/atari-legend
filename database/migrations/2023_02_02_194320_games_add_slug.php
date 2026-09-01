<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('game', function (Blueprint $table) {
            $table->string('slug', 255)->index();
        });

        $games = DB::table('game')
            ->select('game.game_id', 'game.game_name', 'pub_dev.pub_dev_name', 'individuals.ind_name')
            ->leftJoin('game_developer', 'game.game_id', '=', 'game_developer.game_id')
            ->leftJoin('pub_dev', 'game_developer.dev_pub_id', '=', 'pub_dev.pub_dev_id')
            ->leftJoin('game_individual', 'game.game_id', '=', 'game_individual.game_id')
            ->leftJoin('individuals', 'game_individual.individual_id', '=', 'individuals.ind_id')
            ->orderByDesc('pub_dev.pub_dev_name')
            ->orderByDesc('individuals.ind_name')
            ->get()
            ->groupBy('game_id');

        foreach ($games as $gameId => $gameRows) {
            $firstRow = $gameRows->first();
            $slug = $firstRow->name;

            // Handle names like "Enforcer, The" -> "The Enforcer"
            foreach (['The', 'A', 'Das', 'Die', 'Der', 'Les', 'La', 'Le', "L'"] as $article) {
                if (Str::endsWith($slug, ", {$article}")) {
                    $slug = $article . ' ' . preg_replace("/, {$article}$/", '', $slug);
                }
            }
            $slug = Str::slug($slug);
            $slug = str_replace('-ii-', '-2-', $slug);
            $slug = str_replace('-iii-', '-3-', $slug);

            // If duplicates, append the first developer
            if (DB::table('game')->where('slug', '=', $slug)->count()) {
                $devName = $gameRows->whereNotNull('pub_dev_name')->first()?->pub_dev_name;
                if ($devName) {
                    $slug .= '-' . Str::slug($devName);
                }
            }

            // If duplicates, append the first individual
            if (DB::table('game')->where('slug', '=', $slug)->count()) {
                $indName = $gameRows->whereNotNull('ind_name')->first()?->ind_name;
                if ($indName) {
                    $slug .= '-' . Str::slug($indName);
                }
            }

            // If dupicates, not much more we can do. Append the ID
            if (DB::table('game')->where('slug', '=', $slug)->count()) {
                $slug .= '-id-' . $gameId;
            }

            DB::update('update game set slug = ? where game_id = ?', [$slug, $gameId]);
        }

        // Slugs cannot be purely numeric or they will conflict with IDs
        // Handle a few special cases
        foreach ([
            '180'  => '180-darts',
            '1789' => '1789-fil',
            '1944' => '1944-seuck',
            '3'    => '3-adventure',
            '1990' => '1990-shmup',
            '2048' => '2048-puzzle',
        ] as $oldSlug => $newSlug) {
            DB::update('update game set slug = ? where slug = ?', [$newSlug, (string) $oldSlug]);
        }

        Schema::table('game', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('game', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
