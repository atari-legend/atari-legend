<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Author</h2>
    </div>

    <div class="card-body p-2 bg-darklight">
        <p class="card-text">
            @if (isset($mode) && $mode === 'submit' && $reviews->isEmpty())
                You haven't written any reviews before. This is your first. Congratulations and good luck!
                For some examples, see the card 'Indepth reviews'.
            @else
                {{ Helper::user($user) }} has written {{ $reviews->count() }} additional reviews
            @endif

            @if ($reviews->isNotEmpty())
                <ul>
                    @foreach ($reviews as $r)
                        <li>
                            <a href="{{ route('reviews.show', ['review' => $r ]) }}">{{ $r->games->first()->game_name }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </p>


    </div>
</div>
