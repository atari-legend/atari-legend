<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Games</h2>
    </div>
    @include('games.partial_search_criteria')
    <div class="card-body p-2" id="results">
        @include('layouts.search_tabs', ['active' => 'games'])
        @if ($games->total() < 1)
            <p class="card-text text-muted text-center pt-3">No game found</p>
        @endif
        <div class="row">
            @foreach ($games as $game)
                <div class="col-4 text-center p-3 align-self-top">

                    <a href="{{ route('games.show', ['game' => $game]) }}" class="d-block position-relative mb-2">
                        @if ($game->screenshots->isNotEmpty())
                            <img class="w-100 d-block bg-dark" src="{{ $game->screenshots->random()->getUrlRoute('game', $game) }}" alt="Screenshot of {{ $game->name }}" loading="lazy">
                        @else
                            <img class="w-100 d-block bg-black" width="1209" height="756" src="{{ asset('images/no-screenshot.svg') }}" alt="No screenshot for {{ $game->name }}" loading="lazy">
                        @endif
                        @if ($game->comments_count > 0)
                            <span class="badge bg-primary-dark bg-opacity-75 text-shadow position-absolute bottom-0 start-0 m-1" title="{{ $game->comments_count }} {{ Str::plural('comment', $game->comments_count) }}">
                                <i class="fas fa-comment"></i> {{ $game->comments_count }}
                            </span>
                        @endif
                        @if ($game->votes_avg_score !== null)
                            <span class="badge bg-primary-dark bg-opacity-75 text-shadow position-absolute bottom-0 end-0 m-1" title="Score">
                                <i class="fas fa-star"></i> {{ number_format(GameHelper::normalizeScore((float) $game->votes_avg_score), 2) }}
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('games.show', ['game' => $game]) }}">{{ $game->name }}</a><br>

                    @if ($game->developers->isNotEmpty())
                        <span class="text-muted">by</span>
                        @forelse ($game->developers as $developer)
                            <a href="{{ route('games.search', ['developer' => $developer->name]) }}">{{ $developer->name }}</a>@if (!$loop->last), @endif
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
                    @if ($game->releases->pluck('medias')->flatten()->pluck('dumps')->isNotEmpty())
                        <i class="far fa-save fa-fw mt-1" title="as downloads"></i>
                    @endif
                    @if ($game->uses_ai)
                        <i class="fas fa-robot fa-fw mt-1" title="Uses AI"></i>
                    @endif
                    @if ($game->emulator_url !== null)
                        <a href="{{ $game->emulator_url }}" title="Play in the emulator" aria-label="Play {{ $game->name }} in the emulator">
                            <i class="fas fa-play fa-fw mt-1"></i>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        {{ $games->withQueryString()->fragment('results')->links() }}
    </div>
</div>
