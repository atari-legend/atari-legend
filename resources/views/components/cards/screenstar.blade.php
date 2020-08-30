<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase"><a href="{{ route('reviews.index') }}">Screenstar</a></h2>
    </div>
    <div class="card-body p-0">
        <img class="w-100" src="{{ asset('storage/images/game_screenshots/'.$screenstar->games[0]->screenshots[0]->file) }}">
        <div class="p-2">
            <p class="card-text">
                {!! Helper::bbCode(Helper::extractTag($screenstar->review_text, "screenstar")) !!}
            </p>
            <h6 class="card-subtitle text-muted">{{ date('F j, Y', $screenstar->review_date) }} by {{ Helper::user($screenstar->user) }}</h6>
            <a class="d-block text-right" href="{{ route('reviews.show', ['review' => $screenstar->review_id]) }}">
                More <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
</div>
