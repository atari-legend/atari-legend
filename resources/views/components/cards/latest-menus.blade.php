<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Latest menus</h2>
    </div>
    <div class="card-body p-0 striped">
        @foreach ($dumpsOrDisks as $dumpOrDisk)
            @php
                $menu = null;
                $disk = null;
                $event = null;
                if ($dumpOrDisk instanceof App\Models\MenuDiskDump) {
                    $menu = $dumpOrDisk->menuDisk->menu;
                    $disk = $dumpOrDisk->menuDisk;
                    $event = 'dump';
                } else {
                    $menu = $dumpOrDisk->menu;
                    $disk = $dumpOrDisk;
                    $event = 'disk';
                }
            @endphp
            <div>
                <div class="row g-0 p-2 pb-0">
                    <h3 class="fs-6 mb-0">
                        <a
                            href="{{ route('menus.show', ['set' => $menu->menuSet, 'page' => $disk->menuset_page_number]) }}#menudisk-{{ $disk->id }}">
                            {{ $menu->menuSet->name }}
                            {{ $menu->label }}{{ $disk->label }}
                        </a>
                    </h3>
                </div>
                <div class="row p-2 pb-2 g-0">
                    <div class="col-3">
                        @if ($disk->screenshots->isNotEmpty())
                            <img class="w-100"
                                src="{{ asset('storage/images/menu_screenshots/' . $disk->screenshots->first()->file) }}"
                                alt="Screenshot for disk">
                        @else
                            <img class="w-100 bg-black" src="{{ asset('images/no-screenshot.png') }}"
                                alt="No screenshot for this disk">
                        @endif
                    </div>

                    <div class="col p-2 pt-0">
                        @if ($event === 'dump' && isset($disk->menuDiskDump))
                            <p class="card-text text-muted mb-2">
                                {{ $disk->menuDiskDump->created_at->format('F j, Y') }}
                            </p>

                            Dump updated
                            <a class="d-inline-block ms-2"
                                href="{{ asset('storage/zips/menus/' . $disk->menuDiskDump->id . '.zip') }}"
                                download="{{ $disk->download_filename }}">
                                <i class="fas fa-download"></i>
                            </a>
                            <small class="text-muted">{{ Helper::fileSize($disk->menuDiskDump->size) }}</small>
                        @elseif ($event === 'disk')
                            <p class="card-text text-muted mb-2">
                                {{ $disk->updated_at->format('F j, Y') }}
                            </p>
                            Info updated
                        @else
                            Event: {{ $event }}
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
        <div class="text-center p-2">
            <a href="{{ route('changelog.index') }}">View all database changes</a>
        </div>
    </div>
</div>
