<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameVote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameVoteController extends Controller
{


    public function vote(Request $request, Game $game)
    {
        $request->validate([
            'score'  => 'nullable|numeric|min:0|max:4',
        ]);

        $vote = GameVoteController::findVote($game, Auth::user());

        if ($request->remove === 'remove') {
            $vote->delete();
        } else if ($request->has('score')) {
            if (!$vote) {
                $vote = new GameVote([
                    'game_id' => $game->game_id,
                    'user_id' => Auth::user()->user_id,
                ]);
            }

            $vote->score = $request->score;
            $vote->save();
        }

        return redirect()->route('games.show', $game);
    }

    public static function findVote(Game $game, User $user): ?GameVote
    {
        return GameVote::where('game_id', '=', $game->game_id)
            ->where('user_id', '=', $user->user_id)
            ->first();
    }
}
