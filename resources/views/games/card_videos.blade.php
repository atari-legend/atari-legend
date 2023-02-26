@if ($game->videos->isNotEmpty())
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Videos</h2>
        </div>
        <div class="card-body p-0">
            @foreach ($game->videos as $video)
                <div>
                    <div class="video-container mb-2">
                        <iframe
                            class="w-100"
                            src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_id }}"
                            allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
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
