<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title">{{ $gamesWithoutRelease->count() }} {{ Str::plural('game', $gamesWithoutRelease->count()) }} without a release</h2>
        @foreach ($gamesWithoutRelease as $game)
            <a href="{{ config('al.legacy.base_url').'/admin/games/games_detail.php?game_id='.$game->game_id }}">{{ $game->game_name }}</a>@if(!$loop->last)<span class="mr-2">,</span>@endif
        @endforeach
    </div>
</div>
