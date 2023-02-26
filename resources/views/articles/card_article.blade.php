<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            {{ $article->texts->first()->article_title }}
            @contributor
                <a href="{{ config('al.legacy.base_url').'/admin/articles/articles_edit.php?article_id='.$article->article_id }}">
                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                </a>
            @endcontributor
        </h2>
    </div>

    <div class="card-body p-2 bg-darklight">
        <h3 class="fs-5 text-audiowide">Written by {{ Helper::user($article->user) }}</h3>
        <span class="text-muted">{{ $article->texts->first()->article_date->format('F j, Y') }}</span>
    </div>

    <div class="card-body p-2 bg-darklight">
        <div class="float-end col-5 col-sm-3 ps-2 text-center text-muted lightbox-gallery">
            @foreach ($article->screenshots as $screenshot)
                <div class="bg-dark p-2">
                    <a class="lightbox-link" href="{{ $screenshot->getUrl('article') }}" title="@isset($screenshot->pivot->comment) {{ $screenshot->pivot->comment->comment_text }} @endif">
                        <img class="w-100 " src="{{ $screenshot->getUrl('article') }}" alt="@isset($screenshot->pivot->comment) {{ $screenshot->pivot->comment->comment_text }} @endif">
                    </a>
                    @if (isset($screenshot->pivot->comment))
                        <p class="pb-5 mb-0">{{ $screenshot->pivot->comment->comment_text }}</p>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="card-text">{!! Helper::bbCode(nl2br(e($article->texts->first()->article_text), false)) !!}</p>
    </div>
</div>
