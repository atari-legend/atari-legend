<div class="card bg-dark mb-4 border-top-0">
    <div class="card-header">
        <h3 class="text-muted fs-5 mb-0">
            @if ($disk->scrolltext !== null)
                <a href="javascript:;" class="float-end d-inline-block text-primary" data-bs-toggle="collapse"
                    data-bs-target="#scrolltext-{{ $disk->id }}" role="button" aria-expanded="false">
                    <i class="fas fa-scroll fa-fw" title="Has scrolltext"></i>
                </a>
            @endif
            {{-- Only display the menuset name if we're not displaying the menu set
                (e.g. when listing menus for a software) --}}
            @if (!isset($menuset))
                <a href="{{ route('menus.show', $disk->menu->menuSet) }}" class="text-primary">{{ $disk->menu->menuSet->name }}</a>
            @endif
            #{{ $disk->menu->label }}{{ $disk->label }}
        </h3>
    </div>
    <div class="card-body p-0 row">
        <div class="col-3">
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
        </div>
        <div class="col-9 p-2">
            <ol class="list-unstyled">
                @forelse ($disk->contents->sortBy('order') as $content)
                    <li class="w-45 d-inline-block">
                        <span class="text-muted">{{ $content->order }}.</span>
                        @if ($content->release)
                            <a href="{{ route('games.show', $content->release->game) }}">{{ $content->release->game->game_name }}</a>
                            <a href="{{ route('games.releases.show', $content->release) }}" class="text-muted d-inline-block" title="Release information">
                                <i class="fas fa-info-circle"></i>
                            </a>
                        @elseif ($content->game)
                            <a href="{{ route('games.show', $content->game) }}">{{ $content->game->game_name }}</a>
                        @elseif ($content->menuSoftware)
                            @if ($content->menuSoftware->demozoo_id)
                                <a href="https://demozoo.org/productions/{{ $content->menuSoftware->demozoo_id }}/" class="d-inline-block">
                                    <img src="{{ asset('images/demozoo-16x16.png') }}" alt="Demozoo link for {{ $content->menuSoftware->name }}">
                                </a>
                            @endif
                            @if (isset($software) && $software->id === $content->menuSoftware->id)
                                <b>{{ $content->menuSoftware->name }}</b>
                            @else
                                <a href="{{ route('menus.software', $content->menuSoftware) }}">
                                    {{ $content->menuSoftware->name }}
                                </a>
                            @endif
                        @endif

                        @if ($content->notes)
                            <small class="text-muted"><em>{{ $content->notes }}</em></small>
                        @endif

                        @if ($content->subtype)
                            <small class="text-muted">[{{ $content->subtype }}]</small>
                        @endif
                    </li>
                @empty
                    <span class="text-muted">Unknown content</span>
                @endforelse
            </ol>
        </div>
    </div>
    @if ($disk->scrolltext !== null)
        <div class="collapse p-2 my-2 bg-black border border-secondary" id="scrolltext-{{ $disk->id }}">
            <code class="text-white">{{ $disk->scrolltext }}</code>
            <button type="button" class="btn float-end text-primary"
                data-bs-toggle="collapse"
                data-bs-target="#scrolltext-{{ $disk->id }}"><i class="fas fa-2x fa-times"></i></button>
        </div>
    @endif
    <div class="card-footer bg-darklight">
        <div class="row">
            <div class="col text-{{ $conditionClasses[$disk->menuDiskCondition->id] }}">
                <span class="text-muted">Condition:</span> {{ Str::lower($disk->menuDiskCondition->name) }}
            </div>
            <div class="col text-center">
                @if ($disk->donatedBy !== null)
                    <span class="text-muted">Donated by:</span>
                    @if ($disk->donatedBy->games->isNotEmpty())
                        <a href="{{ route('games.search', ['individual_id' => $disk->donatedBy->ind_id]) }}">{{ $disk->donatedBy->ind_name}}</a>
                    @else
                        {{ $disk->donatedBy->ind_name }}
                    @endif
                @endif
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
