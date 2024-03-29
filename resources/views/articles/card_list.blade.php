<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <a class="float-end" href="{{ route('feeds.main') }}"><i class="fas fa-rss-square text-warning"></i></a>
        <h2 class="text-uppercase">Articles</h2>
    </div>
    <div class="card-body p-2 mb-3">
        <p class="card-text">
            This is the blog section of Atari Legend. In here we will try to deliver
            informative and entertaining articles on all that is Atari. There are
            currently {{ $articles->total() }} articles in the AL database!
        </p>
    </div>

    <div class="card-body p-0 striped">
        @foreach ($articles as $article)
            <div class="p-2 lightbox-gallery">
                <div class="clearfix mb-2">
                    <h3 class="fs-5 text-audiowide">
                        <a href="{{ route('articles.show', ['article' => $article]) }}">
                            {{ $article->article_title }}
                        </a>
                    </h3>
                    <p class="card-subtitle text-muted">
                        {{ $article->texts->first()->article_date->format('F j, Y') }} by {{ Helper::user($article->user) }}
                        <span class="badge bg-secondary ms-2">{{ $article->type->article_type ?? ''}}</span>
                    </p>
                </div>

                <div class="clearfix">
                    @if ($article->screenshots->isNotEmpty())
                        <a class="lightbox-link" href="{{ $article->screenshots->first()->getUrl('article') }}">
                            <img class="col-4 col-sm-3 float-start mt-1 me-2 mb-1" src="{{ $article->screenshots->first()->getUrl('article') }}" alt="Article screenshot">
                        </a>
                    @endif

                    {!! Helper::bbCode(e($article->texts->first()->article_intro)) !!}<br>
                    <a class="d-block text-end mt-2" href="{{ route('articles.show', ['article' => $article]) }}">
                        Read {{ $article->article_title }} <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        @endforeach

        {{ $articles->links() }}
    </div>

</div>
