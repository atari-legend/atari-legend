<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            <span class="d-lg-none">{{ $release->game->game_name}}: </span>
            {{ $release->year }}
            @if ($release->name !== null && $release->name !== '')
                / {{ $release->name }}
            @endif
            @contributor
                <a class="d-inline-block ms-1" href="{{ route('admin.games.releases.show', ['game' => $release->game, 'release' => $release]) }}">
                    <small><i class="fas fa-pencil-alt text-contributor"></i></small>
                </a>
            @endcontributor
        </h2>
    </div>
    <div class="card-body">
        @foreach ($descriptions as $description)
            <p class="card-text">{!! Helper::bbCode($description) !!}</p>
        @endforeach
    </div>
</div>
