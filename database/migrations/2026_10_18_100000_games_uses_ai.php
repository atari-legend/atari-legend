<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * games.uses_ai flags a game that was written or ported with the help of AI, so
 * that those games can be found and promoted. games.ai_notes says how: a
 * straight AI port, AI-assisted, or AI use assumed rather than stated by the
 * author.
 *
 * One flag rather than a graded value for now; the notes carry the nuance.
 *
 * See https://github.com/atari-legend/atari-legend/issues/290
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $t) {
            $t->boolean('uses_ai')->default(false)->after('multiplayer_hardware');
            $t->text('ai_notes')->nullable()->after('uses_ai');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $t) {
            $t->dropColumn(['uses_ai', 'ai_notes']);
        });
    }
};
