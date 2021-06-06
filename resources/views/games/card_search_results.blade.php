<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Games</h2>
    </div>
    <div class="card-body p-2">
        <form method="get" action="{{ route('games.search') }}">
            <div class="row mb-3">
                <label for="title" class="col-4 col-sm-3 col-form-label text-nowrap">Title</label>
                <div class="col position-relative">
                    <input type="text" class="autocomplete form-control"
                        data-autocomplete-endpoint="{{ route('ajax.games') }}"
                        data-autocomplete-key="game_name" data-autocomplete-submit="true"
                        value="{{ old('title', $title) }}"
                        id="title" name="title" autocomplete="off">
                </div>
            </div>
            <div class="row mb-3">
                <label for="publisher" class="col-4 col-sm-3 col-form-label text-nowrap">
                    Publisher
                    <a href="#" data-dropdown-toggle="publisher,publisher_id"><i class="fas fa-chevron-circle-down"></i></a>
                </label>
                <div class="col position-relative">
                    <input type="text" class="autocomplete form-control @isset ($publisher_id) d-none @endif"
                        data-autocomplete-endpoint="{{ route('ajax.companies') }}"
                        data-autocomplete-key="pub_dev_name"
                        value="{{ old('publisher', $publisher) }}"
                        id="publisher" name="publisher" autocomplete="off">
                    <select class="form-select @if (!isset($publisher_id)) d-none @endif" id="publisher_id" name="publisher_id">
                        <option value="">-</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->pub_dev_id }}" @if (isset($publisher_id) && (int) $publisher_id === $company->pub_dev_id) selected @endif>{{ $company->pub_dev_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <label for="developer" class="col-4 col-sm-3 col-form-label text-nowrap">
                    Developer
                    <a href="#" data-dropdown-toggle="developer,developer_id"><i class="fas fa-chevron-circle-down"></i></a>
                </label>
                <div class="col position-relative">
                    <input type="text" class="autocomplete form-control @isset ($developer_id) d-none @endif"
                        data-autocomplete-endpoint="{{ route('ajax.companies') }}"
                        data-autocomplete-key="pub_dev_name"
                        value="{{ old('developer', $developer) }}"
                        id="developer" name="developer" autocomplete="off">
                    <select class="form-select @if (!isset($developer_id)) d-none @endif" id="developer_id" name="developer_id">
                        <option value="">-</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->pub_dev_id }}" @if (isset($developer_id) && (int) $developer_id === $company->pub_dev_id) selected @endif>{{ $company->pub_dev_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <label for="year" class="col-4 col-sm-3 col-form-label text-nowrap">
                    Release year
                    <a href="#" data-dropdown-toggle="year,year_id"><i class="fas fa-chevron-circle-down"></i></a>
                </label>
                <div class="col position-relative">
                    <input type="text" class="autocomplete form-control @isset ($year_id) d-none @endif"
                        data-autocomplete-endpoint="{{ route('ajax.release-years') }}"
                        data-autocomplete-key="year"
                        value="{{ old('year', $year) }}"
                        id="year" name="year" autocomplete="off">
                    <select class="form-select @if (!isset($year_id)) d-none @endif" id="year_id" name="year_id">
                        <option value="">-</option>
                        @foreach ($years as $year)
                            <option value="{{ $year->year }}" @if (isset($year_id) && $year_id === $year->year) selected @endif>{{ $year->year }}</option>
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
        @include('layouts.search_tabs', ['active' => 'games'])
        @if ($games->total() < 1)
            <p class="card-text text-muted text-center pt-3">No game found</p>
        @endif
        <div class="row">
            @foreach ($games as $game)
                <div class="col-4 text-center p-3 align-self-top">

                    <a href="{{ route('games.show', ['game' => $game]) }}">
                        @if ($game->screenshots->isNotEmpty())
                            <img class="w-100 mb-2 bg-dark" src="{{ $game->screenshots->random()->getUrl('game') }}" alt="Screenshot of {{ $game->game_name }}">
                        @else
                            <img class="w-100 mb-2 bg-black" src="{{ asset('images/no-screenshot.png') }}">
                        @endif
                    </a>

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
                    @if ($game->sndhs->isNotEmpty())
                        <i class="fas fa-music fa-fw mt-1" title="{{ $game->sndhs->count() }} {{ Str::plural('music', $game->sndhs->count()) }}"></i>
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
