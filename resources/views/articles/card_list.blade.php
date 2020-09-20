<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Articles</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            This is the blog section of Atari Legend. In here we will try to deliver
            informative and entertaining articles on all that is Atari. There are
            currently {{ $articles->total() }} articles in the AL database!
        </p>
    </div>

    <div class="card-body p-0 striped">
        @foreach ($articles as $article)
            <div class="row g-0 p-2">
                <div class="col-4 pr-2 align-self-center">
                    @if (isset($article->screenshots))
                        <img class="w-100 " src="{{ asset('storage/images/article_screenshots/'.$article->screenshots->first()->screenshot->file) }}" alt="Article screenshot">
                    @endif
                </div>
                <div class="col-8 pl-2">
                    <h3>
                        <a href="{{ route('articles.show', ['article' => $article]) }}">
                            {{ $article->article_title }}
                        </a>
                    </h3>
                    <h6 class="card-subtitle text-muted mb-2">
                        {{ date('F j, Y', $article->texts->first()->article_date) }} by {{ Helper::user($article->user) }}
                        <span class="badge bg-secondary ml-2">{{ $article->type->article_type }}</span>
                    </h6>
                    {!! Helper::bbCode($article->texts->first()->article_intro) !!}<br>
                    <a class="d-block text-right" href="{{ route('articles.show', ['article' => $article]) }}">
                        More <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        @endforeach

        {{ $articles->links() }}
    </div>

</div>
