<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Add fact</h2>

        <form action="{{ isset($fact)
            ? route('admin.games.game-facts.update', ['game' => $fact->game, 'fact' => $fact])
            : route('admin.games.game-facts.store', $game) }}"
            onkeydown="return event.key != 'Enter';"
            method="post" enctype="multipart/form-data">
            @csrf
            @isset($fact)
                @method('PUT')
            @endif

            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control @error('content') is-invalid @enderror sceditor" requiredx id="content" name="content" rows="15">{{ old('content', isset($fact) ? $fact->game_fact : '') }}</textarea>

                @error('content')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            @if (isset($fact) && $fact->screenshots->isNotEmpty())
                <div>
                    <label class="form-label">Current file</label>
                    @foreach ($fact->screenshots as $screenshot)
                        <img class="w-100" src="{{ $screenshot->getUrl('game_fact') }}">
                    @endforeach
                </div>
                <div class="mb-3 form-check">
                    <input class="form-check-input" type="checkbox" value="true" name="remove_file" id="remove_file">
                    <label class="form-check-label" for="remove_file">Remove file</label>
                </div>
            @endif

            <div class="mb-3">
                <label for="file" class="form-label">@if (isset($fact) && $fact->screenshots->isNotEmpty()) Replace @else Add @endif file</label>
                <input type="file" class="form-control @error('file') is-invalid @enderror" name="file[]" id="file" multiple>

                @error('file')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a class="btn btn-link" href="{{ route('admin.games.game-facts.index', $game) }}">Cancel</a>
        </form>

    </div>
</div>
