<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Similar games</h2>


        <form class="mt-2 mb-4 row row-cols-lg-auto g-3" action="{{ route('admin.games.game-similar.store', $game) }}"
            method="POST" enctype="multipart/form-data">
            @csrf
            <div class="col">
                <input class="autocomplete form-control @error('similar_name') is-invalid @enderror" name="similar_name"
                    id="similar_name" type="search" data-autocomplete-endpoint="{{ route('admin.ajax.games') }}"
                    data-autocomplete-key="game_name" data-autocomplete-id="game_id"
                    data-autocomplete-companion="similar" value="{{ old('similar_name') }}"
                    placeholder="Type a game name..." autocomplete="off" required>
                <input type="hidden" name="similar" value="{{ old('similar') }}">

                @error('similar')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col">
                <button type="submit" class="btn btn-success w-100">Add similar game</button>
            </div>
        </form>

        @if ($game->similarGames->isNotEmpty())
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($game->similarGames as $similar)
                        <tr>
                            <td><a href="{{ route('admin.games.games.edit', $similar) }}">{{ $similar->game_name }}</a>
                            </td>
                            <td>
                                <form
                                    action="{{ route('admin.games.game-similar.destroy', ['game' => $game, 'similar' => $similar]) }}"
                                    method="POST"
                                    onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                    @csrf
                                    @method('DELETE')
                                    <button title="Delete similar game '{{ $similar->game_name }}'" class="btn btn-sm">
                                        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                    </button>
                                </form>

                            </td>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="card-text text-center text-muted">
                No similar games.
            </p>
        @endif

    </div>
</div>
