@if ($boxscans->isNotEmpty())
    <div class="card bg-dark mb-4 card-boxscans">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Boxscan</h2>
        </div>

        <div class="card-body p-2 lightbox-gallery">
            <div class="row mb-2">
                <div class="col">
                    <div class="carousel slide carousel-fade carousel-thumbnails-horizontal" id="carousel-boxscans" data-bs-ride="carousel">
                        <ol class="carousel-indicators">
                            @foreach($boxscans as $boxscan)
                                <li data-bs-target="#carousel-boxscans" data-bs-slide-to="{{ $loop->index }}" @if ($loop->first) class="active" @endif></li>
                            @endforeach
                        </ol>
                        <div class="carousel-inner">
                            @foreach ($boxscans as $boxscan)
                                <div class="carousel-item @if ($loop->first) active @endif">
                                    <a class="lightbox-link" href="{{ $boxscan['boxscan'] }}">
                                        <img class="w-100 d-block" src="{{ $boxscan['preview'] }}" alt="Large scan of the game box">
                                    </a>
                                    @if (isset($game) && $boxscan['release'] !== null)
                                        {{-- When displaing boxscans in the game page, display the specific
                                            release the scan is for --}}
                                        <div class="carousel-caption">
                                            <div class="fs-5 p-1">
                                            Release: {{ Helper::releaseName($boxscan['release']) }}
                                            @if ($boxscan['release']->publisher !== null)
                                                by {{ $boxscan['release']->publisher->pub_dev_name }}
                                            @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <a class="carousel-control-prev text-decoration-none" href="#carousel-boxscans" role="button" data-bs-slide="prev">
                            <span class="fas fa-chevron-left fa-3x"></span>
                            <span class="visually-hidden">Previous</span>
                        </a>
                        <a class="carousel-control-next text-decoration-none" href="#carousel-boxscans" role="button" data-bs-slide="next">
                            <i class="fas fa-chevron-right fa-3x"></i>
                            <span class="visually-hidden">Next</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col">
                    <div class="carousel-thumbnails d-flex flex-nowrap @if (count($boxscans) < 5) justify-content-center @endif overflow-hidden" data-bs-carousel="carousel-boxscans">
                        @foreach ($boxscans as $boxscan)
                            <a href="#carousel-boxscans" data-bs-slide-to="{{ $loop->index }}" @if ($loop->first) class="active" @endif><img class="me-2" src="{{ $boxscan['boxscan'] }}" alt="Thumbnail of other scans of the game box"></a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer text-muted text-center">
            <strong>{{ $boxscans->count() }}</strong> {{ Str::plural('boxscan', $boxscans->count() )}}
            @isset ($game)
                for {{ $game->releases->count() }} {{ Str::plural('release', $game->releases->count())}}
            @endif
            loaded
        </div>
    </div>
@endif
