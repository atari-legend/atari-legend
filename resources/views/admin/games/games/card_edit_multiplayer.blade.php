<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Multiplayer</h2>

        <form action="{{ route('admin.games.games.update.multiplayer', $game) }}" method="POST">
            @csrf

            <div class="mb-3 row row-cols-2">
                <div class="col">
                    <label for="players" class="form-label">Players - Same machine</label>
                    <input type="number" class="form-control @error('players') is-invalid @enderror"
                        name="players" id="players" value="{{ old('players', $game->number_players_on_same_machine ?? '') }}">

                    @error('players')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                    <label for="players_linked" class="form-label">Players - Linked machines</label>
                    <input type="number" class="form-control @error('players_linked') is-invalid @enderror"
                        name="players_linked" id="players_linked" value="{{ old('players_linked', $game->number_players_multiple_machines ?? '') }}">

                    @error('players_linked')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <div class="mb-3 row row-cols-2">
                <div class="col">
                    <label for="multiplayer_type" class="form-label">Type</label>
                    <select class="form-select @error('multiplayer_type') is-invalid @enderror" name="multiplayer_type">
                        <option value="">-</option>
                        @foreach (\App\Models\Game::MULTIPLAYER_TYPES as $type)
                            <option value="{{ $type }}" @if ($game->multiplayer_type === $type) selected @endif>{{ $type }}</option>
                        @endforeach
                    </select>

                    @error('multiplayer_type')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="col">
                    <label for="multiplayer_hardware" class="form-label">Hardware</label>
                    <select class="form-select @error('multiplayer_hardware') is-invalid @enderror" name="multiplayer_hardware">
                        <option value="">-</option>
                        @foreach (\App\Models\Game::MULTIPLAYER_HARDWARE as $hw)
                            <option value="{{ $hw }}" @if ($game->multiplayer_hardware === $hw) selected @endif>{{ $hw }}</option>
                        @endforeach
                    </select>

                    @error('multiplayer_hardware')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.games.games.index') }}" class="btn btn-link">Cancel</a>
        </form>

    </div>
</div>

