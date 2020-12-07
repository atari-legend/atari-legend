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
        <span class="text-muted">{{ date('F j, Y', $article->texts->first()->article_date) }}</span>
    </div>

    <div class="card-body p-2 bg-darklight">
        <div class="row g-0">
            <div class="col-7 col-sm-9">
                {!! Helper::bbCode(nl2br($article->texts->first()->article_text)) !!}
            </div>
            <div class="col-5 col-sm-3 ps-2 text-center text-muted lightbox-gallery">
                @foreach ($article->screenshots as $screenshot)
                    <div class="bg-dark p-2">
                        <a class="lightbox-link" href="{{ asset('storage/images/article_screenshots/'.$screenshot->screenshot->file) }}" title="{{ $screenshot->comment->comment_text }}">
                            <img class="w-100 " src="{{ asset('storage/images/article_screenshots/'.$screenshot->screenshot->file) }}" alt="{{ $screenshot->comment->comment_text }}">
                        </a>
                        <p class="pb-5 mb-0">{{ $screenshot->comment->comment_text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
