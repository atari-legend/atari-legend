<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase"><a href="{{ route('reviews.index') }}">Screenstar</a></h2>
    </div>
    <div class="card-body p-0">
        @isset ($screenstar)
            <figure>
                <img class="w-100 pixelated" src="{{ $screenstar->games[0]->screenshots[0]->getUrl('game') }}" alt="Screenshot of {{ $screenstar->games->first()->game_name }}">
                <figcaption class="py-2 px-3">
                    <div class="figcaption-caret"><i class="fas fa-angle-up fa-2x"></i></div>
                    <div class="figcaption-title"><a href="{{ route('games.show', ['game' => $screenstar->games->first()]) }}">{{ $screenstar->games->first()->game_name }}</a></div>
                    @if ($firstRelease !== null)
                        <div class="figcaption-note">
                            <a href="{{ route('games.search', ['year' => $firstRelease->date->year]) }}">{{ $firstRelease->date->year }}</a>
                        </div>
                    @endif
                    <div class="figcaption-subtitle mb-2"><strong>Random review</strong></div>
                </figcaption>
            </figure>
            <div class="p-2">
                <p class="card-text">
                    {!! Helper::bbCode(Helper::extractTag($screenstar->review_text, "screenstar")) !!}
                </p>
                <p class="card-subtitle text-muted">{{ date('F j, Y', $screenstar->review_date) }} by {{ Helper::user($screenstar->user) }}</p>
                <a class="d-block text-end" href="{{ route('reviews.show', ['review' => $screenstar->review_id]) }}">
                    More <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        @endisset
    </div>
</div>
