<?php

namespace Database\Seeders;

use App\Helpers\UserHelper;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class E2ESeeder extends Seeder
{
    /**
     * Seed the application for End-to-End (E2E) Playwright tests.
     */
    public function run(): void
    {
        // This seeder creates an administrator whose password is public
        // knowledge, and it ships to the servers with the rest of the code.
        // Refuse to run anywhere it could matter.
        if (app()->isProduction()) {
            throw new RuntimeException('E2ESeeder must never run in production.');
        }

        $salt = UserHelper::salt();
        $sha512Password = UserHelper::hashPassword('password', $salt);

        // 1. Admin user for CPANEL authentication
        User::updateOrCreate(
            ['userid' => 'admin'],
            [
                'email'             => 'admin@atarilegend.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'salt'              => $salt,
                'sha512_password'   => $sha512Password,
                'permission'        => User::PERMISSION_ADMIN,
                'inactive'          => User::ACTIVE,
                'join_date'         => (string) now()->timestamp,
                'last_visit'        => (string) now()->timestamp,
                'remember_token'    => Str::random(10),
                'karma'             => 0,
                'show_email'        => false,
            ]
        );

        // 2. Standard user
        User::updateOrCreate(
            ['userid' => 'testuser'],
            [
                'email'             => 'test@example.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'),
                'salt'              => $salt,
                'sha512_password'   => $sha512Password,
                'permission'        => User::PERMISSION_USER,
                'inactive'          => User::ACTIVE,
                'join_date'         => (string) now()->timestamp,
                'last_visit'        => (string) now()->timestamp,
                'remember_token'    => Str::random(10),
                'karma'             => 0,
                'show_email'        => false,
            ]
        );

        // 3. Ensure minimal sample data exists if database is fresh
        if (DB::table('game')->count() === 0) {
            DB::table('game')->insert([
                'game_id'   => 1,
                'game_name' => 'Xenon 2 Megablast',
                'slug'      => 'xenon-2-megablast',
            ]);
        }

        // A release gives the game page something to link to. It needs a date:
        // the link on the game page is labelled with the release year, and an
        // empty label is not clickable.
        if (DB::table('game_release')->count() === 0) {
            DB::table('game_release')->insert([
                'game_id' => 1,
                'date'    => '1989-01-01',
                'license' => 'Commercial',
            ]);
        }

        if (DB::table('screenshot_main')->count() === 0) {
            DB::table('screenshot_main')->insert([
                'screenshot_id' => 1,
                'imgext'        => 'png',
            ]);
            DB::table('screenshot_game')->insert([
                'game_id'       => 1,
                'screenshot_id' => 1,
            ]);
        }

        if (DB::table('article_main')->count() === 0) {
            DB::table('article_main')->insert([
                'article_id' => 1,
                'user_id'    => 1,
            ]);
            DB::table('article_text')->insert([
                'article_id'    => 1,
                'article_title' => 'Test Article',
                'article_intro' => 'Test Intro',
                'article_text'  => 'Playwright test article content.',
                'article_date'  => now()->timestamp,
            ]);
        }

        if (DB::table('review_main')->count() === 0) {
            DB::table('review_main')->insert([
                'review_id'   => 1,
                'user_id'     => 1,
                'review_text' => 'Great game!',
                'review_date' => now()->timestamp,
            ]);
            DB::table('review_game')->insert([
                'review_id' => 1,
                'game_id'   => 1,
            ]);
        }

        if (DB::table('interview_main')->count() === 0) {
            // Interview 1 is complete: the "Who is it?" card on the home page
            // only picks an interview whose individual has a picture, and the
            // card then reads the interview's text, so seed the whole chain.
            DB::table('individuals')->insert([
                'ind_id'   => 1,
                'ind_name' => 'Test Individual',
            ]);
            DB::table('individual_text')->insert([
                'ind_id'     => 1,
                'ind_imgext' => 'png',
            ]);
            DB::table('interview_main')->insert([
                'interview_id' => 1,
                'user_id'      => 1,
                'ind_id'       => 1,
            ]);
            DB::table('interview_text')->insert([
                'interview_id'    => 1,
                'interview_intro' => 'Playwright test interview intro.',
                'interview_text'  => 'Playwright test interview content.',
                'interview_date'  => now()->timestamp,
            ]);

            // Interview 2 deliberately has neither an individual nor a text
            // row. The admin table joins those, and a row with no match is
            // what used to null out its primary key - keep a row of that shape
            // so the regression stays covered.
            DB::table('interview_main')->insert([
                'interview_id' => 2,
                'user_id'      => 1,
            ]);
        }

        if (DB::table('magazines')->count() === 0) {
            DB::table('magazines')->insert([
                'name' => 'Test Magazine',
            ]);
        }

        // The menu set index inner-joins sets to menus to disks, so a set on
        // its own would not appear at all.
        //
        // These tables are Eloquent-managed, so their timestamps are never
        // null in practice. Set them explicitly: a raw insert would leave them
        // null, and the "Latest menus" card formats updated_at unguarded.
        if (DB::table('menu_sets')->count() === 0) {
            $now = now();

            $setId = DB::table('menu_sets')->insertGetId([
                'name'       => 'Test Menu Set',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $menuId = DB::table('menus')->insertGetId([
                'menu_set_id' => $setId,
                'number'      => 1,
                'date'        => '1990-01-01',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
            DB::table('menu_disks')->insert([
                'menu_id'                => $menuId,
                'menu_disk_condition_id' => DB::table('menu_disk_conditions')
                    ->where('name', 'Intact')
                    ->value('id'),
                'created_at'             => $now,
                'updated_at'             => $now,
            ]);
        }

        if (DB::table('news')->count() === 0) {
            DB::table('news')->insert([
                'news_id'       => 1,
                'news_headline' => 'Welcome to Atari Legend',
                'news_text'     => 'Playwright test news post.',
                'user_id'       => 1,
                'news_date'     => now()->timestamp,
            ]);
        }
    }
}
