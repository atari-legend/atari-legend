<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Games</h2>
    </div>
    <div class="card-body p-2">
        <form method="get" action="search">
            <div class="row mb-3">
                <label for="title" class="col-sm-2 col-form-label">Title</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="title" name="title">
                </div>
            </div>
            <div class="row mb-3">
                <label for="publisher" class="col-sm-2 col-form-label">Publisher</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="publisher" name="publisher">
                </div>
            </div>
            <div class="row mb-3">
                <label for="developer" class="col-sm-2 col-form-label">Developer</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="developer" name="developer">
                </div>
            </div>
            <div class="row mb-3">
                <label for="year" class="col-sm-2 col-form-label">Release year</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="year" name="year">
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
    <div class="card-body p-2" id="results">
        <h5 class="text-center">{{ $games->total() }} games found</h5>
        <div class="row">
            @foreach ($games as $game)
                <div class="col-4 text-center p-3 align-self-center">
                    @if ($game->screenshots->isNotEmpty())
                    <a href="{{ route('games.show', ['game' => $game]) }}">
                        <img class="w-100 mb-2 bg-dark" src="{{ asset('storage/images/game_screenshots/'.$game->screenshots->get(0)->file) }}">
                    </a>
                    @endif

                    <a href="{{ route('games.show', ['game' => $game]) }}">{{ $game->game_name }}</a><br>

                    @forelse ($game->developers as $developer)
                        <!-- FIXME: Links to search on developer -->
                        {{ $developer->pub_dev_name }}<br>
                    @empty
                        n/a<br>
                    @endforelse

                    @if ($game->screenshots->isNotEmpty())
                        <i class="fas fa-camera"></i>
                    @endif
                    @if ($game->musics->isNotEmpty())
                        <i class="fas fa-music"></i>
                    @endif
                    @if ($game->reviews->isNotEmpty())
                        <i class="fas fa-newspaper"></i>
                    @endif
                </div>
            @endforeach
        </div>

        {{ $games->fragment('results')->links() }}
    </div>
</div>
