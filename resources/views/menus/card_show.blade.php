<div class="card bg-dark mb-4">
    <div class="card-header text-center">
        <h2 class="text-uppercase">{{ $menuset->name }}</h2>
    </div>
    <div class="card-body p-2">
        <p class="card-text">
            <em>{{ $menuset->name }}</em> contains
            {{ $menuset->menus->count() }}
            {{ Str::plural('menu', $menuset->menus->count()) }}
            and
            {{ $menuset->menus->pluck('disks')->flatten()->count() }}
            {{ Str::plural('disk', $menuset->menus->pluck('disks')->flatten()->count()) }}.
        </p>
    </div>
</div>

<div class="row lightbox-gallery">
    @foreach ($menuset->menus->pluck('disks')->flatten() as $disk)
        <div class="col-12">
            <div class="card bg-dark mb-4 border-top-0">
                <div class="card-body p-0 row">
                    <div class="col-3">
                        @if ($disk->screenshots->isNotEmpty())
                            <a class="lightbox-link"
                                href="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}"
                                title="{{ $menuset->name }} {{ $disk->menu->label }}{{ $disk->part }}">
                                <img class="card-img-top w-100"
                                    src="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}"
                                    alt="Screenshot of disk">
                            </a>
                        @else
                            <img class="card-img-top w-100 bg-black"
                                src="{{ asset('images/no-screenshot.png') }}"
                                alt="Screenshot of disk">
                        @endif
                    </div>
                    <div class="col-9 p-2">
                        <h3 class="text-muted fs-5 float-end me-3">{{ $disk->menu->label }}{{ $disk->label }}</h3>

                        <ul class="list-unstyled">
                            @foreach ($disk->contents->sortBy('contentName') as $content)
                                <li class="w-45 d-inline-block">
                                    @if ($content->release)
                                        <a href="{{ route('games.releases.show', $content->release) }}">{{ $content->contentName }}</a>
                                    @elseif ($content->demozoo_id)
                                        <img src="{{ asset('images/demozoo-16x16.png') }}">
                                        <a href="https://demozoo.org/productions/{{ $content->demozoo_id }}/">{{ $content->contentName }}</a>
                                    @else
                                        {{ $content->contentName }}
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
                </div>
                <div class="card-footer bg-darklight">
                    <div class="row">
                        <div class="col text-{{ $conditionClasses[$disk->menuDiskCondition->id] }}">
                            <span class="text-muted">Condition:</span> {{ Str::lower($disk->menuDiskCondition->name) }}
                        </div>
                        <div class="col text-center">
                            <span class="text-muted">Donated by:</span> Todo
                        </div>
                        <div class="col text-end">
                            @if ($disk->menuDiskDump !== null)
                                @auth
                                <a class="ms-1 text-muted" data-copy-text="{{ $disk->menuDiskDump->sha512 }}" href="javascript:;"><i class="far fa-copy"></i></a>
                                    <abbr class="text-muted d-inline-block" title="{{ $disk->menuDiskDump->sha512 }}">
                                        <small>{{ Str::limit($disk->menuDiskDump->sha512, 7, '') }}</small>
                                    </abbr>

                                    <a class="d-inline-block ms-2 me-2" href="{{ asset('storage/zips/menus/'.$disk->menuDiskDump->id.'.zip') }}">
                                        <i class="fas fa-download"></i>
                                    </a>

                                    <small class="text-muted">{{ Helper::fileSize($disk->menuDiskDump->size) }}</small>
                                @endauth
                                @guest
                                    <span class="text-danger">
                                        Please <a href="{{ route('login') }}">log in</a> to download
                                    </span>
                                @endguest
                            @else
                                <small class="text-muted">No download available</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
