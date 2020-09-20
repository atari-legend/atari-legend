<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Boxscan</h2>
    </div>

    <div class="card-body p-2">
        @if ($boxscans->isNotEmpty())
            <div class="row">
                <div class="col">
                    <div class="carousel slide carousel-fade" id="carousel-boxscans" data-ride="carousel">
                        <ol class="carousel-indicators">
                            @foreach($boxscans as $boxscan)
                                <li data-target="#carousel-boxscans" data-slide-to="{{ $loop->index }}" @if ($loop->first) class="active" @endif></li>
                            @endforeach
                        </ol>
                        <div class="carousel-inner">
                            @foreach ($boxscans as $boxscan)
                                <div class="carousel-item @if ($loop->first) active @endif">
                                    <img class="w-100 d-block" src="{{ $boxscan }}" alt="Large scan of the game box">
                                </div>
                            @endforeach
                        </div>
                        <a class="carousel-control-prev text-decoration-none" href="#carousel-boxscans" role="button" data-slide="prev">
                            <span class="fas fa-chevron-left fa-3x"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next text-decoration-none" href="#carousel-boxscans" role="button" data-slide="next">
                            <i class="fas fa-chevron-right fa-3x"></i>
                            <span class="sr-only">Next</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    @foreach ($boxscans as $boxscan)
                        <a href="#carousel-boxscans" data-slide-to="{{ $loop->index }}" @if ($loop->first) class="active" @endif>
                            <img class="w-25 mr-2" src="{{ $boxscan }}" alt="Thumbnail of other scans of the game box">
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <p class="card-text text-center">
                There is no boxscan in the database. You have one? Please use the
                submit card to sent it to us.
            </p>
        @endif
    </div>
    @if ($boxscans->isNotEmpty())
        <div class="card-footer text-muted">
            <strong>{{ $boxscans->count() }}</strong> {{ Str::plural('boxscan', $boxscans->count() )}} loaded
        </div>
    @endif
</div>
