<div class="card-body p-2">
    <div class="card-subtitle text-muted mb-2">
        {{ Helper::user($comment->user) }}

        {{-- Edition controls --}}
        @if (Auth::check() && Auth::user()->user_id === $comment->user?->user_id)
            <div class="float-end ms-2">
                {{-- Save button --}}
                <small class="me-1 d-none" data-comment-save="{{ $comment->comments_id }}">
                    <a href="#" onclick="event.preventDefault(); document.getElementById('comment-edit-{{ $comment->comments_id }}').submit()"><i class="far fa-save text-success" title="Save comment"></i></a>
                </small>

                {{-- Edit / Cancel edit button --}}
                <small class="me-1">
                    <a href="#" data-comment-edit="{{ $comment->comments_id }}"><i class="fas fa-pencil-alt" title="Edit comment"></i></a>
                </small>

                {{-- Delete button. Uses a form to POST the deletion --}}
                <small>
                    <form id="comment-delete-{{ $comment->comments_id }}" action="{{ route('comments.delete') }}" method="POST" class="d-none">
                        @csrf
                        <input type="hidden" name="comment_id" value="{{ $comment->comments_id }}">
                    </form>
                    <a href="{{ route('comments.delete') }}"
                        onclick="event.preventDefault(); document.getElementById('comment-delete-{{ $comment->comments_id }}').submit()"><i class="far fa-trash-alt text-danger" title="Delete comment"></i></a>
                </small>
            </div>
        @endif

        @if (isset($showGame) && $showGame === true && $comment->games->isNotEmpty())
            <span class="float-end"><a href="{{ route('games.show', ['game' => $comment->games->first()]) }}">{{ $comment->games->first()->game_name }}</a></span>
        @endif
    </div>

    <div class="py-2 mb-1" id="comment-{{ $comment->comments_id }}">{!! Helper::bbCode(stripslashes(nl2br($comment->comment))) !!}</div>

    {{-- Comment edit form --}}
    @if (Auth::check() && Auth::user()->user_id === $comment->user?->user_id)
        <form id="comment-edit-{{ $comment->comments_id }}" method="post" action="{{ route('comments.update') }}" class="text-center d-none">
            @csrf
            <input type="hidden" name="comment_id" value="{{ $comment->comments_id }}">
            <input type="hidden" name="context" value="{{ $context ?? ''}}">
            <input type="hidden" name="id" value="{{ $id ?? ''}}">
            <textarea class="form-control" rows="5" name="comment" required>{{ stripslashes($comment->comment) }}</textarea>
        </form>
    @endif

    @if (isset($comment->user))
    <small class="text-muted float-start">
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
    <div class="text-muted text-end">
        {{ date('F j, Y', $comment->timestamp) }}
    </div>
</div>
