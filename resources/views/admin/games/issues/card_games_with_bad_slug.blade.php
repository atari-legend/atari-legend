<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title">
            {{ $gamesWithBadSlug->count() }} {{ Str::plural('game', $gamesWithoutRelease->count()) }}
            with a bad URL slug
        </h2>
        @if ($gamesWithBadSlug->count() > 30)
            <p class="text-muted">A random selection of 30 games:</p>
        @endif

        @foreach ($gamesWithBadSlug->random(30) as $game)
            <a href="{{ route('admin.games.games.edit', $game) }}">{{ $game->game_name }}</a>
            <span class="text-muted">({{ $game->slug }})</span>
            @if (!$loop->last)
                <span class="me-2">,</span>
            @endif
        @endforeach
    </div>
</div>
