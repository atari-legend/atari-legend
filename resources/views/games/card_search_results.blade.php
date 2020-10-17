<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Games</h2>
    </div>
    <div class="card-body p-2">
        <form method="get" action="{{ route('games.search') }}">
            <div class="row mb-3">
                <label for="title" class="col-3 col-xxl-2 col-form-label">Title</label>
                <div class="col position-relative">
                    <input type="text" class="autocomplete form-control"
                        data-autocomplete-endpoint="{{ URL::to('/ajax/games.json/') }}"
                        data-autocomplete-key="game_name"
                        id="title" name="title" autocomplete="off">
                </div>
            </div>
            <div class="row mb-3">
                <label for="publisher" class="col-3 col-xxl-2 col-form-label">
                    Publisher
                    <a href="#" data-dropdown-toggle="publisher,publisher_id"><i class="fas fa-chevron-circle-down"></i></a>
                </label>
                <div class="col position-relative">
                    <input type="text" class="autocomplete form-control"
                        data-autocomplete-endpoint="{{ URL::to('/ajax/companies.json') }}"
                        data-autocomplete-key="pub_dev_name"
                        id="publisher" name="publisher" autocomplete="off">
                    <select class="form-select d-none" id="publisher_id" name="publisher_id">
                        <option value="">-</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->pub_dev_id }}">{{ $company->pub_dev_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <label for="developer" class="col-3 col-xxl-2 col-form-label">
                    Developer
                    <a href="#" data-dropdown-toggle="developer,developer_id"><i class="fas fa-chevron-circle-down"></i></a>
                </label>
                <div class="col position-relative">
                    <input type="text" class="autocomplete form-control"
                        data-autocomplete-endpoint="{{ URL::to('/ajax/companies.json') }}"
                        data-autocomplete-key="pub_dev_name"
                        id="developer" name="developer" autocomplete="off">
                    <select class="form-select d-none" id="developer_id" name="developer_id">
                        <option value="">-</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->pub_dev_id }}">{{ $company->pub_dev_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <label for="year" class="col-3 col-xxl-2 col-form-label">
                    Release year
                    <a href="#" data-dropdown-toggle="year,year_id"><i class="fas fa-chevron-circle-down"></i></a>
                </label>
                <div class="col position-relative">
                    <input type="text" class="autocomplete form-control"
                        data-autocomplete-endpoint="{{ URL::to('/ajax/release-years.json') }}"
                        data-autocomplete-key="year"
                        id="year" name="year" autocomplete="off">
                    <select class="form-select d-none" id="year_id" name="year_id">
                        <option value="">-</option>
                        @foreach ($years as $year)
                            <option value="{{ $year->year }}">{{ $year->year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
    <div class="card-body p-2" id="results">
        <h3 class="text-center text-h5">{{ $games->total() }} games found</h3>
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
                    @if (GameHelper::hasBoxscan($game))
                        <i class="fas fa-box fa-fw mt-1" title="Has boxscans"></i>
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
