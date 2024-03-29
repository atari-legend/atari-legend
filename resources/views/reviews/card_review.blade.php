<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            <a href="{{ route('games.show', ['game' => $review->games->first()]) }}">{{ $review->games->first()->game_name }}</a>
            @contributor
                <a href="{{ config('al.legacy.base_url').'/admin/games/games_review_edit.php?game_id='.$review->games->first()->game_id.'&reviewid='.$review->review_id }}">
                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                </a>
            @endcontributor
        </h2>
    </div>

    <div class="card-body p-2 bg-darklight">
        <h3 class="fs-5 text-audiowide">Written by {{ Helper::user($review->user) }}</h3>
        <span class="text-muted">{{ $review->review_date->format('F j, Y') }}</span>
    </div>
    <div class="card-body p-2 bg-darklight">

        <div class="float-end col-5 col-sm-3 ps-2 text-center text-muted lightbox-gallery">
            @foreach ($review->screenshots as $screenshot)
                <div class="bg-dark p-2">
                    <a class="lightbox-link" href="{{ $screenshot->getUrlRoute('game', $review->games->first()) }}" title="{{ $screenshot->pivot->comment->comment_text ?? '' }}">
                        <img class="w-100 mb-2" src="{{ $screenshot->getUrlRoute('game', $review->games->first()) }}" alt="{{ $screenshot->pivot->comment->comment_text ?? '' }}">
                    </a>
                    <p class="pb-5 mb-0">{{ $screenshot->pivot->comment->comment_text }}</p>
                </div>
            @endforeach
        </div>

        <p class="card-text">
            {!! Helper::bbCode(nl2br(e($review->review_text), false)) !!}
        </p>
        <hr>
        <h5>Score</h5>

        <ul class="list-unstyled">
            <li>Graphics: {{ $review->score->review_graphics }}</li>
            <li>Sound: {{ $review->score->review_sound }}</li>
            <li>Gameplay: {{ $review->score->review_gameplay }}</li>
            <li>Overall: {{ $review->score->review_overall }}</li>
        </ul>

    </div>
</div>
