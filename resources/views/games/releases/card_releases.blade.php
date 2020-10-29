 @if ($release->game->releases->count() > 1)
    <div class="card bg-dark mb-4 card-game">
        <div class="card-header text-center">
            <h2 class="text-uppercase">Releases</h2>
        </div>
        <div class="card-body p-2">
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
        </div>
    </div>
@endif
