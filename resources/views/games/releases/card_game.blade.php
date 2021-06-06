<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase d-none d-lg-block">
            <a href="{{ route('games.show', ['game' => $release->game]) }}">
                {{ $release->game->game_name }}
            </a>
        </h2>
        <h2 class="text-uppercase d-lg-none">Game Screenshot</h2>
    </div>
    <div class="card-body p-0">
        @if ($release->game->screenshots->isNotEmpty())
            <img class="w-100 pixelated" src="{{ $release->game->screenshots->random()->getUrl('game') }}" alt="Screenshot of {{ $release->game->game_name }}">
        @else
            <p class="card-text text-center m-2">
                <i class="fas fa-images fa-4x text-muted"></i><br>
                <small class="text-muted">No screenshots</small>
            </p>
        @endif
    </div>
</div>
