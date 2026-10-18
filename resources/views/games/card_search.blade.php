<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Games</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            Welcome to the game section. The place to be for every Atari ST and retro
            gaming nutcase. Enjoy your stay! Search for a game by using any of the
            functionalities below. Combinations are allowed. There are currently {{ $gamesCount }}
            games in the database. Check out the statistics on the database in the 'Statistics'
            card! If there is data missing and you are willing to contribute, please get in touch with
            the team.
        </p>
    </div>
    <div class="card-body p-2">
        <div class="row mb-2 text-center">
            <ul class="list-inline">
                <li class="list-inline-item mx-0 my-1">
                    <a href="{{ route('games.search', ['titleAZ' => '0-9']) }}" class="m-1">#</a>
                </li>
                @foreach (range('A', 'Z') as $letter)
                    <li class="list-inline-item mx-0 my-0">
                        <a href="{{ route('games.search', ['titleAZ' => $letter]) }}" class="m-1">{{ $letter }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
        <form method="get" action="{{ route('games.search') }}">
            @php
                // A search on a single year from elsewhere on the site comes
                // back here as a range of one
                $yearFrom = request('year_from') ?? request('year') ?? request('year_id');
                $yearTo = request('year_to') ?? request('year') ?? request('year_id');
            @endphp

            <fieldset class="mb-4">
                <legend class="fs-5 text-audiowide border-bottom border-2 border-black pb-2 mb-3">Search</legend>
                <div class="row mb-3">
                    <label for="title" class="col-5 col-sm-4 col-md-3 col-form-label">Title</label>
                    <div class="col d-flex gap-2 position-relative">
                        <select class="form-select w-auto" id="titleAZ" name="titleAZ" aria-label="Title starts with">
                            <option value="">A-Z</option>
                            <option value="0-9" @selected(request('titleAZ') === '0-9')>0-9</option>
                            @foreach (range('A', 'Z') as $letter)
                                <option value="{{ $letter }}" @selected(request('titleAZ') === $letter)>{{ $letter }}</option>
                            @endforeach
                        </select>
                        <input type="text" class="autocomplete form-control"
                            data-autocomplete-endpoint="{{ route('ajax.games') }}"
                            data-autocomplete-key="name" data-autocomplete-submit="true"
                            value="{{ request('title') }}"
                            id="title" name="title" autocomplete="off">
                    </div>
                </div>
                @include('games.partial_search_lookup', [
                    'param'    => 'publisher',
                    'label'    => 'Publisher',
                    'endpoint' => route('ajax.companies'),
                    'key'      => 'name',
                    'options'  => $companies->pluck('name', 'id'),
                ])
                @include('games.partial_search_lookup', [
                    'param'    => 'developer',
                    'label'    => 'Developer',
                    'endpoint' => route('ajax.companies'),
                    'key'      => 'name',
                    'options'  => $companies->pluck('name', 'id'),
                ])
                <div class="row mb-3">
                    <label for="individual" class="col-5 col-sm-4 col-md-3 col-form-label">
                        Involving
                        <a href="#" data-dropdown-toggle="individual,individual_id" title="Pick from a list instead"><i class="fas fa-chevron-circle-down"></i></a>
                    </label>
                    <div class="col position-relative">
                        <input type="text" class="autocomplete form-control @if (request()->filled('individual_id')) d-none @endif"
                            data-autocomplete-endpoint="{{ route('ajax.individuals') }}"
                            data-autocomplete-key="name"
                            data-autocomplete-id="id" data-autocomplete-companion="individual_select"
                            value="{{ request('individual') }}"
                            id="individual" name="individual" autocomplete="off">
                        <input type="hidden" name="individual_select" value="{{ request('individual_select') }}">
                        <select class="form-select @unless (request()->filled('individual_id')) d-none @endunless" id="individual_id" name="individual_id">
                            <option value="">-</option>
                            @foreach ($individuals as $individual)
                                <option value="{{ $individual->getKey() }}" @selected((int) request('individual_id') === $individual->getKey())>{{ $individual->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="year_from" class="col-5 col-sm-4 col-md-3 col-form-label">Release year</label>
                    <div class="col d-flex align-items-center gap-2">
                        <select class="form-select" id="year_from" name="year_from">
                            <option value="">-</option>
                            @foreach ($years as $year)
                                <option value="{{ $year->year }}" @selected((string) $yearFrom === (string) $year->year)>{{ $year->year }}</option>
                            @endforeach
                        </select>
                        <label for="year_to" class="text-muted">to</label>
                        <select class="form-select" id="year_to" name="year_to">
                            <option value="">-</option>
                            @foreach ($years as $year)
                                <option value="{{ $year->year }}" @selected((string) $yearTo === (string) $year->year)>{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="fs-5 text-audiowide border-bottom border-2 border-black pb-2 mb-3">Categorisation</legend>
                @include('games.partial_search_lookup', [
                    'param'    => 'genre',
                    'label'    => 'Genre',
                    'endpoint' => route('ajax.genres'),
                    'key'      => 'name',
                    'options'  => $genres->pluck('name', 'id'),
                ])
                @include('games.partial_search_lookup', [
                    'param'    => 'engine',
                    'label'    => 'Engine',
                    'endpoint' => route('ajax.engines'),
                    'key'      => 'name',
                    'options'  => $engines->pluck('name', 'id'),
                ])
                @foreach ([
                    'series_id'               => ['Series', $series],
                    'programming_language_id' => ['Programming language', $programmingLanguages],
                ] as $param => [$label, $options])
                    <div class="row mb-3">
                        <label for="{{ $param }}" class="col-5 col-sm-4 col-md-3 col-form-label">{{ $label }}</label>
                        <div class="col">
                            <select class="form-select" id="{{ $param }}" name="{{ $param }}">
                                <option value="">-</option>
                                @foreach ($options as $option)
                                    <option value="{{ $option->getKey() }}" @selected((int) request($param) === $option->getKey())>{{ $option->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </fieldset>

            <fieldset class="mb-4">
                <legend class="fs-5 text-audiowide border-bottom border-2 border-black pb-2 mb-3">Media</legend>
                <div class="row">
                    @foreach (['screenshot', 'boxscan', 'music', 'video', 'download'] as $param)
                        @include('games.partial_search_yesno', ['param' => $param, 'label' => \App\Http\Controllers\GameSearchController::FLAGS[$param][1]])
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="fs-5 text-audiowide border-bottom border-2 border-black pb-2 mb-3">Coverage</legend>
                <div class="row">
                    @foreach (['review', 'magazine', 'menu'] as $param)
                        @include('games.partial_search_yesno', ['param' => $param, 'label' => \App\Http\Controllers\GameSearchController::FLAGS[$param][1]])
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="fs-5 text-audiowide border-bottom border-2 border-black pb-2 mb-3">Gameplay</legend>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="row mb-3">
                            <label for="players" class="col-5 col-sm-4 col-md-6 col-form-label">Min. players</label>
                            <div class="col">
                                <select class="form-select" id="players" name="players">
                                    <option value="">-</option>
                                    @foreach (\App\Http\Controllers\GameSearchController::PLAYERS as $players)
                                        <option value="{{ $players }}" @selected((int) request('players') === $players)>{{ $players }}+</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="row mb-3">
                            <label for="multiplayer" class="col-5 col-sm-4 col-md-6 col-form-label">Multiplayer</label>
                            <div class="col">
                                <select class="form-select" id="multiplayer" name="multiplayer">
                                    <option value="">-</option>
                                    @foreach (\App\Models\Game::MULTIPLAYER_TYPES as $type)
                                        <option value="{{ $type }}" @selected(request('multiplayer') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="control_id" class="col-5 col-sm-4 col-md-3 col-form-label">Controls</label>
                    <div class="col col-sm-4 col-md-3">
                        <select class="form-select" id="control_id" name="control_id">
                            <option value="">-</option>
                            @foreach ($controls as $control)
                                <option value="{{ $control->getKey() }}" @selected((int) request('control_id') === $control->getKey())>{{ $control->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="fs-5 text-audiowide border-bottom border-2 border-black pb-2 mb-3">Miscellaneous</legend>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="row mb-3">
                            <label for="license" class="col-5 col-sm-4 col-md-6 col-form-label">License</label>
                            <div class="col">
                                <select class="form-select" id="license" name="license">
                                    <option value="">-</option>
                                    @foreach (\App\Models\GameRelease::LICENSES as $license)
                                        <option value="{{ $license }}" @selected(request('license') === $license)>{{ $license }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    @include('games.partial_search_yesno', ['param' => 'ai', 'label' => 'Made with AI'])
                </div>
                <div class="row mb-3">
                    <div class="col offset-5 offset-sm-4 offset-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="export" name="export" @checked(request()->filled('export'))>
                            <label class="form-check-label" for="export">Export mode</label>
                        </div>
                    </div>
                </div>
            </fieldset>

            <div class="text-center">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Search games</button>
            </div>
        </form>
    </div>
</div>
