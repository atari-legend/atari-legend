<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Credits</h2>

        <h3 class="fs-5">Games</h3>
        @if ($individual->games->isNotEmpty())
            <ul>
                @foreach ($individual->games->sortBy('game_name') as $game)
                    <li><a href="{{ route('admin.games.game-credits.index', $game) }}">{{ $game->game_name }}</a>
                        <span class="text-muted">{{ $game->pivot->role?->name }}</span></li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">No games credits</p>
        @endif

        <h3 class="fs-5">Interviews</h3>
        @if ($individual->interviews->isNotEmpty())

            <ul>
                @foreach ($individual->interviews as $interview)
                    <li><a href="{{ route('interviews.show', $interview) }}">{{ $individual->ind_name }}</a></li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">No interviews</p>
        @endif

        <h3 class="fs-5">Crews membership</h3>
        @if ($individual->crews->isNotEmpty())
            <ul>
                @foreach ($individual->crews->sortBy('crew_name') as $crew)
                    <li><a href="{{ route('admin.menus.crews.edit', $crew) }}">{{ $crew->crew_name }}</a></li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">No crews membership</p>
        @endif
    </div>
</div>
