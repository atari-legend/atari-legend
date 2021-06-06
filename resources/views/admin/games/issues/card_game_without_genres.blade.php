<div class="card mb-3 bg-light">
    <div class="card-body p-0">
        @if ($gameWithoutGenre->screenshots->isNotEmpty())
            <div class="clearfix">
                @foreach ($gameWithoutGenre->screenshots->take(4) as $screenshot)
                    <img class="w-50 pixelated float-start" src="{{ $screenshot->getUrl('game') }}" alt="Screenshot of {{ $gameWithoutGenre->game_name }}">
                @endforeach
            </div>
        @endif
        <h2 class="card-title px-2 my-3">
            {{ $gameWithoutGenre->game_name }}
            <span class="text-muted fs-6">genres are…</span>
        </h2>
        <form class="px-2" method="post" action="{{ route('admin.games.issues.genres', ['game' => $gameWithoutGenre]) }}">
            @csrf
            <ul class="list-unstyled mb-3">
            @foreach ($genres as $genre)
                <li class="w-45 d-inline-block">
                    <input type="checkbox" class="form-check-input" id="genre_{{ $genre->id }}" name="genres[]" value="{{ $genre->id }}">
                    <label for="genre_{{ $genre->id }}" class="form-check-label">{{ $genre->name }}</label>
                </li>
            @endforeach
            </ul>
            <button type="submit" class="btn btn-primary">Set genres</button>
            </form>
        </p>

    </div>
</div>
