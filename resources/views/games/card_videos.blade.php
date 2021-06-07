@if ($game->videos->isNotEmpty())
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Videos</h2>
        </div>
        <div class="card-body p-0">
            @foreach ($game->videos as $video)
                <div class="video-container mb-2">
                    <iframe
                        class="w-100"
                        src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_id }}"
                        frameborder="0"
                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
                </div>
            @endforeach
        </div>
    </div>
@endif
