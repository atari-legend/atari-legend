<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Latest Comments</h2>
    </div>
    <div class="striped">
        @forelse ($comments as $comment)
            <div class="card-body p-2">
                <h6 class="card-subtitle text-muted mb-2">
                    {{ Helper::user($comment->user) }}
                    <span class="float-right"><a href="{{ route('games.show', ['game' => $comment->games->first()]) }}">{{ $comment->games->first()->game_name }}</a></span>
                </h6>
                <p class="card-text">
                    {!! Helper::bbCode(stripslashes($comment->comment)) !!}
                </p>
                @if (isset($comment->user))
                <small class="text-muted float-left">
                    @if ($comment->user->user_twitter)
                        <a href="{{ $comment->user->user_twitter }}"><i class="fab fa-twitter"></i></a>
                    @endif
                    @if ($comment->user->user_fb)
                        <a href="{{ $comment->user->user_fb }}"><i class="fab fa-facebook-square"></i></a>
                    @endif
                    @if ($comment->user->user_af)
                        <a href="{{ $comment->user->user_af }}"><i class="fas fa-gamepad"></i></a>
                    @endif
                    @if ($comment->user->user_website)
                        <a href="{{ $comment->user->user_website }}"><i class="fas fa-globe"></i></a>
                    @endif
                </small>
                @endif
                <div class="text-muted text-right">
                    {{ date('F j, Y', $comment->timestamp) }}
                </div>
            </div>
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
