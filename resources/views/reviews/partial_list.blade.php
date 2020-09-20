<div class="row g-0 p-2">
    <div class="col-4 pr-2 align-self-center">
        @if ($review->screenshots->isNotEmpty())
            <img class="w-100 " src="{{ asset('storage/images/game_screenshots/'.$review->screenshots->random()->screenshot->file) }}" alt="Screenshot of {{ $review->games->first()->game_name }}">
        @endif
    </div>
    <div class="col-8 pl-2">
        <h3>
            <a href="{{ route('reviews.show', ['review' => $review]) }}">
                {{ $review->games->first()->game_name}}
            </a>
        </h3>
        <h6 class="card-subtitle text-muted mb-2">{{ date('F j, Y', $review->review_date) }} by {{ Helper::user($review->user) }}</h6>
        {!! Helper::bbCode(Helper::extractTag($review->review_text, "frontpage")) !!}<br>
        <a class="d-block text-right" href="{{ route('reviews.show', ['review' => $review]) }}">
            More <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>
