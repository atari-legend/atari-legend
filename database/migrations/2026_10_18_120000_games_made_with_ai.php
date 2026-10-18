<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * games.uses_ai becomes games.made_with_ai: the flag records that a game was
 * made with AI (written or ported with its help), not that the game uses AI.
 *
 * See https://github.com/atari-legend/atari-legend/issues/290
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $t) {
            $t->renameColumn('uses_ai', 'made_with_ai');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $t) {
            $t->renameColumn('made_with_ai', 'uses_ai');
        });
    }
};
