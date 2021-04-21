@if ($menuDisks->isNotEmpty())
    @php
        $menuColumns = 3
    @endphp
    <h2 class="mb-4 text-uppercase text-center">In {{ $menuDisks->count() }} {{ Str::plural('Menu', $menuDisks->count() )}}</h2>

    <div class="row row-cols-1 row-cols-md-{{ $menuColumns }} lightbox-gallery">
        @foreach ($menuDisks as $disk)

            @if ($loop->index == $menuColumns)
                <div class="collapse d-sm-flex w-100 row row-cols-1 row-cols-md-{{ $menuColumns }} ms-0 ps-0 pe-0" id="more-menus">
            @endif

            <div class="col d-flex">
                <div class="card mb-4 bg-dark w-100">
                    <div class="card-header text-center">
                        <h3 class="text-audiowide fs-6">
                            <a href="{{ route('menus.show', ['set' => $disk->menu->menuSet, 'page' => $disk->menuset_page_number]) }}#menudisk-{{ $disk->id }}">
                                {{ $disk->menu->menuSet->name }}
                                {{ $disk->menu->label}}{{ $disk->label}}
                            </a>
                        </h3>
                    </div>

                    <div class="card-body p-0 d-flex flex-column striped">
                            <figure>
                                @if ($disk->screenshots->isNotEmpty())
                                    <a class="lightbox-link"
                                        href="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}"
                                        title="{{ $disk->menu->menuSet->name }} {{ $disk->menu->label }}{{ $disk->part }}">
                                        <img class="card-img-top w-100"
                                            src="{{ asset('storage/images/menu_screenshots/'.$disk->screenshots->first()->file) }}"
                                            alt="Screenshot of disk">
                                    </a>
                                @else
                                    <img class="card-img-top w-100 bg-black"
                                        src="{{ asset('images/no-screenshot.png') }}"
                                        alt="Screenshot of disk">
                                @endif
                            </figure>

                            <div class="px-2 flex-fill">
                                <ul class="list-unstyled ps-2">
                                    @foreach ($disk->contents->sortBy('order') as $content)

                                        {{-- Only show the first 5 items and collapse the rest --}}
                                        @if ($loop->index == 5)
                                            <div class="collapse" id="more-content-{{ $disk->id }}">
                                        @endif

                                        <li>
                                            @if ($content->release)
                                                <a href="{{ route('games.show', $content->release->game) }}">{{ $content->release->game->game_name }}</a>
                                            @elseif ($content->game)
                                                <a href="{{ route('games.show', $content->game) }}">{{ $content->game->game_name }}</a>
                                            @elseif ($content->menuSoftware)
                                                @if (isset($software) && $software->id === $content->menuSoftware->id)
                                                    <b>{{ $content->menuSoftware->name }}</b>
                                                @else
                                                    <a href="{{ route('menus.software', $content->menuSoftware) }}">
                                                        {{ $content->menuSoftware->name }}
                                                    </a>
                                                @endif
                                            @endif
                                            @if ($content->menuSoftware && $content->menuSoftware->demozoo_id)
                                                <a href="https://demozoo.org/productions/{{ $content->menuSoftware->demozoo_id }}/" class="d-inline-block">
                                                    <img src="{{ asset('images/demozoo-16x16.png') }}" class="border-0" alt="Demozoo link for {{ $content->menuSoftware->name }}">
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

                                    {{-- Collapse controls, if there are more than 5 items --}}
                                    @if ($disk->contents->count() > 5)
                                        </div>
                                        <div class="mt-2 ps-2">
                                            <a href="#more-content-{{ $disk->id }}" data-bs-toggle="collapse"
                                                class="text-white" data-al-collapsed-text="Less"
                                                aria-expanded="false" aria-controls="more-content-{{ $disk->id }}">
                                                View {{ $disk->contents->count() - 5 }} more…
                                            </a>
                                        </div>
                                    @endif

                                </ul>
                            </div>

                            <div class="card-footer text-end">
                                @if ($disk->menuDiskDump !== null)
                                    <a class="ms-1 text-muted" data-copy-text="{{ $disk->menuDiskDump->sha512 }}" href="javascript:;"><i class="far fa-copy"></i></a>
                                    <abbr class="text-muted d-inline-block me-2" title="{{ $disk->menuDiskDump->sha512 }}">
                                        <small>{{ Str::limit($disk->menuDiskDump->sha512, 7, '') }}</small>
                                    </abbr>

                                    <a href="{{ asset('storage/zips/menus/'.$disk->menuDiskDump->id.'.zip') }}"
                                        download="{{ $disk->download_filename}}">
                                        <i class="fas fa-download"></i>
                                    </a>
                                @else
                                    <small class="text-muted">No download available</small>
                                @endif
                            </div>
                    </div>
                </div>
            </div>

        @endforeach
        @if ($menuDisks->count() > $menuColumns)
            </div>
            <div class="d-sm-none mb-4 w-100 text-center text-audiowide">
                <a href="#more-menus" data-bs-toggle="collapse"
                    class="text-white" data-al-collapsed-text="Less"
                    aria-expanded="false" aria-controls="more-menus">
                    View {{ $menuDisks->count() - $menuColumns }} more menus…
                </a>
            </div>
        @endif
    </div>
@endif
