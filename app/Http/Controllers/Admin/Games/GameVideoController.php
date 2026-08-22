<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Game;
use App\Models\GameVideo;
use App\Rules\YoutubeUrl;
use App\View\Components\Admin\Crumb;
use Embed\Embed;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Waynestate\Youtube\ParseId;

class GameVideoController extends Controller
{
    public function index(Game $game)
    {
        return view('admin.games.games.videos.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.games.index'), 'Games'),
                    new Crumb(route('admin.games.games.edit', $game), $game->game_name),
                    new Crumb(route('admin.games.game-videos.index', $game), 'Videos'),
                ],
                'game'        => $game,
            ]);
    }

    public function store(Request $request, Game $game)
    {
        $request->validate([
            'video' => new YoutubeUrl,
        ]);

        $youtubeId = ParseId::fromUrl($request->video);

        // The title and author come from YouTube itself. If it cannot be
        // reached, or answers without them, keep the video and fall back to
        // its ID rather than failing the whole save.
        $title = null;
        $author = null;

        try {
            $embed = new Embed();
            $info = $embed->get($request->video);

            $title = $info->title;
            $author = $info->authorName;
        } catch (Exception $e) {
            Log::warning('Error retrieving details of YouTube video ' . $youtubeId, ['Exception' => $e]);
        }

        $video = GameVideo::create([
            'title'      => $title ?: $youtubeId,
            'author'     => $author ?: '',
            'youtube_id' => $youtubeId,
            'game_id'    => $game->getKey(),
        ]);

        ChangelogHelper::insert([
            'action'           => Changelog::INSERT,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Video',
            'sub_section_id'   => $video->id,
            'sub_section_name' => $video->title,
        ]);

        return redirect()->route('admin.games.game-videos.index', $game);
    }

    public function destroy(Game $game, GameVideo $video)
    {
        $video->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $game->getKey(),
            'section_name'     => $game->game_name,
            'sub_section'      => 'Video',
            'sub_section_id'   => $video->id,
            'sub_section_name' => $video->youtube_id,
        ]);

        return redirect()->route('admin.games.game-videos.index', $game);
    }
}
