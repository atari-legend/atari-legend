<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Videos</h2>


        <form class="mt-2 mb-4 row row-cols-lg-auto g-3"
            action="{{ route('admin.games.game-videos.store', $game) }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            <div class="col">
                <input type="text" class="form-control @error('video') is-invalid @enderror"
                    name="video" value="{{ old('video') }}"
                    placeholder="YouTube URL" required>

                @error('video')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="col">
                <button type="submit" class="btn btn-success w-100">Add video</button>
            </div>
        </form>

        @forelse ($game->videos as $video)
            <div class="d-inline-block m-2 text-center">
                <div class="mb-2">
                    <span class="text-muted">{{$video->author }}</span><br>
                    {{ $video->title }}
                </div>
                <div>
                    <iframe
                        class="border border-primary"
                        width="280"
                        height="157"
                        src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_id }}"

                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        ></iframe><br>

                    <form class="text-center" action="{{ route('admin.games.game-videos.destroy', ['game' => $game, 'video' => $video]) }}" method="POST"
                        onsubmit="javascript:return confirm('This video will be deleted')">
                        @csrf
                        @method('DELETE')

                        <button title="Remove video" class="btn">
                            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <p class="card-text text-center text-muted">
                No videos for the game yet.
            </p>
        @endforelse

    </div>
</div>
