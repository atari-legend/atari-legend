<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fix ind_id column type (was string, should be integer to match individuals table)
        Schema::table('interview_main', function (Blueprint $table) {
            $table->integer('ind_id')
                ->nullable()
                ->change();
        });

        Schema::table('interview_main', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('ind_id')
                ->references('ind_id')
                ->on('individuals')
                ->onDelete('cascade');
        });

        DB::table('interview_text')
            ->whereRaw('interview_id not in (select interview_id from interview_main)')
            ->delete();

        Schema::table('interview_text', function (Blueprint $table) {
            $table->integer('interview_id')
                ->nullable(false)
                ->change();
            $table->text('interview_text')
                ->nullable(false)
                ->change();
            $table->integer('interview_date')
                ->nullable(false)
                ->change();

            $table->foreign('interview_id')
                ->references('interview_id')
                ->on('interview_main')
                ->onDelete('cascade');
        });

        DB::table('screenshot_interview')
            ->whereRaw('interview_id not in (select interview_id from interview_main)')
            ->delete();

        DB::table('screenshot_interview')
            ->whereRaw('screenshot_id not in (select screenshot_id from screenshot_main)')
            ->delete();

        Schema::table('screenshot_interview', function (Blueprint $table) {
            $table->integer('interview_id')
                ->nullable(false)
                ->change();
            $table->integer('screenshot_id')
                ->nullable(false)
                ->change();

            $table->foreign('interview_id')
                ->references('interview_id')
                ->on('interview_main')
                ->onDelete('cascade');
            $table->foreign('screenshot_id')
                ->references('screenshot_id')
                ->on('screenshot_main')
                ->onDelete('cascade');
        });

        DB::table('interview_comments')
            ->whereRaw('screenshot_interview_id not in (select screenshot_interview_id from screenshot_interview)')
            ->delete();

        Schema::table('interview_comments', function (Blueprint $table) {
            $table->integer('screenshot_interview_id')
                ->nullable(false)
                ->change();
            $table->text('comment_text')
                ->nullable(false)
                ->change();

            $table->foreign('screenshot_interview_id')
                ->references('screenshot_interview_id')
                ->on('screenshot_interview')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('interview_main', function (Blueprint $table) {
            $table->dropForeign('interview_main_user_id_foreign');
            $table->dropForeign('interview_main_ind_id_foreign');
        });

        Schema::table('interview_main', function (Blueprint $table) {
            $table->string('ind_id', 50)
                ->nullable()
                ->change();
        });

        Schema::table('interview_text', function (Blueprint $table) {
            $table->integer('interview_id')
                ->nullable()
                ->change();
            $table->text('interview_text')
                ->nullable()
                ->change();
            $table->integer('interview_date')
                ->nullable()
                ->change();

            $table->dropForeign('interview_text_interview_id_foreign');
        });

        Schema::table('screenshot_interview', function (Blueprint $table) {
            $table->integer('interview_id')
                ->nullable()
                ->change();
            $table->integer('screenshot_id')
                ->nullable()
                ->change();

            $table->dropForeign('screenshot_interview_interview_id_foreign');
            $table->dropForeign('screenshot_interview_screenshot_id_foreign');
        });

        Schema::table('interview_comments', function (Blueprint $table) {
            $table->integer('screenshot_interview_id')
                ->nullable()
                ->change();
            $table->text('comment_text')
                ->nullable()
                ->change();

            $table->dropForeign('interview_comments_screenshot_interview_id_foreign');
        });
    }
};
