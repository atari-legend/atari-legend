<?php

namespace Tests\Feature\Helpers;

use App\Helpers\GameVoteHelper;
use App\Models\Game;
use App\Models\GameVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The vote distribution is drawn as a small inline SVG next to a game. Bars are
 * scaled against the most-voted score, so the shape of the chart depends on the
 * whole distribution rather than on each bar alone.
 */
class GameVoteHelperTest extends TestCase
{
    use RefreshDatabase;

    private function vote(Game $game, int $score, int $times = 1): void
    {
        for ($i = 0; $i < $times; $i++) {
            GameVote::forceCreate([
                'game_id' => $game->getKey(),
                'user_id' => User::factory()->create()->getKey(),
                'score'   => $score,
            ]);
        }
    }

    /**
     * Pull the bar heights out of the SVG, in score order.
     *
     * @return float[]
     */
    private function barHeights(string $svg): array
    {
        preg_match_all('/<rect [^>]*height="([^"]*)" class="score-(\d)"/', $svg, $matches, PREG_SET_ORDER);

        $heights = [];
        foreach ($matches as $match) {
            $heights[(int) $match[2]] = (float) $match[1];
        }

        ksort($heights);

        return $heights;
    }

    public function test_every_score_gets_a_bar_and_a_baseline(): void
    {
        $game = Game::factory()->create();
        $this->vote($game, 4);

        $svg = GameVoteHelper::getVoteDistributionSvg($game);

        $this->assertSame(5, substr_count($svg, '<rect'));
        $this->assertSame(5, substr_count($svg, '<line'));
        $this->assertStringContainsString('</svg>', $svg);
    }

    /**
     * The tallest bar fills the chart; everything else is a fraction of it.
     */
    public function test_bars_are_scaled_against_the_most_voted_score(): void
    {
        $game = Game::factory()->create();
        $this->vote($game, 4, times: 4);
        $this->vote($game, 2, times: 2);

        $heights = $this->barHeights(GameVoteHelper::getVoteDistributionSvg($game));

        // SVG_HEIGHT - 3
        $this->assertSame(12.0, $heights[4]);
        $this->assertSame(6.0, $heights[2]);
        $this->assertSame(0.0, $heights[0]);
    }

    public function test_scores_nobody_picked_have_no_height(): void
    {
        $game = Game::factory()->create();
        $this->vote($game, 0);

        $heights = $this->barHeights(GameVoteHelper::getVoteDistributionSvg($game));

        $this->assertSame(12.0, $heights[0]);
        $this->assertSame([0.0, 0.0, 0.0, 0.0], [$heights[1], $heights[2], $heights[3], $heights[4]]);
    }

    /**
     * Votes on another game must not leak into this one's chart.
     */
    public function test_only_the_games_own_votes_are_counted(): void
    {
        $game = Game::factory()->create();
        $other = Game::factory()->create();

        $this->vote($game, 4);
        $this->vote($other, 0, times: 10);

        $heights = $this->barHeights(GameVoteHelper::getVoteDistributionSvg($game));

        $this->assertSame(12.0, $heights[4]);
        $this->assertSame(0.0, $heights[0]);
    }

    public function test_the_chart_is_wide_enough_for_five_bars(): void
    {
        $game = Game::factory()->create();
        $this->vote($game, 3);

        $svg = GameVoteHelper::getVoteDistributionSvg($game);

        // 5 bars of 15, each followed by 2 of spacing
        $this->assertStringContainsString('width="85"', $svg);
        $this->assertStringContainsString('viewBox="0 0 85 15"', $svg);
    }

    /**
     * Bars are laid out left to right with a fixed gap, so their x positions
     * are a straight arithmetic series.
     */
    public function test_bars_are_evenly_spaced(): void
    {
        $game = Game::factory()->create();
        $this->vote($game, 1);

        $svg = GameVoteHelper::getVoteDistributionSvg($game);

        foreach ([0 => 0, 1 => 17, 2 => 34, 3 => 51, 4 => 68] as $score => $x) {
            $this->assertStringContainsString("class=\"score-{$score}\" x=\"{$x}\"", $svg);
        }
    }
}
