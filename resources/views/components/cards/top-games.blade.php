<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Top Games</h2>
    </div>
    <div class="card-body p-0 striped">
        <div class="p-0 striped">
                @foreach ($games as $game)
                    <div class="row align-items-center g-0 py-1">
                        <div class="col-3 text-center text-nowrap">
                            <div class="fs-5 text-audiowide text-primary mb-1">{{ $loop->index+1 }}</div>
                            <div>{{ number_format(GameHelper::normalizeScore($game->avgScore), 2) }}</div>
                            <span class="text-muted">{{ $game->numVotes }} {{ Str::plural('vote', $game->numVotes) }}</span>

                        </div>
                        <div class="col-3">
                            <a class="fs-4 d-inline-block" href="{{ route('games.show', $game) }}">
                                @if ($game->screenshots->isNotEmpty())
                                    <img class="img-fluid" src="{{ $game->screenshots->first()->getUrlRoute('game', $game) }}" alt="Screenshot of {{ e($game->game_name) }}">
                                @else
                                    <img class="img-fluid" src="{{ asset('images/no-screenshot.svg') }}" alt="No screenshot">
                                @endif
                            </a>
                        </div>
                        <div class="col-6 px-2">
                            <a class="fs-5 d-inline-block" href="{{ route('games.show', $game) }}">
                                {{ $game->game_name }}
                            </a>
                            <div class="mt-2">
                                {{ $game->genres->pluck('name')->join(', ') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </ul>
        </div>
    </div>
</div>
