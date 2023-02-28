<div class="card mb-3 bg-light">
    <div class="card-body">
        <h2 class="card-title fs-4">Releases</h2>

        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Year</th>
                    <th>Name</th>
                    <th>Publisher</th>
                    <th>Locations</th>
                    <th>Menu</th>
                    <th>Dumps</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($game->releases->sortBy('year') as $release)
                    <tr>
                        <td>
                            <a
                                href="{{ route('admin.games.releases.show', [
                                    'game' => $release->game,
                                    'release' => $release,
                                ]) }}">{{ $release->year }}</a>
                        </td>
                        <td>{{ $release->name ?? '-' }}</td>
                        <td>
                            {{ $release->publisher?->pub_dev_name ?? '-' }}
                        </td>
                        <td>
                            @foreach ($release->locations as $location)
                                @if ($location->country_iso2 !== null)
                                    <span title="{{ $location->name }}"
                                        class="fi fi-{{ strtolower($location->country_iso2) }} mx-1"></span>
                                @endif
                            @endforeach

                        </td>
                        <td>
                            @if ($release->menuDiskContents->isNotEmpty())
                                <a
                                    href="{{ route('admin.menus.menus.edit', $release->menuDiskContents->first()->menuDisk->menu) }}">
                                    {{ $release->menuDiskContents->first()->menuDisk->menu->full_label }}
                                    {{ $release->menuDiskContents->first()->menuDisk->label }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="{{ $release->dumps->count() ? '' : 'text-muted' }}">
                            <i title="Dumps" class="far fa-save"></i> &times; {{ $release->dumps->count() }}</td>
                        <td>
                            @if ($release->menuDiskContents->isNotEmpty())
                                <a class="btn btn-link"
                                    href="{{ route('menus.show', [
                                        'set' => $release->menuDiskContents->first()->menuDisk->menu->menuSet,
                                        'page' => $release->menuDiskContents->first()->menuDisk->menuset_page_number,
                                    ]) }}#menudisk-{{ $release->menuDiskContents->first()->menuDisk->id }}">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @else
                                <a class="btn btn-link" href="{{ route('games.releases.show', $release) }}"
                                    title="View on main site">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @endif
                        </td>
                        <td>
                            @if ($release->menuDiskContents->isEmpty())
                                <form
                                    action="{{ route('admin.games.releases.destroy', ['game' => $game, 'release' => $release]) }}"
                                    method="POST"
                                    onsubmit="javascript:return confirm('This item will be permanently deleted')">
                                    @csrf
                                    @method('DELETE')
                                    <button title="Delete release '{{ $release->year }}'" class="btn btn-sm">
                                        <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <a href="{{ route('admin.games.releases.create', $game) }}" class="btn btn-success">
            <i class="fas fa-plus-square fa-fw"></i> Add a new release
        </a>

    </div>
</div>
