<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <a class="float-end" href="{{ route('feed') }}"><i class="fas fa-rss-square text-warning"></i></a>
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
    <div class="card-body p-2 mb-3">
        <form method="get" action="{{ route('reviews.index') }}">
            <div class="row mb-3">
                <label for="author" class="col-sm-2 col-form-label">Author</label>
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
            @include('reviews.partial_list', ['review' => $review])
        @endforeach

        {{ $reviews->withQueryString()->links() }}
    </div>
</div>
