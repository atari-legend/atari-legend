<div class="mb-3">
    <div class="form-label">Action</div>
    <div class="form-check">
        <input class="form-check-input" type="radio" name="action" id="create-release" value="create-release" @if(old('action', 'create-release') === 'create-release') checked @endif>
        <label class="form-check-label" for="create-release">
            Create a new release of a game
        </label>
        <span class="form-text ms-3">Use this option to add a new game release to a menu</span>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="radio" name="action" id="use-release" value="use-release" @if(old('action') === 'use-release') checked @endif>
        <label class="form-check-label" for="use-release">
            Use an existing release from the menu
        </label>
        <span class="form-text ms-3">
            Use this option to add a new doc / trainer / hint to a game of the menu (The game must already be on the menu).
            The <strong>subtype field is required</strong> in that case, to indicate the type of doc / hint / trainer / …
        </span>
    </div>
</div>

<div class="mb-3 collapse @if(old('action', 'create-release') === 'create-release') show @endif" id="action-create-release">
    <label for="game" class="form-label">Game</label>
    <select class="form-select @error('game') is-invalid @enderror"
        id="game" name="game">
        <option value="">-- Select --</option>
        @foreach ($games as $game)
            <option value="{{ $game->game_id }}" @if((int) old('game') === $game->game_id) selected @endif>{{ $game->game_name }}</option>
        @endforeach
    </select>
    <div class="form-text">A new release of the game will be created, for this menu</div>

    @error('game')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
    @enderror
</div>

<div class="mb-3 collapse @if(old('action') === 'use-release') show @endif" id="action-use-release">
    <label for="release" class="form-label">{{ Str::plural('Release', $diskReleases->count()) }} of this this menu <i>({{ $disk->menu->menuSet->name}} {{ $disk->menu->label }})</i></label>
    <select class="form-select @error('release') is-invalid @enderror"
        id="release" name="release">
        <option value="">-- Select --</option>
        @foreach ($diskReleases as $release)
            <option value="{{ $release->id }}" @if((int) old('release') === $release->id) selected @endif>{{ $release->game->game_name }}</option>
        @endforeach
    </select>
</div>
