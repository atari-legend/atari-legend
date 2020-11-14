<div class="card bg-dark mb-4 card-game">
    <div class="card-header text-center">
        <h2 class="text-uppercase">
            {{ $release->year }}
            @if ($release->name !== null && $release->name !== '')
                / {{ $release->name }}
            @endif
            @contributor
                <a class="d-inline-block ml-1" href="{{ config('al.legacy.base_url').'/admin/games/games_release_detail.php?release_id='.$release->id.'&game_id='.$release->game->game_id }}">
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
