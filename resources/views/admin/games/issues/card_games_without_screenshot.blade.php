<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title">{{ $gamesWithoutScreenshot->count() }} {{ Str::plural('game', $gamesWithoutScreenshot->count()) }} without screenshots</h2>
        <p class="card-text">
            @if ($gamesWithoutScreenshot->count() > 30)
                <span class="text-muted">A random selection of 30 games:</span><br>
            @endif

            @foreach ($gamesWithoutScreenshot->random(30) as $game)
                @if ($loop->index > 30)
                    …
                    @break
                @endif
                <a href="{{ config('al.legacy.base_url').'/admin/games/games_detail.php?game_id='.$game->game_id }}">{{ $game->game_name }}</a>@if(!$loop->last)<span class="me-2">,</span>@endif
            @endforeach
        </p>
    </div>
</div>
