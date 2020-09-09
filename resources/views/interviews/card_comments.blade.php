<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Interview Comments</h2>
    </div>

    <div class="card-body p-2">
        @guest
            <p class="card-text text-center text-danger">
                Please <a href="{{ route('login') }}">log in</a> to add your own comment to this interview
            </p>
        @endguest

        @auth
        <form method="post" action="{{ route('interview.comment', ['interview' => $interview]) }}" class="text-center">
            @csrf
            <textarea class="form-control" rows="5" name="comment" placeholder="Your comment here..." required></textarea>
            <button type="submit" class="btn btn-primary mt-3">Submit</button>
        </form>
        @endauth
    </div>
    <div class="striped">
        @foreach ($interview->comments->sortByDesc("timestamp") as $comment)
            <div class="card-body p-2">
                <h6 class="card-subtitle text-muted mb-2">
                    {{ Helper::user($comment->user) }}
                </h6>
                <p class="card-text">
                    {!! Helper::bbCode($comment->comment) !!}
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
        @endforeach
    </div>
</div>
