<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $game->game_name }}</h2>
    </div>
    <div class="card-body p-2">
        @if ($game->screenshots->isNotEmpty())
            <div class="row">
                <div class="col-2">
                    @foreach($game->screenshots->skip(1) as $screenshot)
                        <img class="w-100 mb-2" src="{{ asset('storage/images/game_screenshots/'.$screenshot->file) }}">
                    @endforeach
                </div>
                <div class="col-10">
                    <img class="w-100" src="{{ asset('storage/images/game_screenshots/'.$game->screenshots->first()->file) }}">
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
