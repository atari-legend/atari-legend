<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Author</h2>
    </div>

    <div class="card-body p-2 bg-darklight">
        <p class="card-text">
            {{ Helper::user($article->user) }} has written {{ $otherArticles->count() }} additional articles

            @if ($otherArticles->isNotEmpty())
                <ul>
                    @foreach ($otherArticles as $a)
                        <li>
                            <a href="{{ route('articles.show', ['article' => $a ]) }}">{{ $a->texts->first()->article_title }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif

        </p>
    </div>
</div>
