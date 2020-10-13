<div class="card card-ellipsis bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Latest Articles</h2>
    </div>

    <div class="striped">
        @foreach ($articles as $article)
            <div class="card-body p-2">
                <h3 class="card-title text-h5 text-audiowide">
                    <a href="{{ route('articles.show', ['article' => $article]) }}">{{ $article->texts->first()->article_title }}</a>
                </h3>
                <p class="card-subtitle text-muted mb-2">{{ date('F j, Y', $article->texts->first()->article_date) }} by {{ Helper::user($article->user) }}</p>
                <p class="card-text ellipsis">
                    {!! Helper::bbCode($article->texts->first()->article_intro) !!}
                </p>
                <a class="d-block text-right" href="{{ route('articles.show', ['article' => $article]) }}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        @endforeach
    </div>
</div>
