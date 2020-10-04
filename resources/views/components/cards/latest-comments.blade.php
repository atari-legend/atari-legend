<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Latest Comments</h2>
    </div>
    <div class="striped">
        @forelse ($comments as $comment)
            @include('components.cards.partial_comment', ['showGame' => true])
        @empty
            <div class="card-body p-2">
                @if ($user !== null)
                    <p class="card-text text-center">
                        You haven't written any game comments. Browse the library,
                        share some memories and upgrade your karma stats ;-)
                    </p>
                @else
                    <p class="card-text text-center">
                        No comments!
                    </p>
                @endif
            </div>
        @endforelse
    </div>
</div>
