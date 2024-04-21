<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title">
            {{ $releasesWithoutScans->count() }} commercial
            {{ Str::plural('release', $releasesWithoutScans->count()) }} without box scans
        </h2>
        @if ($gamesWithBadSlug->count() > 30)
            <p class="text-muted">A random selection of 30 releases:</p>
        @endif

        @foreach ($releasesWithoutScans->random(30) as $release)
            <a href="{{ route('admin.games.releases.scans.index', [
                'game' => $release->game,
                'release' => $release
            ]) }}">{{ $release->game->game_name }}</a>
            <span class="text-muted">({{$release->full_label}})</span>
            @if (!$loop->last)
                <span class="me-2">,</span>
            @endif
        @endforeach
    </div>
</div>
