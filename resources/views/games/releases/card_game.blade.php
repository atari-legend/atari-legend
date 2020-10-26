<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            <a href="{{ route('games.show', ['game' => $release->game]) }}">
                {{ $release->game->game_name }}
            </a>
        </h2>
    </div>
    <div class="card-body p-0">
        @if ($release->game->screenshots->isNotEmpty())
            <img class="w-100 pixelated" src="{{ asset('storage/images/game_screenshots/'.$release->game->screenshots->random()->file) }}" alt="Screenshot of {{ $release->game->game_name }}">
        @else
            <p class="card-text text-center m-2">
                <i class="fas fa-images fa-4x text-muted"></i><br>
                <small class="text-muted">No screenshots</small>
            </p>
        @endif
    </div>
    <div class="card-body">
        @if ($release->game->releases->count() > 1)
            <h3>Releases</h3>
            <ul class="list-unstyled ml-2">
            @foreach ($release->game->releases as $r)
                <li>
                    @if ($r->id === $release->id)
                        <i class="fas fa-caret-right text-muted"></i> <strong class="text-muted">{{ Helper::releaseName($r) }}</strong>
                    @else
                        <a href="{{ route('games.releases.show', ['release' => $r]) }}">{{ Helper::releaseName($r) }}</a>
                    @endif
                </li>
            @endforeach
            </ul>
        @endif
    </div>
</div>
