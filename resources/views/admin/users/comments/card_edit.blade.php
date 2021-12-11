<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Comment</h2>

        <p class="card-subtitle mb-3">
            <span class="text-muted">By</span> {{ Helper::user($comment->user) }}
            <span class="text-muted">on</span>
            {{ Carbon\Carbon::createFromTimestamp($comment->timestamp)->toDayDateTimeString() }}
            <br>
            <span class="text-muted">{{ Str::ucfirst($comment->type) }}</span>: <a href="{{ route("{$comment->type}s.show", $comment->target_id) }}#comment-{{ $comment->comments_id }}">{{ $comment->target }}</a>
        </p>

        <form action="{{ route('admin.users.comments.update', $comment) }}" method="post">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" required id="content" name="content" rows="5">{{ old('content', stripslashes($comment->comment)) }}</textarea>
            </div>

            <button type="submit" class="btn btn-success">Save</button>
            <a href="{{ route('admin.users.users.index') }}" class="btn btn-link">Cancel</a>

        </form>
    </div>
</div>
