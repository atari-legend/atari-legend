<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Split `comments` into game_comments, article_comments, interview_comments and
 * review_comments, and drop the four pivots with it.
 *
 * A comment belongs to exactly one of a game, an article, an interview or a
 * review, but it reached that owner through a many-to-many pivot, so finding
 * out what a comment was on took up to four queries and a comment that belonged
 * to nothing threw. The split gives every comment a real foreign key that
 * cascades, which is what lets the admin game delete stop sweeping comments by
 * hand, and the table a comment is in is what says which section it is on.
 *
 * The plural names follow article_screenshot_comments and
 * docs/plans/2026-08-29-plural-table-rename.md: these are entity tables after
 * the split, not pivots.
 *
 * A comment that belongs to nothing has nowhere to go and down() could not
 * restore one, so up() refuses to run while any exists.
 *
 * See docs/plans/2026-09-10-relationship-cardinality.md, Unit 4.
 */
return new class extends Migration
{
    /**
     * Owner column => pivot table. The same order everywhere below.
     */
    private const OWNERS = [
        'game_id'      => 'game_comment',
        'article_id'   => 'article_comment',
        'interview_id' => 'interview_comment',
        'review_id'    => 'review_comment',
    ];

    public function up(): void
    {
        $owners = implode(' + ', array_map(
            fn ($pivot) => "(comments.id IN (SELECT comment_id FROM {$pivot}))",
            self::OWNERS
        ));

        $shared = DB::table('comments')->whereRaw("{$owners} > 1")->count();

        if ($shared > 0) {
            throw new RuntimeException("{$shared} comments belong to more than one thing.");
        }

        $ownerless = DB::table('comments')->whereRaw("{$owners} = 0")->count();

        if ($ownerless > 0) {
            throw new RuntimeException("{$ownerless} comments belong to nothing; delete them before migrating.");
        }

        $expected = [];

        foreach (self::OWNERS as $column => $pivot) {
            $expected[$column] = DB::table($pivot)->count();

            Schema::create(self::table($column), function (Blueprint $t) use ($column) {
                // integer(.., true) and not increments(): `comments` is a
                // signed int(11) and the ids carry across unchanged.
                $t->integer('id', true);
                $t->integer($column);
                // The shape `comments.user_id` has: NOT NULL DEFAULT 0, an
                // index, and no constraint. 2026_08_28_120000 ruled that a user
                // can be deleted and what they wrote stays.
                $t->integer('user_id')->default(0)->index();
                $t->mediumText('text')->nullable();
                $t->dateTime('created_at')->nullable();
                $t->dateTime('updated_at')->nullable();
                $t->foreign($column)->references('id')->on(self::ownerTable($column))->cascadeOnDelete();
            });

            // comments.id carries into the new id so that every changelogs row
            // naming a comment still names the same one.
            DB::table(self::table($column))->insertUsing(
                ['id', $column, 'user_id', 'text', 'created_at', 'updated_at'],
                DB::table('comments')
                    ->join($pivot, "{$pivot}.comment_id", '=', 'comments.id')
                    ->select('comments.id', "{$pivot}.{$column}", 'comments.user_id',
                        'comments.text', 'comments.created_at', 'comments.updated_at')
            );

            $moved = DB::table(self::table($column))->count();

            if ($moved !== $expected[$column]) {
                throw new RuntimeException(
                    "Backfilled {$moved} of {$expected[$column]} {$pivot} rows; refusing to drop anything."
                );
            }
        }

        // Last, and only once the data is provably in its new home: MariaDB
        // will not roll a DDL statement back, so the ordering is the safety.
        foreach (self::OWNERS as $pivot) {
            Schema::drop($pivot);
        }

        Schema::drop('comments');
    }

    public function down(): void
    {
        Schema::create('comments', function (Blueprint $t) {
            $t->integer('id', true);
            $t->mediumText('text')->nullable();
            $t->integer('user_id')->default(0)->index();
            $t->dateTime('created_at')->nullable();
            $t->dateTime('updated_at')->nullable();
        });

        foreach (self::OWNERS as $column => $pivot) {
            // The ids came out of one sequence, so they are disjoint across the
            // four tables until each starts handing out its own. Once they
            // have, this meets a duplicate key and throws, rather than
            // renumbering -- renumbering would silently repoint every
            // changelogs row that names a comment.
            DB::table('comments')->insertUsing(
                ['id', 'text', 'user_id', 'created_at', 'updated_at'],
                DB::table(self::table($column))
                    ->select('id', 'text', 'user_id', 'created_at', 'updated_at')
            );

            Schema::create($pivot, function (Blueprint $t) use ($column) {
                $t->integer('id', true);
                $t->integer($column)->default(0);
                $t->integer('comment_id')->default(0);
                $t->foreign($column)->references('id')->on(self::ownerTable($column))->cascadeOnDelete();
                $t->foreign('comment_id')->references('id')->on('comments')->cascadeOnDelete();
            });

            DB::table($pivot)->insertUsing(
                [$column, 'comment_id'],
                DB::table(self::table($column))->select($column, 'id')
            );

            Schema::drop(self::table($column));
        }
    }

    private static function table(string $column): string
    {
        return str_replace('_id', '_comments', $column);
    }

    private static function ownerTable(string $column): string
    {
        return str_replace('_id', 's', $column);
    }
};
