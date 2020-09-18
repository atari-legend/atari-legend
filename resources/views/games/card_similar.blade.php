@if ($game->similarGames->isNotEmpty())
    @php
        $similar = $game->similarGames->random()
    @endphp
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Similar Game</h2>
        </div>
        <div class="card-body p-0">
            <a href="{{ route('games.show', ['game' => $similar]) }}">
                <img class="w-100" src="{{ asset('storage/images/game_screenshots/'.$similar->screenshots[0]->file) }}" alt="{{ $similar->game_name }}">
            </a>
        </div>
    </div>
@endif
