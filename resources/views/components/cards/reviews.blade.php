<div class="card bg-dark mb-4 card-ellipsis">
    <div class="card-header text-center">
        <h2 class="text-uppercase"><a href="{{ route('reviews.index') }}">In-Depth Reviews</a></h2>
    </div>
    <div class="striped">
        @foreach ($reviews as $review)
            <div class="card-body p-2">
                <h3 class="card-title fs-6 text-audiowide"><a class="text-nowrap overflow-hidden overflow-ellipsis d-block" href="{{ route('games.show', ['game' => $review->games[0]->game_id]) }}">{{ $review->games[0]->game_name }}</a></h3>
                <p class="card-subtitle text-muted mb-2">{{ $review->review_date->format('F j, Y') }} by {{ Helper::user($review->user) }}</p>
                <p class="card-text mb-0">
                    {!! Helper::bbCode(e(Helper::extractTag($review->review_text, "frontpage"))) !!}
                </p>
                <a class="d-block text-end" href="{{ route('reviews.show', ['review' => $review->review_id]) }}">
                    <i title="Read the review of {{ $review->games[0]->game_name }}" class="fas fa-chevron-right"></i>
                </a>
            </div>
        @endforeach
    </div>
</div>
