<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">{{ $user->userid }} activity</h2>

        <h3 class="card-subtitle fs-5">Reviews</h3>
        @if ($user->reviews->isNotEmpty())
            <p>
                @foreach ($user->reviews as $review)
                    <a href="{{ route('reviews.show', $review) }}">{{ $review->games->first()->game_name }}</a>
                    @if (!$loop->last), @endif
                @endforeach
            </p>
        @else
            <p class="text-muted">No review</p>
        @endif

        <h3 class="card-subtitle fs-5">News</h3>
        @if ($user->news->isNotEmpty())
            <p>
                @foreach ($user->news->sortByDesc('news_date') as $news)
                    <a
                        href="{{ route('admin.news.news.edit', $news) }}">{{ $news->news_headline }}</a>
                    @if (!$loop->last), @endif
                @endforeach
            </p>
        @else
            <p class="text-muted">No news</p>
        @endif

        <h3 class="card-subtitle fs-5">News submissions</h3>
        @if ($user->newsSubmissions->isNotEmpty())
            <p>
                @foreach ($user->newsSubmissions->sortByDesc('news_date') as $news)
                    <a
                        href="{{ route('admin.news.submissions.index') }}">{{ $news->news_headline }}</a>
                    @if (!$loop->last), @endif
                @endforeach
            </p>
        @else
            <p class="text-muted">No news submission</p>
        @endif

        <h3 class="card-subtitle fs-5">Link submissions</h3>
        @if ($user->websiteSubmissions->isNotEmpty())
            <p>
                @foreach ($user->websiteSubmissions->sortByDesc('website_date') as $website)
                    <a
                        href="{{ config('al.legacy.base_url') . '/admin/links/link_addnew.php' }}">{{ $website->website_name }}</a>
                    @if (!$loop->last), @endif
                @endforeach
            </p>
        @else
            <p class="text-muted">No link submission</p>
        @endif

        <h3 class="card-subtitle fs-5">Game submissions</h3>
        @if ($user->gameSubmissions->isNotEmpty())
            <p>
                @foreach ($user->gameSubmissions->sortByDesc('timestamp') as $submission)
                    <a
                        href="{{ config('al.legacy.base_url') . '/admin/games/submission_games.php' }}">{{ $submission->game->game_name }}</a>
                    @if (!$loop->last), @endif
                @endforeach
            </p>
        @else
            <p class="text-muted">No game submission</p>
        @endif

        <h3 class="card-subtitle fs-5">Comments</h3>
        @if ($user->comments->isNotEmpty())
            <ul>
                <li>{{ $user->comments->pluck('games')->flatten()->count() }} game comments</li>
                <li>{{ $user->comments->pluck('articles')->flatten()->count() }} article comments</li>
                <li>{{ $user->comments->pluck('reviews')->flatten()->count() }} review comments</li>
                <li>{{ $user->comments->pluck('interviews')->flatten()->count() }} interview comments</li>
            </ul>
        @else
            <p class="text-muted">No comments</p>
        @endif

    </div>
</div>
