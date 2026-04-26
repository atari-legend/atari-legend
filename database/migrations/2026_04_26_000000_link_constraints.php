c<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('website_category_cross')
            ->whereNotIn('website_id', DB::table('website')->select('website_id'))
            ->delete();

        DB::table('website_category_cross')
            ->whereNotIn('website_category_id', DB::table('website_category')->select('website_category_id'))
            ->delete();

        $pivotIdsToKeep = DB::table('website_category_cross')
            ->selectRaw('MIN(website_category_cross_id) as website_category_cross_id')
            ->groupBy('website_id', 'website_category_id')
            ->pluck('website_category_cross_id');

        if ($pivotIdsToKeep->isNotEmpty()) {
            DB::table('website_category_cross')
                ->whereNotIn('website_category_cross_id', $pivotIdsToKeep)
                ->delete();
        }

        DB::table('website')
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', DB::table('users')->select('user_id'))
            ->update(['user_id' => null]);

        Schema::table('website_validate', function (Blueprint $table) {
            $table->integer('user_id')
                ->nullable()
                ->default(null)
                ->change();
        });

        DB::table('website_validate')
            ->whereNotNull('user_id')
            ->whereNotIn('user_id', DB::table('users')->select('user_id'))
            ->update(['user_id' => null]);

        Schema::table('website_category_cross', function (Blueprint $table) {
            $table->foreign('website_id')
                ->references('website_id')
                ->on('website')
                ->onDelete('cascade');

            $table->foreign('website_category_id')
                ->references('website_category_id')
                ->on('website_category')
                ->onDelete('cascade');

            $table->unique(['website_id', 'website_category_id']);
        });

        Schema::table('website', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('website_validate', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_validate', function (Blueprint $table) {
            $table->dropForeign('website_validate_user_id_foreign');
        });

        Schema::table('website', function (Blueprint $table) {
            $table->dropForeign('website_user_id_foreign');
        });

        Schema::table('website_category_cross', function (Blueprint $table) {
            $table->dropForeign('website_category_cross_website_id_foreign');
            $table->dropForeign('website_category_cross_website_category_id_foreign');
            $table->dropUnique('website_category_cross_website_id_website_category_id_unique');
        });

        DB::table('website_validate')
            ->whereNull('user_id')
            ->update(['user_id' => 0]);

        Schema::table('website_validate', function (Blueprint $table) {
            $table->integer('user_id')
                ->nullable(false)
                ->default(0)
                ->change();
        });
    }
};
