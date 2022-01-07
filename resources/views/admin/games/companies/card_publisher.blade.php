<div class="card mb-3 bg-light">
    <div class="card-body">

        <h2 class="card-title fs-4">Published</h2>

        @if ($company->releases->isNotEmpty())
            <ul>
                @foreach ($company->releases->sortBy('year') as $release)
                    <li>{{ $release->game->game_name }} {{ $release->year }}</li>
                @endforeach
            </ul>
        @else
            <p class="text-muted">No releases</p>
        @endif

    </div>
</div>
