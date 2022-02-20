<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Author</h2>
    </div>

    <div class="card-body p-0 bg-darklight">
        <p class="card-text p-2 mb-0">
            @if (isset($mode) && $mode === 'submit' && $reviews->isEmpty())
                You haven't written any reviews before. This is your first. Congratulations and good luck!
                For some examples, see the card 'Indepth reviews'.
            @else
                {{ Helper::user($user) }} has written {{ $reviews->count() }} additional reviews
            @endif
        </p>

        @if ($reviews->isNotEmpty())
            <ul class="striped mb-0 list-unstyled">
                @foreach ($reviews as $r)
                    <li class="p-1 ps-4">
                        <a href="{{ route('reviews.show', ['review' => $r ]) }}">{{ $r->games->first()->game_name }}</a>
                        <small class="ms-2 text-muted">{{ $r->review_date->format('F j, Y') }}</small>
                    </li>
                @endforeach
            </ul>
        @endif

    </div>
</div>
