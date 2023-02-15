<div class="p-2 lightbox-gallery">
    <div class="clearfix mb-2">
        <h3 class="fs-5 text-audiowide">
            <a href="{{ route('reviews.show', ['review' => $review]) }}">
                {{ $review->games->first()->game_name}}
            </a>
        </h3>
        <p class="card-subtitle text-muted">{{ $review->review_date->format('F j, Y') }} by {{ Helper::user($review->user) }}</p>
    </div>

    <div class="clearfix">
        @if ($review->screenshots->isNotEmpty())
            @php
                $screenshot = $review->screenshots->random()
            @endphp
            <a class="lightbox-link" href="{{ $screenshot->getUrlRoute('game', $review->games->first()) }}">
                <img class="col-4 col-sm-3 float-start mt-1 me-2 mb-1" src="{{ $screenshot->getUrlRoute('game', $review->games->first()) }}" alt="Screenshot of {{ $review->games->first()->game_name }}">
            </a>
        @endif

        {!! Helper::bbCode(Helper::extractTag(e($review->review_text), "frontpage")) !!}<br>
        <a class="d-block text-end mt-2" href="{{ route('reviews.show', ['review' => $review]) }}">
            More <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>
