@if ($game->series !== null)
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Series</h2>
        </div>
        <div class="card-body p-2">
            <p class="card-text">
                The <em>{{ $game->series->name }}</em> series contains:
            </p>
            <ul class="list-unstyled ms-2">
                @foreach ($game->series->games->sortBy('game_name') as $g)
                    <li>
                        @if ($g->game_id === $game->game_id)
                            <i class="fas fa-caret-right text-muted"></i> <strong class="text-muted">{{ $g->game_name }}</strong>
                        @else
                            <a href="{{ route('games.show', ['game' => $g ]) }}">{{ $g->game_name }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
