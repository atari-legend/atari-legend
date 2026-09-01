<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            <a href="{{ route('games.show', ['game' => $review->games->first()]) }}">{{ $review->games->first()->name }}</a>
            @contributor
                <a href="{{ route('admin.reviews.reviews.edit', $review) }}">
                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                </a>
            @endcontributor
        </h2>
    </div>

    <div class="card-body p-2 bg-darklight">
        <h3 class="fs-5 text-audiowide">Written by {{ Helper::user($review->user) }}</h3>
        <span class="text-muted">{{ $review->date->format('F j, Y') }}</span>
    </div>
    <div class="card-body p-2 bg-darklight">

        <div class="float-end col-5 col-sm-3 ps-2 text-center text-muted lightbox-gallery">
            @foreach ($review->screenshots->sortBy('id') as $screenshot)
                <div class="bg-dark p-2">
                    <a class="lightbox-link" href="{{ $screenshot->getUrlRoute('game', $review->games->first()) }}" title="{{ $screenshot->pivot->comment->text ?? '' }}">
                        <img class="w-100 mb-2" src="{{ $screenshot->getUrlRoute('game', $review->games->first()) }}" alt="{{ $screenshot->pivot->comment->text ?? '' }}">
                    </a>
                    <p class="pb-5 mb-0">{{ $screenshot->pivot->comment->text }}</p>
                </div>
            @endforeach
        </div>

        <p class="card-text">
            {!! Helper::bbCode(nl2br(e($review->text), false)) !!}
        </p>
        {{-- One column stands for four: every write path sets all four
             together, so a review either has the whole score or none of it. --}}
        @isset ($review->graphics)
            <hr>
            <h5>Score</h5>

            <ul class="list-unstyled">
                <li>Graphics: {{ $review->graphics }}</li>
                <li>Sound: {{ $review->sound }}</li>
                <li>Gameplay: {{ $review->gameplay }}</li>
                <li>Overall: {{ $review->overall }}</li>
            </ul>
        @endisset

    </div>
</div>
