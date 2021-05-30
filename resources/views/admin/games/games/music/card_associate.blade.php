<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Possible candidates</h2>
        <p>
            <small class="text-muted">
                This section tries to find songs from the SNDH archive that
                match the game, based on the game name and song title. If a
                song looks like a correct candidate you can tick it and associat
                it with the game.
            </small>
        </p>

        @if ($sndhs->isNotEmpty())
            <form action="{{ route('admin.games.game-music.associate', $game) }}" method="POST">
                @csrf
                <ul class="list-unstyled">
                    @foreach ($sndhs as $sndh)
                        <li>
                            <input class="form-check-input" type="checkbox"
                                name="associations[]" value="{{ $sndh->id }}" id="{{ $sndh->id }}">
                            <label class="form-check-label" for="{{ $sndh->id }}">
                                {{ $sndh->title }} <small class="text-muted ms-2">{{ $sndh->id }}</small>
                            </label>
                        </li>
                    @endforeach
                </ul>

                <button type="submit" class="btn btn-primary">Associate</button>
            </form>
        @else
            <p class="card-text text-center text-muted">
                No candidate song found for the game.
            </p>
        @endif
    </div>
</div>
