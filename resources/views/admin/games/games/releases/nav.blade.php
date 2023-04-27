<ul class="list-unstyled ps-4 border-start border-info">
    <li>
        <a href="{{ route('admin.games.releases.show', ['game' => $release->game, 'release' => $release]) }}"
            class="@activeroute('admin.games.releases.show')">
            Details
        </a>
    </li>
    <li>
        <a href="{{ route('admin.games.releases.system.index', ['game' => $release->game, 'release' => $release]) }}"
            class="@activeroute('admin.games.releases.system.index')">
            System Info
        </a>
    </li>
    <li>
        <a href="{{ route('admin.games.releases.scene.index', ['game' => $release->game, 'release' => $release]) }}"
            class="@activeroute('admin.games.releases.scene.index')">
            Scene
        </a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $release->medias->pluck('dumps')->count() }}</span>
        <a href="{{ route('admin.games.releases.medias.index', ['game' => $release->game, 'release' => $release]) }}"
            class="@activeroute('admin.games.releases.medias.index')">
            Media & Dumps
        </a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $release->boxscans->count() }}</span>
        <a class="text-muted text-decoration-none">
            Scans
        </a>
    </li>
</ul>
