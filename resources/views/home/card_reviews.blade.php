<div class="card bg-dark mb-4 card-ellipsis">
    <div class="card-header text-center">
        <h2 class="text-uppercase"><a href="{{ route('reviews.index') }}">In-Depth Reviews</a></h2>
    </div>
    <div class="striped">
        @forelse ($reviews as $review)
            <div class="card-body p-2">
                <h5 class="card-title"><a href="{{ route('games.show', ['game' => $review->games[0]->game_id]) }}">{{ $review->games[0]->game_name }}</a></h5>
                <h6 class="card-subtitle text-muted mb-2">{{ date('F j, Y', $review->review_date) }} by {{ Helper::user($review->user) }}</h6>
                <p class="card-text ellipsis">
                    {!! Helper::bbCode(Helper::extractTag($review->review_text, "frontpage")) !!}
                </p>
                <a class="d-block text-right" href="{{ route('reviews.show', ['review' => $review->review_id]) }}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        @empty
            No news!
        @endforelse
    </div>
</div>
