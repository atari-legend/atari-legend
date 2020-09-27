<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $game->game_name }}</h2>
    </div>
    <div class="card-body p-2">
        @auth
            <h5 class="text-muted mb-3">{{ date('F j, Y', time()) }} by {{ Auth::user()->userid }}</h5>
            <form method="post" action="{{ route('reviews.submit', ['game' => $game]) }}">
                @csrf
                <input type="hidden" name="game" value="{{ $game->game_id }}">

                <fieldset>
                    <legend>Review text</legend>
                    <div class="mb-5">
                        <textarea class="form-control" rows="20" name="text" placeholder="Your review here..." required></textarea>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Score</legend>
                        <div class="row mb-3">
                            <div class="col">
                                <label for="graphics" class="form-label">Graphics</label>
                                <input type="number" min="0" max="10" class="form-control" id="graphics" name="graphics" required placeholder="From 0 (worse) to 10 (best)">
                            </div>
                            <div class="col">
                                <label for="sound" class="form-label">Sound</label>
                                <input type="number" min="0" max="10" class="form-control" id="sound" name="sound" required placeholder="From 0 (worse) to 10 (best)">
                            </div>
                        </div>
                        <div class="row mb-5">
                            <div class="col">
                                <label for="gameplay" class="form-label">Gameplay</label>
                                <input type="number" min="0" max="10" class="form-control" id="gameplay" name="gameplay" required placeholder="From 0 (worse) to 10 (best)">
                            </div>
                            <div class="col">
                                <label for="overall" class="form-label">Overall</label>
                                <input type="number" min="0" max="10" class="form-control" id="overall" name="overall" required placeholder="From 0 (worse) to 10 (best)">
                            </div>
                        </div>
                </fieldset>

                @if ($game->screenshots->isNotEmpty())
                    <fieldset class="lightbox-gallery">
                        <legend>Screenshots</legend>
                        @foreach ($game->screenshots->sortBy('screenshot_id') as $screenshot)
                            <div class="row mb-3">
                                <div class="col-2">
                                    <a class="lightbox-link" href="{{ asset('storage/images/game_screenshots/'.$screenshot->file) }}">
                                        <img class="w-100" src="{{ asset('storage/images/game_screenshots/'.$screenshot->file) }}" alt="Game screenshot">
                                    </a>
                                </div>
                                <div class="col-10 d-flex">
                                    <input type="text" class="form-control align-self-center" name="screenshot[]" placeholder="Comment for this screenshot">
                                </div>
                            </div>
                        @endforeach
                    </fieldset>
                @endif

                <div class="text-center">
                    <button type="submit" class="btn btn-primary mt-3">Submit</button>
                </div>
            </form>
        @endauth
    </div>
</div>
