<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Developer of</h2>

        @if ($company->games->isNotEmpty())
            <ul>
                @foreach ($company->games->sortBy('name') as $game)
                    <li>{{ $game->name }}</li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">No games</p>
        @endif

    </div>
</div>
