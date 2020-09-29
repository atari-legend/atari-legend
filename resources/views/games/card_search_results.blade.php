<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Games</h2>
    </div>
    <div class="card-body p-2">
        <form method="get" action="{{ route('games.search') }}">
            <div class="row mb-3">
                <label for="title" class="col-sm-2 col-form-label">Title</label>
                <div class="col-sm-10 position-relative">
                    <input type="text" class="autocomplete form-control"
                        data-autocomplete-endpoint="{{ URL::to('/ajax/games.json/') }}"
                        data-autocomplete-key="game_name"
                        id="title" name="title" autocomplete="off">
                </div>
            </div>
            <div class="row mb-3">
                <label for="publisher" class="col-sm-2 col-form-label">Publisher</label>
                <div class="col-sm-10 position-relative">
                    <input type="text" class="autocomplete form-control"
                        data-autocomplete-endpoint="{{ URL::to('/ajax/companies.json') }}"
                        data-autocomplete-key="pub_dev_name"
                        id="publisher" name="publisher" autocomplete="off">
                </div>
            </div>
            <div class="row mb-3">
                <label for="developer" class="col-sm-2 col-form-label">Developer</label>
                <div class="col-sm-10 position-relative">
                    <input type="text" class="autocomplete form-control"
                        data-autocomplete-endpoint="{{ URL::to('/ajax/companies.json') }}"
                        data-autocomplete-key="pub_dev_name"
                        id="developer" name="developer" autocomplete="off">
                </div>
            </div>
            <div class="row mb-3">
                <label for="year" class="col-sm-2 col-form-label">Release year</label>
                <div class="col-sm-10 position-relative">
                    <input type="text" class="autocomplete form-control"
                        data-autocomplete-endpoint="{{ URL::to('/ajax/release-years.json') }}"
                        data-autocomplete-key="year"
                        id="year" name="year" autocomplete="off">
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
                            <img class="w-100 mb-2 bg-dark" src="{{ asset('storage/images/game_screenshots/'.$game->screenshots->random()->file) }}" alt="Screenshot of {{ $game->game_name }}">
                        </a>
                    @endif

                    <a href="{{ route('games.show', ['game' => $game]) }}">{{ $game->game_name }}</a><br>

                    @if ($game->developers->isNotEmpty())
                        <span class="text-muted">by</span>
                        @forelse ($game->developers as $developer)
                            <a href="{{ route('games.search', ['developer' => $developer->pub_dev_name]) }}">{{ $developer->pub_dev_name }}</a>@if (!$loop->last), @endif
                        @endforeach
                    @else
                        <span class="text-muted">n/a</span>
                    @endif
                    <br>

                    @if ($game->screenshots->isNotEmpty())
                        <i class="fas fa-camera fa-fw mt-1" title="{{ $game->screenshots->count() }} {{ Str::plural('screenshot', $game->screenshots->count()) }}"></i>
                    @endif
                    @if ($game->releases->isNotEmpty())
                        @foreach ($game->releases as $release)
                            @if ($release->boxscans->isNotEmpty())
                                <i class="fas fa-box fa-fw mt-1" title="Has boxscans"></i>
                                @break
                            @endif
                        @endforeach
                    @endif
                    @if ($game->musics->isNotEmpty())
                        <i class="fas fa-music fa-fw mt-1" title="{{ $game->musics->count() }} {{ Str::plural('music', $game->musics->count()) }}"></i>
                    @endif
                    @if ($game->reviews->isNotEmpty())
                        <i class="fas fa-newspaper fa-fw mt-1" title="{{ $game->reviews->count() }} {{ Str::plural('review', $game->reviews->count()) }}"></i>
                    @endif
                </div>
            @endforeach
        </div>

        {{ $games->withQueryString()->fragment('results')->links() }}
    </div>
</div>
