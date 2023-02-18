<?php

use App\Models\Game;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        collect([
            '%-ii' => [
                'pattern'     => '/-ii$/',
                'replacement' => '-2',
            ],
            '%-iii' => [
                'pattern'     => '/-iii$/',
                'replacement' => '-3',
            ],
            '%-iv' => [
                'pattern'     => '/-iv$/',
                'replacement' => '-4',
            ],
            '%-v' => [
                'pattern'     => '/-v$/',
                'replacement' => '-5',
            ],
            '%-vi' => [
                'pattern'     => '/-vi$/',
                'replacement' => '-6',
            ],
            '%-iv-%' => [
                'pattern'     => '/-iv-/',
                'replacement' => '-4-',
            ],
            '%-v-%' => [
                'pattern'     => '/-v-/',
                'replacement' => '-5-',
            ],
            '%-vi-%' => [
                'pattern'     => '/-vi-/',
                'replacement' => '-6-',
            ],
        ])->each(function ($data, $like) {
            Game::where('slug', 'like', $like)
                ->each(function ($game) use ($data) {
                    $slug = preg_replace($data['pattern'], $data['replacement'], $game->slug);

                    DB::update('update game set slug = ? where game_id = ?', [$slug, $game->getKey()]);
                });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
