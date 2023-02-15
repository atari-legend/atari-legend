<ul class="list-unstyled ps-3">
    <li class="text-info"><strong>
        {{ $game->game_name }}</strong></li>
    <li>
        <i class="fa-regular fa-rectangle-list fa-fw text-muted"></i>
        <a class="@activeroute('admin.games.games.edit')"
            href="{{ route('admin.games.games.edit', $game) }}" }}">Details</a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $game->releases->count() }}</span>
        <a class="disabled text-decoration-none text-muted" href="#">Releases</a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $game->individuals->count() }}</span>
        <a class="@activeroute('admin.games.game-credits.index')"
            href="{{ route('admin.games.game-credits.index', $game) }}">
            Credits
        </a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $game->developers->count() }}</span>
        <a class="@activeroute('admin.games.game-credits.index')"
            href="{{ route('admin.games.game-credits.index', $game) }}">
            Developers
        </a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $game->facts->count() }}</span>
        <a class="disabled text-decoration-none text-muted" href="#">Facts</a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $game->screenshots->count() }}</span>
        <a class="@activeroute('admin.games.game-screenshots.index')"
            href="{{ route('admin.games.game-screenshots.index', $game) }}">
            Screenshots
        </a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $game->sndhs->count() }}</span>
        <a class="@activeroute('admin.games.game-music.index')"
            href="{{ route('admin.games.game-music.index', $game) }}">
            Music
        </a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $game->similarGames->count() }}</span>
        <a class="@activeroute('admin.games.game-similar.index')"
            href="{{ route('admin.games.game-similar.index', $game) }}">
            Similar
        </a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $game->reviews->count() }}</span>
        <a class="disabled text-decoration-none text-muted" href="#">Reviews</a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $game->videos->count() }}</span>
        <a class="@activeroute('admin.games.game-videos.index')"
            href="{{ route('admin.games.game-videos.index', $game) }}">
            Videos
        </a>
    </li>
    <li>
        <i class="fas fa-eye fa-fw text-muted"></i>
        <a class="text-dark" href="{{ route('games.show', $game) }}">
            View on main site
        </a>
    </li>
</ul>
