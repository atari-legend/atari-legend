<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Add a song</h2>

        <form class="mb-5" action="{{ route('admin.games.game-music.store', $game) }}" method="POST">
            @csrf
            <div class="row mb-3">
                <label for="song" class="form-label">Song</label>
                <div class="col-9">
                    <input class="form-control autocomplete @error('song') is-invalid @enderror"
                        type="search" id="song" name="song" value="{{ old('song') }}" required
                        data-autocomplete-endpoint="{{ route('admin.ajax.sndh') }}"
                        data-autocomplete-key="display" data-autocomplete-id="id"
                        data-autocomplete-companion="sndh" autocomplete="off">
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
