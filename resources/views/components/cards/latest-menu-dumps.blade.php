<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">Latest menus</h2>
    </div>
    <div class="card-body p-0 striped">
        @foreach ($dumps as $dump)
            <div>
                <div class="row g-0 p-2 pb-0">
                    <h3 class="fs-6 mb-0">
                        <a href="{{ route('menus.show', ['set' => $dump->menuDisk->menu->menuSet, 'page' => $dump->menuDisk->menuset_page_number]) }}#menudisk-{{ $dump->menuDisk->id }}">
                            {{ $dump->menuDisk->menu->menuSet->name }}
                            {{ $dump->menuDisk->menu->label }}{{ $dump->menuDisk->label }}
                        </a>
                    </h3>
                </div>
                <div class="row p-2 pb-2 g-0">
                    @if ($dump->menuDisk->screenshots->isNotEmpty())
                        <div class="col-3">
                            <img class="w-100"
                                src="{{ asset('storage/images/menu_screenshots/' . $dump->menuDisk->screenshots->first()->file) }}">
                        </div>
                    @endif
                    <div class="col p-2 pt-0">
                        <p class="card-text text-muted mb-0">
                            {{ $dump->created_at->format('F j, Y') }}
                            by {{ Helper::User($dump->user) }}<br>
                        </p>

                        @auth
                            <a class="d-inline-block mt-2 me-2  "
                                href="{{ asset('storage/zips/menus/' . $dump->menuDisk->menuDiskDump->id . '.zip') }}"
                                download="{{ $dump->menuDisk->download_filename }}">
                                <i class="fas fa-download"></i>
                            </a>
                            <small class="text-muted">{{ Helper::fileSize($dump->menuDisk->menuDiskDump->size) }}</small>
                        @endauth
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
