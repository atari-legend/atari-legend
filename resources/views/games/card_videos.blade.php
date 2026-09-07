@if ($game->videos->isNotEmpty())
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Videos</h2>
        </div>
        <div class="card-body p-0">
            @foreach ($game->videos as $video)
                <div>
                    <div
                        class="video-container video-facade mb-2"
                        data-youtube-id="{{ $video->youtube_id }}"
                        data-youtube-title="{{ $video->title }}"
                        role="button"
                        tabindex="0"
                        aria-label="Play video: {{ $video->title }}"
                    >
                        <img
                            class="video-facade-thumb"
                            src="https://i.ytimg.com/vi/{{ $video->youtube_id }}/hqdefault.jpg"
                            alt=""
                            loading="lazy"
                        >
                        <img class="video-facade-play" src="{{ asset('images/play-overlay.png') }}" alt="" loading="lazy">
                    </div>
                    <p class="p-2">
                        <span class="text-muted">{{ $video->author }}: </span>
                        <a href="https://www.youtube.com/watch?v={{ $video->youtube_id }}">{{ $video->title }}</a>
                    </p>
                </div>
            @endforeach
        </div>
    </div>
@endif
