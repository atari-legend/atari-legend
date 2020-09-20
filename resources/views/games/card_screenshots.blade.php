<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $game->game_name }}</h2>
    </div>
    <div class="card-body p-2">
        @if ($game->screenshots->isNotEmpty())
            <div class="row">
                <div class="col-2">
                    @foreach($game->screenshots as $screenshot)
                        <a href="#carousel-screenshots" data-slide-to="{{ $loop->index }}" @if ($loop->first) class="active" @endif>
                            <img class="w-100 mb-2" src="{{ asset('storage/images/game_screenshots/'.$screenshot->file) }}" alt="Thumbnail of other screenshot of {{ $game->game_name }}">
                        </a>
                    @endforeach
                </div>
                <div class="col-10">
                    <div class="carousel slide" id="carousel-screenshots" data-ride="carousel">
                        <ol class="carousel-indicators">
                            @foreach($game->screenshots as $screenshot)
                                <li data-target="#carousel-screenshots" data-slide-to="{{ $loop->index }}" @if ($loop->first) class="active" @endif></li>
                            @endforeach
                        </ol>
                        <div class="carousel-inner">
                            @foreach($game->screenshots as $screenshot)
                                <div class="carousel-item @if ($loop->first) active @endif">
                                    <img class="w-100 d-block" src="{{ asset('storage/images/game_screenshots/'.$screenshot->file) }}" alt="Large screenshot of {{ $game->game_name }}">
                                </div>
                            @endforeach
                        </div>
                        <a class="carousel-control-prev text-decoration-none" href="#carousel-screenshots" role="button" data-slide="prev">
                            <span class="fas fa-chevron-left fa-3x"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                        <a class="carousel-control-next text-decoration-none" href="#carousel-screenshots" role="button" data-slide="next">
                            <i class="fas fa-chevron-right fa-3x"></i>
                            <span class="sr-only">Next</span>
                        </a>
                    </div>
                </div>
            </div>
        @else
            <p class="card-text text-center">
                There are no screenshots in the database. You have any? Please use the submit
                card to sent it to us. You can select more screenshots at once to upload.
            </p>
        @endif
    </div>
    @if ($game->screenshots->isNotEmpty())
        <div class="card-footer text-muted">
            <strong>{{ $game->screenshots->count() }}</strong> {{ Str::plural('screenshot', $game->screenshots->count() )}} loaded
        </div>
    @endif
</div>
