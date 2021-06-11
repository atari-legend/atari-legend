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
use Illuminate\Http\Request;
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

        $embed = new Embed();
        $info = $embed->get($request->video);

        $video = GameVideo::create([
            'title'      => $info->title,
            'author'     => $info->authorName,
            'youtube_id' => ParseId::fromUrl($request->video),
            'game_id'    => $game->game_id,
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
