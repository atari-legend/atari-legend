<div class="card bg-dark mb-4 card-ellipsis">
    <div class="card-header text-center">
        <h2 class="text-uppercase"><a href="{{ route('news.index') }}">Press Releases</a></h2>
    </div>
    <div class="striped">
        @forelse ($news as $new)
            <div class="card-body p-2">
                <h5 class="card-title"><a href="{{ route('news.index') }}">{{ $new->news_headline }}</a></h5>
                <h6 class="card-subtitle text-muted mb-2">{{ date('F j, Y', $new->news_date) }} by {{ Helper::user($new->user) }}</h6>
                <p class="card-text mb-0 ellipsis">
                    {!! Helper::bbCode(Helper::extractTag($new->news_text, "frontpage")) !!}
                </p>
                <a class="d-block text-right" href="{{ route('news.index') }}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        @empty
            No news!
        @endforelse
    </div>
</div>
