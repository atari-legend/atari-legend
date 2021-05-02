<ul class="nav nav-tabs justify-content-center fs-5 my-2">
    <li class="nav-item">
        <a class="nav-link @if ($active === 'games') active @endif"
            href="{{ route('games.search', ['title' => $title, 'titleAZ' => $titleAZ]) }}">
            {{ Str::plural('Game', $games->total())}}
            <small class="badge rounded-pill bg-secondary">{{ $games->total() }}</small>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link @if ($active === 'software') active @endif"
            href="{{ route('menus.search', ['title' => $title, 'titleAZ' => $titleAZ]) }}">
            Software
            <small class="badge rounded-pill bg-secondary">{{ $software->total() }}</small>
        </a>
    </li>
</ul>
