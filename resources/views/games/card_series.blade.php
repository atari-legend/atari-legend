@if ($game->gameSeries !== null)
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Series</h2>
        </div>
        <div class="card-body p-2">
            <p class="card-text">
                The <em>{{ $game->gameSeries->name }}</em> series contains:
            </p>
            <ul class="list-unstyled ms-2">
                @foreach ($game->gameSeries->games->sortBy('name') as $g)
                    <li>
                        @if ($g->getKey() === $game->getKey())
                            <i class="fas fa-caret-right text-muted"></i> <strong class="text-muted">{{ $g->name }}</strong>
                        @else
                            <a href="{{ route('games.show', ['game' => $g ]) }}">{{ $g->name }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
