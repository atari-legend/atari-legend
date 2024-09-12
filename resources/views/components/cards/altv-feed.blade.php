@if ($entries !== null && count($entries) > 0)
    <div class="card bg-dark mb-4">
        <div class="card-header text-center">
            <h2 class="text-uppercase"><a href="https://www.youtube.com/atarilegend">Latest AL TV Videos</a></h2>
        </div>

        <div class="striped">
            @foreach ($entries as $entry)
                @if ($loop->index < 3)
                    <div class="card-body p-0">
                        <div class="position-relative text-center w-100">
                            <a href="{{ $entry['link'] }}">
                                <img class="w-100" src="{{ $entry['thumbnail'] }}" alt="Thumbnail of the video {{ $entry['title'] }}" loading="lazy">
                                <img class="w-100 position-absolute top-0 start-0" src="{{ asset('images/play-overlay.png') }}" alt="Play button">
                            </a>


                        </div>
                        <div class="p-2 pb-4">
                            <span class="text-muted">{{ $entry['date']->format("F j, Y") }}</span>
                            <a href="{{ $entry['link'] }}">
                                {{ $entry['title'] }}
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif
