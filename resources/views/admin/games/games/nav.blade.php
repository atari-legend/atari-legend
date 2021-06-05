<ul class="nav nav-tabs bg-light mb-2">
    <li class="nav-item">
        <a class="nav-link disabled">Editing: <span class="text-dark">{{ $game->game_name }}</a></a>
    </li>

    <li class="nav-item">
        <a class="nav-link disabled" href="#" }}">Details</a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled" href="#">Releases <span class="badge rounded-pill bg-secondary">{{ $game->releases->count() }}</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link @activeroute('admin.games.game-credits.index')"
            href="{{ route('admin.games.game-credits.index', $game) }}">
            Credits <span class="badge rounded-pill bg-secondary">{{ $game->individuals->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled" href="#">Facts <span class="badge rounded-pill bg-secondary">{{ $game->facts->count() }}</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link @activeroute('admin.games.game-screenshots.index')"
            href="{{ route('admin.games.game-screenshots.index', $game) }}">
            Screenshots <span class="badge rounded-pill bg-secondary">{{ $game->screenshots->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link @activeroute('admin.games.game-music.index')"
            href="{{ route('admin.games.game-music.index', $game) }}">
            Music <span class="badge rounded-pill bg-secondary">{{ $game->sndhs->count() }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled" href="#">Similar <span class="badge rounded-pill bg-secondary">{{ $game->similarGames->count() }}</span></a>
    </li>
    <li class="nav-item">
        <a class="nav-link disabled" href="#">Reviews <span class="badge rounded-pill bg-secondary">{{ $game->reviews->count() }}</span></a>

    </li>
    <li class="nav-item">
        <a class="nav-link disabled" href="#">Series <span class="badge rounded-pill bg-secondary">{{ $game->series !== null ? '1' : '0' }}</span></a>
    </li>
    <li class="nav-item ms-auto">
        <a class="nav-link text-info" href="{{ route('games.show', $game) }}">
            <i class="fas fa-eye"></i> View on main site
        </a>
    </li>
</ul>
