<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Reviews</h2>
    </div>
    <div class="card-body p-4">
        <p class="card-text text-center">
            Feel inspired to write your own review? Please click <a href="#">here</a> and get started!
        </p>
    </div>
    @if ($game->reviews->isNotEmpty())
        <div class="striped">
            @foreach ($game->reviews as $review)
            <div class="card-body p-2">
                @include('reviews.partial_list', ['review' => $review])
            </div>
            @endforeach
        </div>
    @endif
</div>
