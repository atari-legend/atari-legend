<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title">{{ $gamesWithoutScreenshot->count() }} {{ Str::plural('game', $gamesWithoutScreenshot->count()) }} without screenshots</h2>
        <p class="card-text">
            @if ($gamesWithoutScreenshot->count() > 30)
                <p class="text-muted">A random selection of 30 games:</p>
            @endif

            @foreach ($gamesWithoutScreenshot->shuffle()->take(30) as $game)
                <a href="{{ route('admin.games.games.edit', $game) }}">{{ $game->game_name }}</a>@if(!$loop->last)<span class="me-2">,</span>@endif
            @endforeach
        </p>
    </div>
</div>
