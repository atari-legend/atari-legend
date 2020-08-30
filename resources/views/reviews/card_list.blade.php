<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Reviews</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            What do other people think of a certain game? You wanna learn more
            about a particular game? You came to the right place. Over here you'll
            find reviews of your favorite ST classics. If you feel inspired and
            you want to write something yourself, make sure to send your review
            to the Atari Legend team. And if it fits, we'll be happy to place it online.
            Enjoy the read! There are currently <strong>{{ $reviews->total() }}</strong> reviews
            available in the Atari Legend database.
        </p>
    </div>
    <div class="card-body p-2">
        <form method="get" action="reviews">
            <div class="row mb-3">
                <label for="titleAZ" class="col-sm-2 col-form-label">Author</label>
                <div class="col-sm-10">
                    <select class="form-select" id="author" name="author">
                        <option value="" selected>-</option>
                        @foreach ($authors as $author)
                            <option value="{{ $author->user_id }}">{{ $author->userid }} ({{ $author->reviews->count() }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <div class="card-body p-0 striped">
        @foreach ($reviews as $review)
            <div class="row g-0 p-2">
                <div class="col-4 pr-2 align-self-center">
                    <img class="w-100 " src="{{ asset('storage/images/game_screenshots/'.$review->screenshots->random()->screenshot->file) }}">
                </div>
                <div class="col-8 pl-2">
                    <h3>
                        <a href="{{ route('reviews.show', ['review' => $review]) }}">
                            {{ $review->games()->get()->first()->game_name}}
                        </a>
                    </h3>
                    <h6 class="card-subtitle text-muted">{{ date('F j, Y', $review->review_date) }} by {{ Helper::user($review->user) }}</h6>
                    {!! Helper::bbCode(Helper::extractTag($review->review_text, "frontpage")) !!}<br>
                    <a class="d-block text-right" href="{{ route('reviews.show', ['review' => $review]) }}">
                        More <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        @endforeach

        {{ $reviews->links() }}
    </div>
</div>
