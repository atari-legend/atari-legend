<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Submission</h2>

        <p class="card-subtitle mb-3">
            <span class="text-muted">By</span> {{ Helper::user($submission->user) }}
            <span class="text-muted">on</span>
            {{ Carbon\Carbon::createFromTimestamp($submission->timestamp)->toDayDateTimeString() }}
            <br>
            <span class="text-muted">For</span> <a
                href="{{ route('games.show', $submission->game) }}">{{ $submission->game->game_name }}</a>
            <br>
            <span class="text-muted">Reviewed</span>
            @if ($submission->game_done === App\Models\GameSubmitInfo::SUBMISSION_REVIEWED)
                <span class="text-success">Yes</span>
            @else
                <span class="text-warning">No</span>
            @endif
        </p>

        <div class="border p-2 bg-white">
            <p>{!! Helper::bbCode(stripslashes(nl2br(e($submission->submit_text)))) !!}</p>

            <div>
                @foreach ($submission->screenshots as $screenshot)
                    @if (Str::startsWith($screenshot->mime_type, 'image/'))
                        <div class="d-inline-block position-relative m-2">
                            <form
                                action="{{ route('admin.games.submissions.screenshots.destroy', ['submission' => $submission, 'screenshot' => $screenshot]) }}"
                                method="POST" onsubmit="javascript:return confirm('This screenshot will be deleted')">
                                @csrf
                                @method('DELETE')

                                <img class="border border-dark" src="{{ $screenshot->getUrl('game_submission') }}"
                                    style="max-width: 320px;">

                                <button title="Remove screenshot" class="btn position-absolute end-0 pe-4 pt-4">
                                    <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <form
                            action="{{ route('admin.games.submissions.screenshots.destroy', ['submission' => $submission, 'screenshot' => $screenshot]) }}"
                            method="POST" onsubmit="javascript:return confirm('This attachment will be deleted')">
                            @csrf
                            @method('DELETE')

                            <a href="{{ $screenshot->getUrl('game_submission') }}">{{ $screenshot->file }}</a>

                            <button title="Remove screenshot" class="btn btn-link p-0 m-0">
                                <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                            </button>
                        </form>

                    @endif
                @endforeach
            </div>
        </div>

        <form action="{{ route('admin.games.submissions.update', $submission) }}" class="mt-3 me-1 float-start"
            method="post">
            @csrf
            @method('PUT')

            @if ($submission->game_done === App\Models\GameSubmitInfo::SUBMISSION_REVIEWED)
                <button type="submit" class="btn btn-warning" name="action" value="unreview">Mark back as not
                    reviewed</button>
            @else
                <button type="submit" class="btn btn-success" name="action" value="review">Mark as reviewed</button>
            @endif
            <button type="submit" class="btn btn-outline-secondary" name="action" value="comment">Convert to
                comment</button>
        </form>

        <form action="{{ route('admin.games.submissions.destroy', $submission) }}" class="mt-3"
            method="post">
            @csrf
            @method('DELETE')

            <button type="submit" class="btn btn-danger">Delete</button>
            <a href="{{ route('admin.games.submissions.index') }}" class="btn btn-link">Cancel</a>

        </form>

    </div>
</div>
