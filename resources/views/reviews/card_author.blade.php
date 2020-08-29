<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Author</h2>
    </div>

    <div class="card-body p-2 bg-darklight">
        <p class="card-text">
            {{ Helper::user($review->user) }} has written {{ $otherReviews->count() }} additional reviews

            @if ($otherReviews->isNotEmpty())
                <ul>
                    @foreach ($otherReviews as $r)
                        <li>
                            <a href="{{ route('reviews.show', ['review' => $r ]) }}">{{ $r->games->first()->game_name }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif

        </p>
    </div>
</div>
