<?php

namespace App\Helpers;

use App\Models\Game;
use App\Models\GameVote;

/**
 * Helper for Game votes.
 */
class GameVoteHelper
{
    const SVG_HEIGHT = 15;
    const BAR_WIDTH = 15;
    const SPACING = 2;

    /**
     * Draw a SVG of vote distribution for a game.
     *
     * @param  \App\Models\Game  $game  Game to draw the SVG for.
     * @return string SVG suitable to embed in HTML.
     */
    public static function getVoteDistributionSvg(Game $game)
    {
        $votes = GameVote::where('game_id', '=', $game->getKey())
            ->get()
            ->groupBy('score')
            ->map(function ($item, $key) {
                return $item->count();
            })
            ->all();

        $maxVotes = collect($votes)
            ->sortByDesc(function ($voteCount, $score) {
                return $voteCount;
            })->first();

        $barHeight = GameVoteHelper::SVG_HEIGHT - 3;
        $svgWidth = 5 * GameVoteHelper::BAR_WIDTH + (5 * GameVoteHelper::SPACING);

        $content = '<svg class="votes ms-2"
            width="'.$svgWidth.'"
            height="'.GameVoteHelper::SVG_HEIGHT.'"
            viewBox="0 0 '.$svgWidth.' '.GameVoteHelper::SVG_HEIGHT.'"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">';

        $content .= collect([0, 1, 2, 3, 4])->map(function ($score) use ($barHeight, $votes, $maxVotes) {
            $x = ($score * GameVoteHelper::BAR_WIDTH) + $score * GameVoteHelper::SPACING;
            $height = ($votes[$score] ?? 0) / $maxVotes * $barHeight;
            $y = $barHeight - $height;

            return '<rect width="'.GameVoteHelper::BAR_WIDTH."\" height=\"{$height}\" class=\"score-{$score}\" x=\"{$x}\" y=\"{$y}\" />";
        })
            ->join("\n");

        $content .= collect([0, 1, 2, 3, 4])->map(function ($score) {
            $x1 = ($score * GameVoteHelper::BAR_WIDTH) + $score * GameVoteHelper::SPACING;
            $x2 = $x1 + GameVoteHelper::BAR_WIDTH;
            $y = GameVoteHelper::SVG_HEIGHT - 1;

            return "<line x1=\"{$x1}\" y1=\"{$y}\" x2=\"{$x2}\" y2=\"{$y}\" class=\"outline\" shape-rendering=\"crispEdges\" />";
        })
            ->join("\n");

        $content .= '</svg>';

        return $content;
    }
}
