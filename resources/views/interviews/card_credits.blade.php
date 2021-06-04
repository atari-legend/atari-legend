<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Credits</h2>
    </div>

    <div class="card-body p-0 striped">
        @forelse ($interview->individual->games->groupBy("game_id") as $gameRoles)
            <div class="p-2">
                <a href="{{ route('games.show', ['game' => $gameRoles->first()]) }}">{{ $gameRoles->first()->game_name }}</a><br>
                <span class="text-muted">{{ $gameRoles->pluck('pivot')->pluck('role')->pluck('name')->join(', ')}}</span>
            </div>
        @empty
            <p class="card-text text-center text-muted bg-dark p-2">There are currently no credits for this person in our database</p>
        @endforelse
    </div>
</div>
