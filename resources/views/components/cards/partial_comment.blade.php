<div class="card-body p-2">
    <div class="card-subtitle text-muted mb-2">
        {{ Helper::user($comment->user) }}

        {{-- Edition controls --}}
        @if (Auth::check() && Auth::user()->getKey() === $comment->user?->getKey())
            <div class="float-end ms-2">
                {{-- Save button --}}
                <small class="me-1 d-none" data-comment-save="{{ $comment->getKey() }}">
                    <a href="#" onclick="event.preventDefault(); document.getElementById('comment-edit-{{ $comment->getKey() }}').submit()"><i class="far fa-save text-success" title="Save comment"></i></a>
                </small>

                {{-- Edit / Cancel edit button --}}
                <small class="me-1">
                    <a href="#" data-comment-edit="{{ $comment->getKey() }}"><i class="fas fa-pencil-alt" title="Edit comment"></i></a>
                </small>

                {{-- Delete button. Uses a form to POST the deletion --}}

                <form id="comment-delete-{{ $comment->getKey() }}" action="{{ route('comments.delete') }}" method="POST" class="d-none">
                    @csrf
                    <input type="hidden" name="comment_id" value="{{ $comment->getKey() }}">
                </form>
                <small>
                    <a href="{{ route('comments.delete') }}"
                        onclick="event.preventDefault(); document.getElementById('comment-delete-{{ $comment->getKey() }}').submit()"><i class="far fa-trash-alt text-danger" title="Delete comment"></i></a>
                </small>
            </div>
        @endif

        @if (isset($showGame) && $showGame === true && $comment->games->isNotEmpty())
            <span class="float-end"><a href="{{ route('games.show', ['game' => $comment->games->first()]) }}">{{ $comment->games->first()->name }}</a></span>
        @endif
    </div>

    <div class="py-2 mb-1" id="comment-{{ $comment->getKey() }}">
        @contributor
            <a class="d-inline-block me-1" href="{{ route('admin.users.comments.edit', $comment) }}">
                <small><i class="fas fa-pencil-alt text-contributor"></i></small>
            </a>
        @endcontributor

        {!! Helper::bbCode(stripslashes(nl2br(e($comment->text), false))) !!}
    </div>

    {{-- Comment edit form --}}
    @if (Auth::check() && Auth::user()->getKey() === $comment->user?->getKey())
        <form id="comment-edit-{{ $comment->getKey() }}" method="post" action="{{ route('comments.update') }}" class="text-center d-none">
            @csrf
            <input type="hidden" name="comment_id" value="{{ $comment->getKey() }}">
            <input type="hidden" name="context" value="{{ $context ?? ''}}">
            <input type="hidden" name="id" value="{{ $id ?? ''}}">
            <textarea class="form-control" rows="5" name="comment" required>{{ stripslashes($comment->text) }}</textarea>
        </form>
    @endif

    @if (isset($comment->user))
    <small class="text-muted float-start">
        @if ($comment->user->twitter)
            <a href="{{ $comment->user->twitter }}"><i title="Visit Twitter account of {{ $comment->user->userid }}" class="fab fa-twitter"></i></a>
        @endif
        @if ($comment->user->facebook)
            <a href="{{ $comment->user->facebook }}"><i title="Visit Facebook page of {{ $comment->user->userid }}" class="fab fa-facebook-square"></i></a>
        @endif
        @if ($comment->user->atari_forum)
            <a href="{{ $comment->user->atari_forum }}"><i title="Visit AtariForum account of {{ $comment->user->userid }}" class="fas fa-gamepad"></i></a>
        @endif
        @if ($comment->user->website)
            <a href="{{ $comment->user->website }}"><i title="visit Website of {{ $comment->user->userid }}" class="fas fa-globe"></i></a>
        @endif
    </small>
    @endif
    <div class="text-muted text-end">
        {{ date('F j, Y', $comment->timestamp) }}
    </div>
</div>
