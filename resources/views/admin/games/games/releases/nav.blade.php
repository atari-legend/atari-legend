<ul class="list-unstyled ps-4 border-start border-info">
    <li>
        <a href="{{ route('admin.games.releases.show', ['game' => $release->game, 'release' => $release]) }}">
            Details
        </a>
    </li>
    <li>
        <a class="text-muted text-decoration-none">System info</a>
    </li>
    <li>
        <a class="text-muted text-decoration-none">Scene</a>
    </li>
    <li>
        <span class="badge bg-light text-secondary">{{ $release->medias->pluck('dumps')->count() }}</span>
        <a href="{{ route('admin.games.releases.medias.index', ['game' => $release->game, 'release' => $release]) }}">
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
