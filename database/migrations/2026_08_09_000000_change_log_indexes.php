<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('change_log', function (Blueprint $table) {
            $table->index('timestamp');
            $table->index('user_id');
            $table->index('action');
            $table->index(['section', 'sub_section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_log', function (Blueprint $table) {
            $table->dropIndex('change_log_timestamp_index');
            $table->dropIndex('change_log_user_id_index');
            $table->dropIndex('change_log_action_index');
            $table->dropIndex('change_log_section_sub_section_index');
        });
    }
};
