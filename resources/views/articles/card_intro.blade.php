<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Intro</h2>
    </div>

    <div class="card-body p-2">
        <p class="card-text">
            {!! Helper::bbCode($article->texts->first()->article_intro) !!}
        </p>
    </div>
</div>
