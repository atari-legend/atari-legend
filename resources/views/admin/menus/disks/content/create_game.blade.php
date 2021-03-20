<div class="mb-3">
    <label for="game" class="form-label">Game</label>
    <select class="form-select @error('game') is-invalid @enderror"
        id="game" name="game">
        <option value="">-- Select --</option>
        @foreach ($games as $game)
            <option value="{{ $game->game_id }}" @if((int) old('game') === $game->game_id) selected @endif>{{ $game->game_name }}</option>
        @endforeach

    </select>

    @error('game')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
