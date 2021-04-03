<div class="mb-3">
    <label for="game_name" class="form-label">Game</label>
    <input class="autocomplete form-control @error('game') is-invalid @enderror"
        name="game_name" id="game_name" type="search"
        data-autocomplete-endpoint="{{ route('ajax.games') }}"
        data-autocomplete-key="game_name" data-autocomplete-id="game_id"
        data-autocomplete-companion="game" value="{{ old('game_name') }}"
        placeholder="Type a game name..." autocomplete="off">
    <input type="hidden" name="game" value="{{ old('game') }}">

    @error('game')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>
