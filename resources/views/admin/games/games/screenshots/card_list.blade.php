<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Screenshots</h2>

        <form class="mt-2 mb-4 row row-cols-lg-auto g-3 align-items-center g-3 align-items-center"
            action="{{ route('admin.games.game-screenshots.store', $game) }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            <div class="col-12">
                <input type="file" class="form-control @error('screenshot') is-invalid @enderror"
                    name="screenshot[]" multiple required>

                @error('screenshot')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Add screenshot</button>
            </div>
        </form>

        @forelse ($game->screenshots as $screenshot)
            <div class="d-inline-block position-relative m-2">
                <form action="{{ route('admin.games.game-screenshots.destroy', ['game' => $game, 'screenshot' => $screenshot]) }}" method="POST"
                    onsubmit="javascript:return confirm('This screenshot will be deleted')">
                    @csrf
                    @method('DELETE')

                    <img class="border border-dark" src="{{ $screenshot->getUrl('game') }}" style="max-width: 320px;">

                    <button title="Remove screenshot" class="btn position-absolute end-0 pe-4 pt-4">
                        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        @empty
            <p class="card-text text-center text-muted">
                No screenshots for the game yet.
            </p>
        @endforelse

    </div>
</div>
