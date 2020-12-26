<?php

namespace App\Http\Controllers;

use App\Helpers\ChangelogHelper;
use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameSubmitInfo;
use App\Models\Review;
use App\Models\Screenshot;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function show(Game $game)
    {
        $developersLogos = $game->developers
            ->filter(function ($developer) {
                return $developer->texts->isNotEmpty() && $developer->texts->first()->file !== null;
            })
            ->map(function ($developer) {
                return $developer->texts->first()->file;
            });

        // Collect all release scans and game scans in a single list
        // This is temporary until all game scans are moved to releases
        $releaseBoxscans = $game->releases
            ->flatMap(function ($release) {
                return $release->boxscans
                    ->filter(function ($boxscan) {
                        return Str::startsWith($boxscan->type, 'Box');
                    })
                    ->map(function ($boxscan) use ($release) {
                        return [
                            'release' => $release,
                            'boxscan' => asset('storage/images/game_release_scans/'.$boxscan->file),
                        ];
                    });
            });
        $gameBoxscans = $game->boxscans->map(function ($boxscan) {
            return [
                'release' => null,
                'boxscan' => asset('storage/images/game_boxscans/'.$boxscan->file),
            ];
        });

        // Collect interviews for individuals of the game. An individual may
        // have multiple role so make sure to get unique interviews
        $interviews = $game->individuals
            // Display only interviews where we have a picture of the individual
            ->filter(function ($gameIndividual) {
                return $gameIndividual->individual->text !== null && $gameIndividual->individual->text->file !== null;
            })
            ->flatMap(function ($gameIndividual) {
                return $gameIndividual->individual->interviews;
            })
            ->unique('interview_id');

        // Filter unpublishes reviews
        $reviews = $game->reviews->reject(function ($review) {
            return $review->review_edit !== Review::REVIEW_PUBLISHED;
        });

        // Similar games, only the ones with screenshots
        $similar = null;
        $game->similarGames
            ->filter(function($similar) {
                return $similar->screenshots->isNotEmpty();
            })
            ->whenNotEmpty(function ($collection) use(&$similar) {
                $similar = $collection->random();
            });

        return view('games.show')->with([
            'game'              => $game,
            'developersLogos'   => $developersLogos,
            'boxscans'          => $releaseBoxscans->merge($gameBoxscans),
            'interviews'        => $interviews,
            'reviews'           => $reviews,
            'similar'           => $similar,
        ]);
    }

    public function postComment(Game $game, Request $request)
    {
        $comment = new Comment();
        $comment->comment = $request->comment;
        $comment->timestamp = time();

        $request->user()->comments()->save($comment);
        $game->comments()->save($comment);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Comment',
            'sub_section_id'   => $comment->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        return back();
    }

    public function submitInfo(Game $game, Request $request)
    {
        $info = new GameSubmitInfo();
        $info->timestamp = time();
        $info->submit_text = $request->info;
        $info->game_done = GameSubmitInfo::SUBMISSION_NEW;

        $info->user()->associate($request->user());
        $game->infoSubmissions()->save($info);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $screenshot = new Screenshot();
                $screenshot->imgext = $file->extension();

                $info->screenshots()->save($screenshot);

                $file->storeAs('images/game_submit_screenshots', $screenshot->screenshot_id.'.'.$screenshot->imgext, 'public');
            }
        }

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Submission',
            'sub_section_id'   => $game->getKey(),
            'sub_section_name' => $game->game_name,
        ]);

        $request->session()->flash('alert-title', 'Info submitted');
        $request->session()->flash(
            'alert-success',
            'Thanks for your submission, a moderator will review it soon!'
        );

        return back();
    }
}
