<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Helpers\GameHelper;
use App\Helpers\JsonLd;
use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameSubmission;
use App\Models\MenuDisk;
use App\Models\Review;
use App\Models\Screenshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function show(Game $game)
    {
        // Everything this method and the card_* partials of games/show walk.
        // Loaded up front, each relation costs one query instead of one per
        // row
        $game->load([
            'developers',
            'genres',
            'screenshots',
            'sndhs',
            'reviews',
            'individuals.interviews',
            'releases.boxscans',
            'releases.menuDiskContents',
            'menuDiskContents',
            // getAllSimilarGamesAttribute() merges these two, and the filter
            // below reads the screenshots of every game they return.
            'similarGames.screenshots',
            'similarGamesReverse.screenshots',
        ]);

        $developersLogos = $game->developers
            ->filter(function ($developer) {
                return $developer->logo;
            })
            ->map(function ($developer) {
                return $developer->logo;
            });

        $boxscans = $game->releases
            ->flatMap(function ($release) {
                return $release->boxscans
                    ->filter(function ($boxscan) {
                        return Str::startsWith($boxscan->type, 'Box');
                    })
                    ->sortByDesc('type')    // Arrange for "Box front" to be sorted before "Box back"
                    ->map(function ($boxscan) use ($release) {
                        return [
                            'release' => $release,
                            'boxscan' => asset('storage/' . $boxscan->path),
                            'preview' => route('games.releases.boxscan', [
                                'release' => $release,
                                'id'      => $boxscan->getKey(),
                            ]),
                        ];
                    });
            });

        // Collect interviews for individuals of the game. An individual may
        // have multiple role so make sure to get unique interviews
        $interviews = $game->individuals
            // Display only interviews where we have a picture of the individual
            ->filter(function ($individual) {
                return $individual !== null
                    && $individual->file !== null;
            })
            ->flatMap(function ($individual) {
                return $individual->interviews;
            })
            ->unique('id');

        // Filter unpublished reviews
        $reviews = $game->reviews->reject(function ($review) {
            return $review->submission !== Review::REVIEW_PUBLISHED;
        });

        // Similar games, only the ones with screenshots
        $similar = null;
        $game->allSimilarGames
            ->filter(function ($similar) {
                return $similar->screenshots->isNotEmpty();
            })
            ->whenNotEmpty(function ($collection) use (&$similar) {
                $similar = $collection->random();
            });

        // Collect all menu disks that this game is part of

        // First collect all releases that are part of a menu, and get the
        // corresponding disk
        $menuDiskIds = $game->releases
            ->filter(function ($release) {
                return $release->menuDiskContents->isNotEmpty();
            })
            ->map(function ($release) {
                return $release->menuDiskContents->first()->menu_disk_id;
            })
            // These are ids, not models. Eloquent\Collection::map() only
            // downgrades to a base collection when it can see a non-model in
            // the result, so for a game whose releases are none of them in a
            // menu it hands back an empty *Eloquent* collection - and
            // Eloquent\Collection::unique() then calls getKey() on the ints
            // concat'd in below. Force the type rather than rely on content.
            ->toBase()
            // Then collect all standalone menus disks that don't have a release
            // (i.e. docs, trainer, ...)
            ->concat($game->menuDiskContents->pluck('menu_disk_id'))
            ->unique();

        // Fetch the disks themselves in one query, with everything card_menus
        // renders for each of them.
        $menuDisks = MenuDisk::with([
            'menu.menuSet',
            'screenshots',
            'menuDiskDump',
            'contents.release.game',
            'contents.game',
            'contents.menuSoftware',
        ])
            ->whereIn('id', $menuDiskIds)
            ->get()
            ->sortBy('download_basename');

        // Collect all SNDH tracks
        $sndhs = $game->sndhs
            ->map(function ($sndh) use ($game) {
                $songs = [];
                if ($sndh->subtunes > 1) {
                    for ($i = 1; $i <= $sndh->subtunes; $i++) {
                        $songs[] = [
                            'name'   => ($sndh->title ?? 'Unknown') . ' (' . $i . '/' . $sndh->subtunes . ')',
                            'artist' => $sndh->composer ?? 'Unknown',
                            'url'    => route('music', ['sndh' => $sndh, 'subtune' => $i]),
                            'cover'  => route('music.cover', $game),
                        ];
                    }
                } else {
                    $songs[] = [
                        'name'   => $sndh->title ?? 'Unknown',
                        'artist' => $sndh->composer ?? 'Unknown',
                        'url'    => route('music', $sndh),
                        'cover'  => route('music.cover', $game),
                    ];
                }

                return $songs;
            })
            ->flatten(1);

        $jsonLd = (new JsonLd('VideoGame', url()->current()))
            ->add('name', $game->name)
            ->add('description', GameHelper::description($game))
            ->add('applicationCategory', 'Game')
            ->add('operatingSystem', 'TOS')
            ->add('gamePlatform', 'Atari ST');
        if ($game->screenshots->isNotEmpty()) {
            $jsonLd->add('image', $game->screenshots->random()->getUrlRoute('game', $game));
        }
        if ($game->genres->isNotEmpty()) {
            $jsonLd->add('genre', $game->genres->pluck('name'));
        }

        $vote = null;
        if (Auth::check()) {
            $vote = GameVoteController::findVote($game, Auth::user());
        }

        $votes = DB::table('game_votes')
            ->selectRaw('avg(score) as score')
            ->selectRaw('count(score) as votes')
            ->where('game_id', '=', $game->getKey())
            ->groupBy('game_id')
            ->first();
        $score = null;
        $voteCount = 0;
        if ($votes) {
            $score = GameHelper::normalizeScore((float) $votes->score);
            $voteCount = $votes->votes;
        }

        return view('games.show')->with([
            'game'              => $game,
            'developersLogos'   => $developersLogos,
            'boxscans'          => $boxscans,
            'interviews'        => $interviews,
            'reviews'           => $reviews,
            'similar'           => $similar,
            'menuDisks'         => $menuDisks,
            'sndhs'             => $sndhs,
            'jsonLd'            => $jsonLd,
            'vote'              => $vote,
            'score'             => $score,
            'voteCount'         => $voteCount,
        ]);
    }

    public function postComment(Game $game, Request $request)
    {
        $comment = new Comment();
        $comment->text = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $game->comments()->save($comment);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->name,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $game->name,
        ]);

        return back();
    }

    public function submit(Game $game, Request $request)
    {
        $submission = new GameSubmission();
        $submission->timestamp = time();
        $submission->text = $request->info;
        $submission->game_done = GameSubmission::SUBMISSION_NEW;

        $submission->user()->associate($request->user());
        $game->submissions()->save($submission);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $screenshot = new Screenshot();
                $screenshot->imgext = $file->extension();

                $submission->screenshots()->save($screenshot);

                $file->storeAs('images/game_submit_screenshots', $screenshot->getKey() . '.' . $screenshot->imgext, 'public');
            }
        }

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->name,
            'sub_section'      => 'Submission',
            'sub_section_id'   => $game->getKey(),
            'sub_section_name' => $game->name,
        ]);

        $request->session()->flash('alert-title', 'Info submitted');
        $request->session()->flash(
            'alert-success',
            'Thanks for your submission, a moderator will review it soon!'
        );

        return back();
    }
}
