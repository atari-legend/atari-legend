<h2 class="fs-4">{{ count($disks) }} disks in this menu</h2>

<div class="row row-cols row-cols-md-3 row-cols-lg-4 gy-4 mb-3">
    @foreach ($disks->sortBy('part') as $disk)
        <div class="col d-flex">
            <div class="card w-100">
                <div class="card-header">
                    <div class="float-end">
                        <i class="fas fa-scroll fa-fw text-muted @if (!$disk->scrolltext) fa-translucent @endif" title="Scrolltext"></i>
                        <i class="fas fa-comment fa-fw text-muted @if (!$disk->notes) fa-translucent @endif" title="Comments"></i>
                        <i class="far fa-save fa-fw text-muted @if ($disk->menuDiskDump === null) fa-translucent @endif" title="Dump"></i>
                        <i class="fas fa-camera fa-fw text-muted @if ($disk->screenshots->isEmpty()) fa-translucent @endif" title="Screenshots"></i>
                    </div>
                    <h3 class="card-title fs-5">
                        <a href="{{ route('admin.menus.disks.edit', $disk) }}">{{ $disk->menu->label}}{{ $disk->part}}</a>
                    </h3>
                </div>
                @if ($disk->screenshots->isNotEmpty())
                    <img class="card-img-top w-100"
                        src="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}"
                        alt="Screenshot of disk">
                @else
                    <img class="card-img-top w-100 bg-dark"
                        src="{{ asset('images/no-screenshot.png') }}"
                        alt="Screenshot of disk">
                @endif
                <div class="card-body">
                    <ul class="list-unstyled">
                        @foreach ($disk->contents->sortBy('order') as $content)
                            <li>
                                @if ($content->release)
                                    <a href="{{ route('games.releases.show', $content->release) }}">{{ $content->release->game->game_name }}</a>
                                @elseif ($content->game)
                                    <a href="{{ route('games.show', $content->game) }}">{{ $content->game->game_name }}</a>
                                @elseif ($content->menuSoftware)
                                    @if (isset($software) && $software->id === $content->menuSoftware->id)
                                        <b>{{ $content->menuSoftware->name }}</b>
                                    @else
                                        <a href="{{ route('admin.menus.software.edit', $content->menuSoftware) }}">
                                            {{ $content->menuSoftware->name }}
                                        </a>
                                    @endif
                                @endif

                                @if ($content->menuSoftware && $content->menuSoftware->demozoo_id)
                                    <a href="https://demozoo.org/productions/{{ $content->menuSoftware->demozoo_id }}/" class="d-inline-block">
                                        <img src="{{ asset('images/demozoo-16x16.png') }}" alt="Demozoo link for {{ $content->menuSoftware->name }}">
                                    </a>
                                @endif


                                @if ($content->notes)
                                    <small class="text-muted"><em>{{ $content->notes }}</em></small>
                                @endif

                                @if ($content->subtype)
                                    <small class="text-muted">[{{ $content->subtype }}]</small>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                </div>
                <div class="card-footer">
                    <form action="{{ route('admin.menus.disks.destroy', $disk) }}"
                        class="float-end pt-2"
                        method="POST"
                        onsubmit="javascript:return confirm('This item will be permanently deleted')">
                        @csrf
                        @method('DELETE')
                        <button title="Delete disk '{{ $disk->part }}'" class="btn pe-0">
                            <i class="fas fa-trash fa-fw text-danger" aria-hidden="true"></i>
                        </button>
                    </form>
                    <small class="text-muted">
                        Created: {{ $disk->created_at ? $disk->created_at->diffForHumans() : '-' }}<br>
                        Updated: {{ $disk->updated_at ? $disk->updated_at->diffForHumans() : '-' }}
                    </small>
                </div>
            </div>
        </div>
    @endforeach
</div>

<a href="{{ route('admin.menus.disks.create', ['menu' => $menu]) }}" class="btn btn-success">
    <i class="fas fa-plus-square fa-fw"></i> Add a new disk to this menu
</a>
