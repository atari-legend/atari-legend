<?php

use App\Models\Game;
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

        Game::all()
            ->sortByDesc(['developers.pub_dev_name', 'individuals.ind_name'])
            ->each(function ($game) {
                $slug = $game->game_name;

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
                if (Game::where('slug', '=', $slug)->count()) {
                    if ($game->developers->isNotEmpty()) {
                        $slug .= '-' . Str::slug($game->developers->first()->pub_dev_name);
                    }
                }

                // If duplicates, append the first individual
                if (Game::where('slug', '=', $slug)->count()) {
                    if ($game->individuals->isNotEmpty()) {
                        $slug .= '-' . Str::slug($game->individuals->first()->ind_name);
                    }
                }

                // If dupicates, not much more we can do. Append the ID
                if (Game::where('slug', '=', $slug)->count()) {
                    $slug .= '-id-' . $game->getKey();
                }

                DB::update('update game set slug = ? where game_id = ?', [$slug, $game->getKey()]);
            });

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
