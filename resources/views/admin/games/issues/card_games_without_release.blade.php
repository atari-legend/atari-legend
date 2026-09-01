<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title">{{ $gamesWithoutRelease->count() }} {{ Str::plural('game', $gamesWithoutRelease->count()) }} without a release</h2>
        @foreach ($gamesWithoutRelease as $game)
            <a href="{{ route('admin.games.games.edit', $game) }}">{{ $game->name }}</a>@if(!$loop->last)<span class="me-2">,</span>@endif
        @endforeach
    </div>
</div>
