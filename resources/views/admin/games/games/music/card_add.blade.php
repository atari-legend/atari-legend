<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Find & associate a song</h2>
        <p>
            <small class="text-muted">
                This section is to associate a song with the game, if it's not
                found in the list of possible candidates. Start typing the name
                of a song to find it in the SNDH archive and associate it.
            </small>
        </p>

        <form class="mb-5" action="{{ route('admin.games.game-music.store', $game) }}" method="POST">
            @csrf
            <div class="row mb-3">
                <label for="song" class="form-label">Song</label>
                <div class="col-9">
                    <input class="form-control autocomplete @error('song') is-invalid @enderror"
                        type="search" id="song" name="song" value="{{ old('song') }}" required
                        data-autocomplete-endpoint="{{ route('admin.ajax.sndh') }}"
                        data-autocomplete-key="display" data-autocomplete-id="id"
                        data-autocomplete-companion="sndh" autocomplete="off"
                        placeholder="Type a song name (e.g. 'Toki')">
                    <input type="hidden" name="sndh" value="{{ old('sndh') }}">

                    @error('sndh')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror

                </div>
                <div class="col-3">
                    <button type="submit" class="btn btn-success w-100">Add song</button>
                </div>
            </div>
        </form>

    </div>
</div>
