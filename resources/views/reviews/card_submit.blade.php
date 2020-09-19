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

                <div class="mb-3">
                    <label for="text" class="form-label">Review text</label>
                    <textarea class="form-control" rows="20" id="text" name="text" placeholder="Your review here..." required></textarea>
                </div>
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
                <div class="row">
                    <div class="col">
                        <label for="gameplay" class="form-label">Gameplay</label>
                        <input type="number" min="0" max="10" class="form-control" id="gameplay" name="gameplay" required placeholder="From 0 (worse) to 10 (best)">
                    </div>
                    <div class="col">
                        <label for="overall" class="form-label">Overall</label>
                        <input type="number" min="0" max="10" class="form-control" id="overall" name="overall" required placeholder="From 0 (worse) to 10 (best)">
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary mt-3">Submit</button>
                </div>
            </form>
        @endauth
    </div>
</div>
