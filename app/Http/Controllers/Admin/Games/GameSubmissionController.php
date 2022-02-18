<?php

namespace App\Http\Controllers\Admin\Games;

use App\Helpers\ChangelogHelper;
use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\Comment;
use App\Models\GameSubmitInfo;
use App\Models\Screenshot;
use App\View\Components\Admin\Crumb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GameSubmissionController extends Controller
{
    public function index()
    {
        return view('admin.games.submissions.index')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.submissions.index'), 'Game submissions'),
                ],
            ]);
    }

    public function show(GameSubmitInfo $submission)
    {
        return view('admin.games.submissions.show')
            ->with([
                'breadcrumbs' => [
                    new Crumb(route('admin.games.submissions.index'), 'Games submissions'),
                    new Crumb(route('admin.games.submissions.show', $submission), $submission->game->game_name),
                ],
                'submission'        => $submission,
            ]);
    }

    public function update(Request $request, GameSubmitInfo $submission)
    {
        switch ($request->action) {
            case 'unreview':
                $submission->game_done = GameSubmitInfo::SUBMISSION_NEW;
                $submission->save();

                ChangelogHelper::insert([
                    'action'           => Changelog::UPDATE,
                    'section'          => 'Games',
                    'section_id'       => $submission->game->getKey(),
                    'section_name'     => $submission->game->game_name,
                    'sub_section'      => 'Submission',
                    'sub_section_id'   => $submission->getKey(),
                    'sub_section_name' => $submission->game->game_name,
                ]);
                break;
            case 'review':
                $submission->game_done = GameSubmitInfo::SUBMISSION_REVIEWED;
                $submission->save();

                ChangelogHelper::insert([
                    'action'           => Changelog::UPDATE,
                    'section'          => 'Games',
                    'section_id'       => $submission->game->getKey(),
                    'section_name'     => $submission->game->game_name,
                    'sub_section'      => 'Submission',
                    'sub_section_id'   => $submission->getKey(),
                    'sub_section_name' => $submission->game->game_name,
                ]);
                break;
            case  'comment':
                $comment = new Comment([
                    'comment'   => $submission->submit_text,
                    'timestamp' => $submission->timestamp,
                    'user_id'   => $submission->user_id,
                ]);
                $comment->save();
                $comment->games()->attach($submission->game);

                ChangelogHelper::insert([
                    'action'           => Changelog::INSERT,
                    'section'          => 'Games',
                    'section_id'       => $submission->game->getKey(),
                    'section_name'     => $submission->game->game_name,
                    'sub_section'      => 'Comment',
                    'sub_section_id'   => $comment->comments_id,
                    'sub_section_name' => $submission->game->game_name,
                ]);

                $this->destroy($submission);

                break;
        }

        return redirect()->route('admin.games.submissions.index');
    }

    public function destroy(GameSubmitInfo $submission)
    {
        foreach ($submission->screenshots as $screenshot) {
            Storage::disk('public')->delete($screenshot->getPath('game_submission'));
        }
        $submission->delete();

        ChangelogHelper::insert([
            'action'           => Changelog::DELETE,
            'section'          => 'Games',
            'section_id'       => $submission->game->getKey(),
            'section_name'     => $submission->game->game_name,
            'sub_section'      => 'Submission',
            'sub_section_id'   => $submission->getKey(),
            'sub_section_name' => $submission->game->game_name,
        ]);

        return redirect()->route('admin.games.submissions.index');
    }

    public function destroyScreenshot(GameSubmitInfo $submission, Screenshot $screenshot)
    {
        if ($submission->screenshots->contains($screenshot)) {
            $submission->screenshots()->detach($screenshot);

            Storage::disk('public')->delete($screenshot->getPath('game_submission'));

            ChangelogHelper::insert([
                'action'           => Changelog::DELETE,
                'section'          => 'Games',
                'section_id'       => $submission->game->getKey(),
                'section_name'     => $submission->game->game_name,
                'sub_section'      => 'Submission',
                'sub_section_id'   => $submission->getKey(),
                'sub_section_name' => 'Screenshot',
            ]);
        }

        return redirect()->route('admin.games.submissions.show', $submission);
    }
}
