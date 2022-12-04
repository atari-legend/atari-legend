<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Games</h2>

        <form class="mt-2 mb-4 row row-cols-lg-auto g-3" action="{{ route('admin.games.series.game.store', $series) }}"
            method="POST">
            @csrf
            <div class="col">
                <input class="autocomplete form-control @error('game_name') is-invalid @enderror" name="game_name"
                    id="game_name" type="search" data-autocomplete-endpoint="{{ route('admin.ajax.games') }}"
                    data-autocomplete-key="game_name" data-autocomplete-id="game_id" data-autocomplete-companion="game"
                    value="{{ old('game_name') }}" placeholder="Type a game name..." autocomplete="off" required>
                <input type="hidden" name="game" value="{{ old('game') }}">

                @error('developer')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Add game</button>
            </div>
        </form>



        @if ($series->games->isNotEmpty())
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($series->games->sortBy('game_name') as $game)
                        <tr>
                            <td><a href="{{ route('admin.games.games.edit', $game) }}">{{ $game->game_name }}</a></td>
                            <td>
                                <form
                                    action="{{ route('admin.games.series.game.destroy', ['series' => $series, 'game' => $game]) }}"
                                    method="POST"
                                    onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                    @csrf
                                    @method('DELETE')
                                    <button title="Remove game '{{ $game->game_name }}'" class="btn btn-sm">
                                        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                    </button>
                                </form>

                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">No games</p>
        @endif

    </div>
</div>
