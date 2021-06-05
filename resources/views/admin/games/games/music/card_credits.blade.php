<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="fs-4">Game credits</h2>

        <p>
            <small class="text-muted">
                Check here that the musician is given the appropriate credits for the game music.
                <a href="{{ route('admin.games.game-credits.index', $game) }}">Edit credits</a>.
            </small>
        </p>

        @if ($game->individuals->isNotEmpty())
            <ul class="list-inline">
                @foreach ($game->individuals->sortBy('pivot.role.name') as $individual)
                    <li class="list-inline-item me-4">
                        {{ $individual->ind_name }}
                        @if ($individual->aka_list->isNotEmpty())
                            <small>aka. {{ $individual->aka_list->join(', ') }}</small>
                        @endif
                        @if ($individual->pivot->role !== null)
                            <span class="text-muted">[{{ $individual->pivot->role->name }}]</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="card-text text-center text-muted">
                No credits for this game.
            </p>
        @endif
    </div>
</div>
