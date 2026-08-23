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
        } elseif ($request->has('score')) {
            if (! $vote) {
                $vote = new GameVote([
                    'game_id' => $game->getKey(),
                    'user_id' => Auth::user()->getKey(),
                ]);
            }

            $vote->score = $request->score;
            $vote->save();
        }

        return redirect()->route('games.show', $game);
    }

    public static function findVote(Game $game, User $user): ?GameVote
    {
        return GameVote::where('game_id', '=', $game->getKey())
            ->where('user_id', '=', $user->getKey())
            ->first();
    }
}
