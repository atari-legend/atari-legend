<div class="card bg-dark mb-4 card-ellipsis">
    <div class="card-header text-center">
        <h2 class="text-uppercase"><a href="{{ route('news.index') }}">Press Releases</a></h2>
    </div>
    <div class="striped">
        @foreach ($news as $new)
            <div class="card-body p-2">
                <h3 class="card-title text-h6 text-audiowide"><a class="text-nowrap overflow-hidden overflow-ellipsis d-block" href="{{ route('news.index') }}">{{ $new->news_headline }}</a></h3>
                <p class="card-subtitle text-muted mb-2">{{ date('F j, Y', $new->news_date) }} by {{ Helper::user($new->user) }}</p>
                <p class="card-text mb-0 ellipsis">
                    {!! Helper::bbCode(Helper::extractTag($new->news_text, "frontpage")) !!}
                </p>
                <a class="d-block text-right" href="{{ route('news.index') }}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        @endforeach
    </div>
</div>
